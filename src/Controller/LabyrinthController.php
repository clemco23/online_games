<?php

namespace App\Controller;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use RuntimeException;

class LabyrinthController extends AppController
{
    private const MAX_AP = 15;
    private const AP_REGEN_AMOUNT = 5;

    private const DIRECTIONS = [
        'up' => ['x' => 0, 'y' => -1],
        'down' => ['x' => 0, 'y' => 1],
        'left' => ['x' => -1, 'y' => 0],
        'right' => ['x' => 1, 'y' => 0],
    ];

    public function beforeFilter($event)
    {
        $this->requiresAuth = true;

        return parent::beforeFilter($event);
    }

    public function index()
    {
        $boardgame = $this->findLabyrinthBoardgame();
        $userId = $this->currentUserId();
        $Sessionsgames = $this->fetchTable('Sessionsgames');

        $mySessions = $Sessionsgames->find()
            ->matching('UsersSessionsgames', function ($q) use ($userId) {
                return $q->where(['UsersSessionsgames.user_id' => $userId]);
            })
            ->where(['Sessionsgames.boardgames_id' => $boardgame->id])
            ->contain([
                'LabyrinthGames',
                'UsersSessionsgames' => function ($q) {
                    return $q
                        ->contain(['Users'])
                        ->orderBy(['UsersSessionsgames.id' => 'ASC']);
                },
            ])
            ->orderBy([
                'Sessionsgames.isfinish' => 'ASC',
                'Sessionsgames.modified' => 'DESC',
            ])
            ->all();

        $openSessionRows = $Sessionsgames->find()
            ->where([
                'Sessionsgames.boardgames_id' => $boardgame->id,
                'Sessionsgames.isfinish' => false,
            ])
            ->contain([
                'UsersSessionsgames' => function ($q) {
                    return $q
                        ->contain(['Users'])
                        ->orderBy(['UsersSessionsgames.id' => 'ASC']);
                },
            ])
            ->orderBy(['Sessionsgames.id' => 'DESC'])
            ->all();

        $openSessions = [];
        foreach ($openSessionRows as $session) {
            if (count($session->users_sessionsgames) >= 2) {
                continue;
            }

            if ($this->isUserInSession($session->users_sessionsgames, $userId)) {
                continue;
            }

            $openSessions[] = $session;
        }

        $this->set(compact('boardgame', 'mySessions', 'openSessions'));
    }

    public function new( $boardgameId = null)
    {
        $this->request->allowMethod(['get', 'post']);

        $boardgame = $boardgameId !== null
            ? $this->fetchTable('Boardgames')->get($boardgameId)
            : $this->findLabyrinthBoardgame();

        $userId = $this->currentUserId();
        $username = $this->currentUsername();
        $Sessionsgames = $this->fetchTable('Sessionsgames');
        $UsersSessionsgames = $this->fetchTable('UsersSessionsgames');
        $session = $this->findJoinableSession($boardgame->id, $userId);

        if ($session === null) {
            $session = $Sessionsgames->newEntity([
                'boardgames_id' => $boardgame->id,
                'isfinish' => false,
                'code' => $this->generateSessionCode(),
            ]);
            $Sessionsgames->saveOrFail($session);

            $UsersSessionsgames->saveOrFail($UsersSessionsgames->newEntity([
                'user_id' => $userId,
                'session_game_id' => $session->id,
                'final_score' => 0,
                'time_session' => '00:00:00',
                'is_winner' => false,
            ]));

            $this->createLabyrinthGame($session->id, $boardgame->id, $userId, $username);
        } else {
            $alreadyInSession = $this->isUserInSession($session->users_sessionsgames, $userId);
            if (!$alreadyInSession) {
                $UsersSessionsgames->saveOrFail($UsersSessionsgames->newEntity([
                    'user_id' => $userId,
                    'session_game_id' => $session->id,
                    'final_score' => 0,
                    'time_session' => '00:00:00',
                    'is_winner' => false,
                ]));
            }

            $labyrinthGame = $session->labyrinth_game ?? null;
            if ($labyrinthGame === null) {
                $labyrinthGame = $this->createLabyrinthGame(
                    $session->id,
                    $boardgame->id,
                    $userId,
                    $username,
                );
            }

            $this->assignLabyrinthPlayer($labyrinthGame, $userId, $username);
        }

        return $this->redirect(['action' => 'play', $session->id]);
    }

    public function start($boardgameId = null)
    {
        return $this->new($boardgameId);
    }

    public function join($sessionGameId)
    {
        $session = $this->loadSession($sessionGameId);
        $userId = $this->currentUserId();

        if ($session->isfinish) {
            $this->Flash->error('Cette partie est deja terminee.');

            return $this->redirect(['action' => 'index']);
        }

        if (!$this->isUserInSession($session->users_sessionsgames, $userId)) {
            if (count($session->users_sessionsgames) >= 2) {
                $this->Flash->error('Cette partie est deja complete.');

                return $this->redirect(['action' => 'index']);
            }

            $UsersSessionsgames = $this->fetchTable('UsersSessionsgames');
            $UsersSessionsgames->saveOrFail($UsersSessionsgames->newEntity([
                'user_id' => $userId,
                'session_game_id' => $session->id,
                'final_score' => 0,
                'time_session' => '00:00:00',
                'is_winner' => false,
            ]));
        }

        $this->assignLabyrinthPlayer(
            $session->labyrinth_game,
            $userId,
            $this->currentUsername(),
        );

        return $this->redirect(['action' => 'play', $session->id]);
    }

    public function play($sessionGameId)
    {
        $session = $this->loadSession($sessionGameId);
        if (!$this->isUserInSession($session->users_sessionsgames, $this->currentUserId())) {
            $this->Flash->error('Vous ne faites pas partie de cette partie.');

            return $this->redirect(['controller' => 'Boardgames', 'action' => 'index']);
        }

        $state = $this->buildLabyrinthState($session->labyrinth_game, $this->currentUserId());

        $this->set(compact('session', 'state'));
    }

    public function move($sessionGameId)
    {
        $this->request->allowMethod(['post']);

        $redirect = ['action' => 'play', $sessionGameId];

        try {
            $session = $this->loadSession($sessionGameId);
            $this->assertSessionPlayer($session);

            $direction = $this->request->getData('direction');
            $result = $this->moveLabyrinthPlayer($session->labyrinth_game, $this->currentUserId(), $direction);

            if ($result['won']) {
                $this->finishSession($result['game'], $this->currentUserId());
            }

            if ($result['success']) {
                $this->Flash->success($result['message']);
            } else {
                $this->Flash->error($result['message']);
            }
        } catch (ForbiddenException $exception) {
            $this->Flash->error($exception->getMessage());
            $redirect = ['action' => 'index'];
        } catch (NotFoundException $exception) {
            $this->Flash->error($exception->getMessage());
            $redirect = ['action' => 'index'];
        } catch (RuntimeException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect($redirect);
    }

    private function findLabyrinthBoardgame()
    {
        return $this->fetchTable('Boardgames')->find()
            ->where(['Boardgames.name IN' => ['Labyrinth', 'Labyrinthe']])
            ->orderBy(['Boardgames.id' => 'ASC'])
            ->firstOrFail();
    }

    private function findJoinableSession($boardgameId, $userId)
    {
        $sessions = $this->fetchTable('Sessionsgames')->find()
            ->where([
                'Sessionsgames.boardgames_id' => $boardgameId,
                'Sessionsgames.isfinish' => false,
            ])
            ->contain([
                'LabyrinthGames',
                'UsersSessionsgames' => function ($q) {
                    return $q->orderBy(['UsersSessionsgames.id' => 'ASC']);
                },
            ])
            ->orderBy(['Sessionsgames.id' => 'ASC'])
            ->all();

        foreach ($sessions as $session) {
            if ($this->isUserInSession($session->users_sessionsgames, $userId)) {
                return $session;
            }

            if (count($session->users_sessionsgames) < 2) {
                return $session;
            }
        }

        return null;
    }

    private function loadSession($sessionGameId)
    {
        $session = $this->fetchTable('Sessionsgames')->find()
            ->where(['Sessionsgames.id' => $sessionGameId])
            ->contain([
                'Boardgames',
                'LabyrinthGames',
                'UsersSessionsgames' => function ($q) {
                    return $q
                        ->contain(['Users'])
                        ->orderBy(['UsersSessionsgames.id' => 'ASC']);
                },
            ])
            ->first();

        if (!$session || !$session->labyrinth_game) {
            throw new NotFoundException('Partie Labyrinth introuvable.');
        }

        return $session;
    }

    private function assertSessionPlayer($session)
    {
        if (!$this->isUserInSession($session->users_sessionsgames, $this->currentUserId())) {
            throw new ForbiddenException('Vous ne faites pas partie de cette partie.');
        }
    }

    private function createLabyrinthGame(
        $sessionGameId,
        $boardGameId,
        $userId,
        $username,
        $mapName = 'default.txt',
    ) {
        $map = $this->loadLabyrinthMap($mapName);
        [$player1Start, $player2Start] = $this->findAdjacentStarts($map);

        $playerData = [
            'players' => [
                [
                    'slot' => 1,
                    'user_id' => $userId,
                    'username' => $username,
                    'x' => $player1Start['x'],
                    'y' => $player1Start['y'],
                    'ap' => self::MAX_AP,
                    'last_ap_regen_at' => time(),
                    'winner' => false,
                ],
                [
                    'slot' => 2,
                    'user_id' => null,
                    'username' => null,
                    'x' => $player2Start['x'],
                    'y' => $player2Start['y'],
                    'ap' => self::MAX_AP,
                    'last_ap_regen_at' => time(),
                    'winner' => false,
                ],
            ],
            'winner_user_id' => null,
            'finished_at' => null,
        ];

        $LabyrinthGames = $this->fetchTable('LabyrinthGames');
        $game = $LabyrinthGames->newEntity([
            'session_game_id' => $sessionGameId,
            'board_game_id' => $boardGameId,
            'map_name' => $map['name'],
            'treasure_pos' => json_encode($map['treasure'], JSON_THROW_ON_ERROR),
            'player_data' => json_encode($playerData, JSON_THROW_ON_ERROR),
        ]);

        return $LabyrinthGames->saveOrFail($game);
    }

    private function assignLabyrinthPlayer($game, $userId, $username)
    {
        $userId = $userId;
        $playerData = $this->decodeLabyrinthPlayerData($game);

        foreach ($playerData['players'] as $player) {
            if (($player['user_id'] ?? 0) === $userId) {
                return $game;
            }
        }

        foreach ($playerData['players'] as $index => $player) {
            if ($player['user_id'] === null) {
                $playerData['players'][$index]['user_id'] = $userId;
                $playerData['players'][$index]['username'] = $username;
                $playerData['players'][$index]['last_ap_regen_at'] = time();
                $game->player_data = json_encode($playerData, JSON_THROW_ON_ERROR);

                return $this->fetchTable('LabyrinthGames')->saveOrFail($game);
            }
        }

        throw new RuntimeException('Cette partie Labyrinth est deja complete.');
    }

    private function moveLabyrinthPlayer($game, $userId, $direction)
    {
        $userId = $userId;

        if (!isset(self::DIRECTIONS[$direction])) {
            return [
                'success' => false,
                'message' => 'Direction invalide.',
                'won' => false,
                'game' => $game,
            ];
        }

        $this->regenerateLabyrinthAp($game);

        $map = $this->loadLabyrinthMap($game->map_name);
        $playerData = $this->decodeLabyrinthPlayerData($game);

        if (!empty($playerData['winner_user_id'])) {
            return [
                'success' => false,
                'message' => 'La partie est deja terminee.',
                'won' => false,
                'game' => $game,
            ];
        }

        if ($this->assignedPlayerCount($playerData) < 2) {
            return [
                'success' => false,
                'message' => 'En attente du second joueur.',
                'won' => false,
                'game' => $game,
            ];
        }

        $playerIndex = $this->findPlayerIndex($playerData, $userId);
        if ($playerIndex === null) {
            return [
                'success' => false,
                'message' => 'Vous ne faites pas partie de cette partie.',
                'won' => false,
                'game' => $game,
            ];
        }

        $player = $playerData['players'][$playerIndex];
        if ($player['ap'] <= 0) {
            return [
                'success' => false,
                'message' => 'Plus assez de points d action.',
                'won' => false,
                'game' => $game,
            ];
        }

        $delta = self::DIRECTIONS[$direction];
        $target = [
            'x' => $player['x'] + $delta['x'],
            'y' => $player['y'] + $delta['y'],
        ];

        if (!$this->isWalkable($map, $target['x'], $target['y'])) {
            return [
                'success' => false,
                'message' => 'Impossible de traverser un mur.',
                'won' => false,
                'game' => $game,
            ];
        }

        if ($this->isOccupiedByOtherPlayer($playerData, $userId, $target['x'], $target['y'])) {
            return [
                'success' => false,
                'message' => 'Cette case est occupee par l autre joueur.',
                'won' => false,
                'game' => $game,
            ];
        }

        $playerData['players'][$playerIndex]['x'] = $target['x'];
        $playerData['players'][$playerIndex]['y'] = $target['y'];
        $playerData['players'][$playerIndex]['ap'] = max(0,$player['ap'] - 1);
        $playerData['players'][$playerIndex]['last_ap_regen_at'] = time();

        $won = $target === $map['treasure'];
        if ($won) {
            $playerData['players'][$playerIndex]['winner'] = true;
            $playerData['winner_user_id'] = $userId;
            $playerData['finished_at'] = date(DATE_ATOM);
        }

        $game->player_data = json_encode($playerData, JSON_THROW_ON_ERROR);
        $game = $this->fetchTable('LabyrinthGames')->saveOrFail($game);

        return [
            'success' => true,
            'message' => $won ? 'Tresor atteint.' : 'Deplacement effectue.',
            'won' => $won,
            'game' => $game,
        ];
    }

    private function buildLabyrinthState($game, $currentUserId)
    {
        $currentUserId = $currentUserId;
        $this->regenerateLabyrinthAp($game);

        $map = $this->loadLabyrinthMap($game->map_name);
        $playerData = $this->decodeLabyrinthPlayerData($game);
        $players = [];
        $currentPlayer = null;

        foreach ($playerData['players'] as $player) {
            $normalized = [
                'slot' => $player['slot'],
                'user_id' => $player['user_id'] === null ? null : $player['user_id'],
                'username' => $player['username'],
                'x' => $player['x'],
                'y' => $player['y'],
                'ap' => $player['ap'],
                'next_ap_in' => $this->secondsUntilNextAp($game, $player),
                'winner' => $player['winner'],
                'is_current' => ($player['user_id'] ?? 0) === $currentUserId,
            ];

            if ($normalized['is_current']) {
                $currentPlayer = $normalized;
            }

            $players[] = $normalized;
        }

        $winnerUserId = $playerData['winner_user_id'] ?? null;
        $state = [
            'game_id' => $game->id,
            'session_game_id' => $game->session_game_id,
            'map' => [
                'name' => $map['name'],
                'width' => $map['width'],
                'height' => $map['height'],
                'tiles' => $map['tiles'],
                'treasure' => $map['treasure'],
            ],
            'players' => $players,
            'current_player' => $currentPlayer,
            'status' => [
                'waiting' => $this->assignedPlayerCount($playerData) < 2,
                'finished' => $winnerUserId !== null,
                'winner_user_id' => $winnerUserId === null ? null : $winnerUserId,
                'max_ap' => self::MAX_AP,
            ],
        ];
        $state['available_moves'] = $this->buildAvailableMoves($map, $playerData, $currentPlayer, $currentUserId);

        return $state;
    }

    private function regenerateLabyrinthAp($game)
    {
        $playerData = $this->decodeLabyrinthPlayerData($game);
        if (!empty($playerData['winner_user_id'])) {
            return 0;
        }

        $updatedPlayers = 0;
        $now = time();

        foreach ($playerData['players'] as $index => $player) {
            if ($player['user_id'] === null) {
                continue;
            }

            $currentAp = $player['ap'];
            $lastRegenAt = $this->lastApRegenAt($game, $player);
            $playerChanged = false;

            if ($currentAp >= self::MAX_AP) {
                if (!isset($player['last_ap_regen_at'])) {
                    $playerData['players'][$index]['last_ap_regen_at'] = $now;
                    $playerChanged = true;
                }
                if ($playerChanged) {
                    $updatedPlayers++;
                }
                continue;
            }

            if (!isset($player['last_ap_regen_at'])) {
                $playerData['players'][$index]['last_ap_regen_at'] = $lastRegenAt;
                $playerChanged = true;
            }

            $elapsedSeconds = max(0, $now - $lastRegenAt);
            $elapsedMinutes = intdiv($elapsedSeconds, 60);

            if ($elapsedMinutes <= 0) {
                if ($playerChanged) {
                    $updatedPlayers++;
                }
                continue;
            }

            $nextAp = min(self::MAX_AP, $currentAp + ($elapsedMinutes * self::AP_REGEN_AMOUNT));
            $playerData['players'][$index]['ap'] = $nextAp;
            $playerData['players'][$index]['last_ap_regen_at'] = $nextAp >= self::MAX_AP
                ? $now
                : $lastRegenAt + ($elapsedMinutes * 60);
            $updatedPlayers++;
        }

        if ($updatedPlayers > 0) {
            $game->player_data = json_encode($playerData, JSON_THROW_ON_ERROR);
            $this->fetchTable('LabyrinthGames')->saveOrFail($game);
        }

        return $updatedPlayers;
    }

    private function loadLabyrinthMap( $mapName)
    {
        $safeName = basename($mapName);
        if (pathinfo($safeName, PATHINFO_EXTENSION) !== 'txt') {
            $safeName .= '.txt';
        }

        $path = WWW_ROOT . 'maps' . DS . 'labyrinth' . DS . $safeName;
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Map "%s" introuvable.', $safeName));
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false || $lines === []) {
            throw new RuntimeException(sprintf('Map "%s" vide.', $safeName));
        }

        $width = strlen($lines[0]);
        $tiles = [];
        $treasure = null;

        foreach ($lines as $y => $line) {
            $line = rtrim($line, "\r\n");
            if (strlen($line) !== $width) {
                throw new RuntimeException(sprintf('Map "%s" invalide: lignes de tailles differentes.', $safeName));
            }

            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $char = $line[$x];
                if (!in_array($char, ['#', '.', 'T'], true)) {
                    throw new RuntimeException(sprintf('Caractere "%s" interdit dans la map "%s".', $char, $safeName));
                }

                if ($char === 'T') {
                    if ($treasure !== null) {
                        throw new RuntimeException(sprintf('Map "%s" invalide: plusieurs tresors.', $safeName));
                    }
                    $treasure = ['x' => $x, 'y' => $y];
                }

                $row[] = $char;
            }

            $tiles[] = $row;
        }

        if ($treasure === null) {
            throw new RuntimeException(sprintf('Map "%s" invalide: aucun tresor.', $safeName));
        }

        return [
            'name' => $safeName,
            'width' => $width,
            'height' => count($tiles),
            'tiles' => $tiles,
            'treasure' => $treasure,
        ];
    }

    private function decodeLabyrinthPlayerData($game)
    {
        $decoded = json_decode($game->player_data, true);
        if (!is_array($decoded) || !isset($decoded['players']) || !is_array($decoded['players'])) {
            throw new RuntimeException('Donnees de joueurs invalides.');
        }

        return $decoded;
    }

    private function findAdjacentStarts( $map)
    {
        for ($y = 0; $y < $map['height']; $y++) {
            for ($x = 0; $x < $map['width']; $x++) {
                if (!$this->isPathStartTile($map, $x, $y)) {
                    continue;
                }

                foreach ([[1, 0], [0, 1]] as [$dx, $dy]) {
                    $nextX = $x + $dx;
                    $nextY = $y + $dy;
                    if ($this->isPathStartTile($map, $nextX, $nextY)) {
                        return [
                            ['x' => $x, 'y' => $y],
                            ['x' => $nextX, 'y' => $nextY],
                        ];
                    }
                }
            }
        }

        throw new RuntimeException('La map doit contenir deux cases de depart adjacentes.');
    }

    private function isPathStartTile( $map,  $x,  $y)
    {
        if (!$this->isInsideMap($map, $x, $y)) {
            return false;
        }

        return $map['tiles'][$y][$x] === '.';
    }

    private function isWalkable($map, $x, $y)
    {
        if (!$this->isInsideMap($map, $x, $y)) {
            return false;
        }

        return in_array($map['tiles'][$y][$x], ['.', 'T'], true);
    }

    private function isInsideMap( $map,  $x,  $y)
    {
        return $x >= 0 && $y >= 0 && $x < $map['width'] && $y < $map['height'];
    }

    private function assignedPlayerCount( $playerData)
    {
        $count = 0;
        foreach ($playerData['players'] as $player) {
            if ($player['user_id'] !== null) {
                $count++;
            }
        }

        return $count;
    }

    private function lastApRegenAt($game, $player)
    {
        if (isset($player['last_ap_regen_at']) && is_numeric($player['last_ap_regen_at'])) {
            return $player['last_ap_regen_at'];
        }

        if ($game->modified instanceof \DateTimeInterface) {
            return $game->modified->getTimestamp();
        }

        if ($game->created instanceof \DateTimeInterface) {
            return $game->created->getTimestamp();
        }

        return time();
    }

    private function secondsUntilNextAp($game, $player)
    {
        if ($player['user_id'] === null || $player['ap'] >= self::MAX_AP) {
            return null;
        }

        $elapsed = max(0, time() - $this->lastApRegenAt($game, $player));
        $remaining = 60 - ($elapsed % 60);

        return $remaining === 0 ? 60 : $remaining;
    }

    private function findPlayerIndex( $playerData,  $userId)
    {
        $userId = $userId;

        foreach ($playerData['players'] as $index => $player) {
            if (($player['user_id'] ?? 0) === $userId) {
                return $index;
            }
        }

        return null;
    }

    private function isOccupiedByOtherPlayer( $playerData,  $userId,  $x,  $y)
    {
        $userId = $userId;

        foreach ($playerData['players'] as $player) {
            if ($player['user_id'] === null || $player['user_id'] === $userId) {
                continue;
            }

            if ($player['x'] === $x && $player['y'] === $y) {
                return true;
            }
        }

        return false;
    }

    private function buildAvailableMoves(
        $map,
        $playerData,
        $currentPlayer,
        $currentUserId,
    ) {
        $moves = array_fill_keys(array_keys(self::DIRECTIONS), false);

        if (
            $currentPlayer === null
            || !empty($playerData['winner_user_id'])
            || $this->assignedPlayerCount($playerData) < 2
            || $currentPlayer['ap'] <= 0
        ) {
            return $moves;
        }

        foreach (self::DIRECTIONS as $direction => $delta) {
            $targetX = $currentPlayer['x'] + $delta['x'];
            $targetY = $currentPlayer['y'] + $delta['y'];

            $moves[$direction] = $this->isWalkable($map, $targetX, $targetY)
                && !$this->isOccupiedByOtherPlayer($playerData, $currentUserId, $targetX, $targetY);
        }

        return $moves;
    }

    private function finishSession($labyrinthGame, $winnerUserId)
    {
        $winnerUserId = $winnerUserId;
        $Sessionsgames = $this->fetchTable('Sessionsgames');
        $UsersSessionsgames = $this->fetchTable('UsersSessionsgames');

        $session = $Sessionsgames->get($labyrinthGame->session_game_id);
        if (!$session->isfinish) {
            $session->isfinish = true;
            $Sessionsgames->saveOrFail($session);
        }

        $playerData = $this->decodeLabyrinthPlayerData($labyrinthGame);
        $duration = $this->calculateDuration($session->created);

        foreach ($playerData['players'] as $player) {
            if ($player['user_id'] === null) {
                continue;
            }

            $userSession = $UsersSessionsgames->find()
                ->where([
                    'user_id' => $player['user_id'],
                    'session_game_id' => $session->id,
                ])
                ->first();

            if ($userSession === null) {
                continue;
            }

            $isWinner = $player['user_id'] === $winnerUserId;
            $userSession->final_score = $isWinner ? 1 : 0;
            $userSession->time_session = $duration;
            $userSession->is_winner = $isWinner;
            $UsersSessionsgames->saveOrFail($userSession);
        }
    }

    private function isUserInSession( $usersSessionsgames,  $userId)
    {
        $userId = $userId;

        foreach ($usersSessionsgames as $usersSession) {
            if ($usersSession->user_id === $userId) {
                return true;
            }
        }

        return false;
    }

    private function currentUserId()
    {
        if (is_object($this->currentUser) && method_exists($this->currentUser, 'getIdentifier')) {
            return $this->currentUser->getIdentifier();
        }

        if (is_object($this->currentUser) && isset($this->currentUser->id)) {
            return $this->currentUser->id;
        }

        if (is_array($this->currentUser) && isset($this->currentUser['id'])) {
            return $this->currentUser['id'];
        }

        throw new ForbiddenException('Utilisateur introuvable.');
    }

    private function currentUsername()
    {
        if (is_object($this->currentUser) && isset($this->currentUser->username)) {
            return $this->currentUser->username;
        }

        if (is_object($this->currentUser) && method_exists($this->currentUser, 'get')) {
            return ($this->currentUser->get('username') ?? 'Joueur');
        }

        if (is_array($this->currentUser) && isset($this->currentUser['username'])) {
            return $this->currentUser['username'];
        }

        return 'Joueur';
    }

    private function calculateDuration($start)
    {
        $seconds = max(0, time() - $start->getTimestamp());

        return gmdate('H:i:s', $seconds);
    }

    private function generateSessionCode( $length = 10)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($chars) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
