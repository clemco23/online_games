<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Sessionsgame;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query\SelectQuery;

class FillersController extends AppController
{
    protected bool $requiresAuth = true;

    /**
     * Lists the current user's Filler sessions and open sessions.
     *
     * @return void
     */
    public function index()
    {
        $boardgame = $this->findFillerBoardgame();
        $userId = $this->currentUserId();
        $Sessionsgames = $this->fetchTable('Sessionsgames');

        $mySessions = $Sessionsgames->find()
            ->matching('UsersSessionsgames', function ($q) use ($userId) {
                return $q->where(['UsersSessionsgames.user_id' => $userId]);
            })
            ->where(['Sessionsgames.boardgames_id' => $boardgame->id])
            ->contain([
                'Fillers',
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

    /**
     * Creates a new session or joins an open one.
     *
     * @param int|null $boardgameId Boardgame catalog id.
     * @return \Cake\Http\Response|null
     */
    public function new( $boardgameId = null)
    {
        $this->request->allowMethod(['get', 'post']);

        $boardgame = $boardgameId !== null
            ? $this->fetchTable('Boardgames')->get($boardgameId)
            : $this->findFillerBoardgame();

        $userId = $this->currentUserId();
        $Sessionsgames = $this->fetchTable('Sessionsgames');
        $session = $this->findJoinableSession($boardgame->id, $userId);

        if ($session === null) {
            $session = $Sessionsgames->newEntity([
                'boardgames_id' => $boardgame->id,
                'isfinish' => false,
                'code' => $this->generateSessionCode(),
            ]);
            $Sessionsgames->saveOrFail($session);
        }

        $this->attachCurrentUserToSession($session, $userId);
        $session = $this->loadFillerSession($session->id);
        $this->ensureFillerForSession($session, $userId);

        return $this->redirect(['action' => 'play', $session->id]);
    }

    /**
     * Compatibility entry point used by the boardgame catalog button.
     *
     * @param int|null $boardgameId Boardgame catalog id.
     * @return \Cake\Http\Response|null
     */
    public function start($boardgameId = null)
    {
        return $this->new($boardgameId);
    }

    /**
     * Joins a specific open Filler session.
     *
     * @param int $sessionGameId Session id.
     * @return \Cake\Http\Response|null
     */
    public function join($sessionGameId)
    {
        $session = $this->loadFillerSession($sessionGameId);
        $userId = $this->currentUserId();

        if ($session->isfinish) {
            $this->Flash->error('Cette partie est deja terminee.');

            return $this->redirect(['action' => 'index']);
        }

        if (
            !$this->isUserInSession($session->users_sessionsgames, $userId)
            && count($session->users_sessionsgames) >= 2
        ) {
            $this->Flash->error('Cette partie est deja complete.');

            return $this->redirect(['action' => 'index']);
        }

        $this->attachCurrentUserToSession($session, $userId);
        $session = $this->loadFillerSession($sessionGameId);
        $this->ensureFillerForSession($session, $userId);

        return $this->redirect(['action' => 'play', $session->id]);
    }

    /**
     * Renders the Filler game screen.
     *
     * @param int $sessionGameId Session id.
     * @return \Cake\Http\Response|null|void
     */
    public function play($sessionGameId)
    {
        $userId = $this->currentUserId();

        $Sessionsgames = $this->fetchTable('Sessionsgames');
        $session = $Sessionsgames->find()
            ->where(['Sessionsgames.id' => $sessionGameId])
            ->contain([
                'Boardgames',
                'Fillers',
                'UsersSessionsgames' => function (SelectQuery $q) {
                    return $q
                        ->contain(['Users'])
                        ->orderBy(['UsersSessionsgames.id' => 'ASC']);
                },
            ])
            ->first();

        if (!$session) {
            throw new NotFoundException('Session introuvable.');
        }

        $isInSession = false;
        foreach ($session->users_sessionsgames as $usersSession) {
            if ((int)$usersSession->user_id === $userId) {
                $isInSession = true;
                break;
            }
        }

        if (!$isInSession) {
            $this->Flash->error('Vous ne faites pas partie de cette session.');

            return $this->redirect(['controller' => 'Boardgames', 'action' => 'index']);
        }

        $filler = $this->ensureFillerForSession($session, $userId);

        $grid = json_decode($filler->grid, true);
        if (!is_array($grid)) {
            $grid = [];
        }

        $players = $session->users_sessionsgames;
        $player1 = $players[0] ?? null;
        $player2 = $players[1] ?? null;

        $currentPlayerNumber = null;
        if ($player1 && (int)$player1->user_id === $userId) {
            $currentPlayerNumber = 1;
        } elseif ($player2 && (int)$player2->user_id === $userId) {
            $currentPlayerNumber = 2;
        }

        $isWaitingOpponent = count($players) < 2;
        $isMyTurn = !$isWaitingOpponent
            && !$session->isfinish
            && (int)$filler->current_turn_user_id === $userId;

        $player1Cells = [];
        $player2Cells = [];
        $allowedColors = [];
        $remainingColors = [];
        $winnerMessage = null;
        $player1Score = 0;
        $player2Score = 0;

        if (!empty($grid)) {
            $player1Cells = $this->getPlayerTerritory($grid, 1);
            $player2Cells = $this->getPlayerTerritory($grid, 2);
            $player1Score = count($player1Cells);
            $player2Score = count($player2Cells);
            $remainingColors = $this->getRemainingColors($grid);
        }

        if (!$isWaitingOpponent && !$session->isfinish && !empty($grid)) {
            $player1Color = (int)$grid[0][0];
            $player2Color = (int)$grid[count($grid) - 1][count($grid[0]) - 1];

            if ($currentPlayerNumber === 1) {
                $allowedColors = array_values(array_diff([1, 2, 3, 4], [$player1Color, $player2Color]));
            } elseif ($currentPlayerNumber === 2) {
                $allowedColors = array_values(array_diff([1, 2, 3, 4], [$player2Color, $player1Color]));
            }
        }

        if ($session->isfinish && $player1 && $player2) {
            if ($player1Score > $player2Score) {
                $winnerMessage = 'Le joueur 1 a gagné.';
            } elseif ($player2Score > $player1Score) {
                $winnerMessage = 'Le joueur 2 a gagné.';
            } else {
                $winnerMessage = 'Égalité.';
            }
        }

        $this->set(compact(
            'session',
            'filler',
            'grid',
            'players',
            'player1',
            'player2',
            'currentPlayerNumber',
            'isWaitingOpponent',
            'player1Cells',
            'player2Cells',
            'allowedColors',
            'isMyTurn',
            'remainingColors',
            'winnerMessage',
            'player1Score',
            'player2Score'
        ));
    }

    public function chooseColor($sessionGameId = null, $color = null)
    {
        $passParams = $this->request->getParam('pass') ?? [];
        $sessionGameId = (int)($sessionGameId ?? ($passParams[0] ?? 0));
        $color = filter_var($color ?? ($passParams[1] ?? null), FILTER_VALIDATE_INT);

        $userId = $this->currentUserId();

        if ($color === false || !in_array($color, [1, 2, 3, 4], true)) {
            $this->Flash->error('Couleur invalide.');
            return $this->redirect(['action' => 'play', $sessionGameId]);
        }

        $Sessionsgames = $this->fetchTable('Sessionsgames');
        $Fillers = $this->fetchTable('Fillers');
        $UsersSessionsgames = $this->fetchTable('UsersSessionsgames');

        $session = $Sessionsgames->find()
            ->where(['Sessionsgames.id' => $sessionGameId])
            ->contain([
                'Fillers',
                'UsersSessionsgames' => function (SelectQuery $q) {
                    return $q->orderBy(['UsersSessionsgames.id' => 'ASC']);
                }
            ])
            ->first();

        if (!$session) {
            throw new NotFoundException('Session introuvable.');
        }

        if ($session->isfinish) {
            $this->Flash->error('La partie est déjà terminée.');
            return $this->redirect(['action' => 'play', $sessionGameId]);
        }

        if (!$session->filler) {
            throw new NotFoundException('Données Filler introuvables pour cette session.');
        }

        $session->filler = $this->ensureFillerForSession($session, $userId);

        $players = $session->users_sessionsgames;
        if (count($players) < 2) {
            $this->Flash->error('La partie n’a pas encore 2 joueurs.');
            return $this->redirect(['action' => 'play', $sessionGameId]);
        }

        if ((int)$session->filler->current_turn_user_id !== $userId) {
            $this->Flash->error('Ce n’est pas ton tour.');
            return $this->redirect(['action' => 'play', $sessionGameId]);
        }

        $player1 = $players[0];
        $player2 = $players[1];

        $isPlayer1 = ((int)$player1->user_id === $userId);
        $isPlayer2 = ((int)$player2->user_id === $userId);

        if (!$isPlayer1 && !$isPlayer2) {
            $this->Flash->error('Vous ne faites pas partie de cette session.');
            return $this->redirect(['action' => 'play', $sessionGameId]);
        }

        $grid = json_decode($session->filler->grid, true);
        if (!is_array($grid) || empty($grid)) {
            $this->Flash->error('Grille invalide.');
            return $this->redirect(['action' => 'play', $sessionGameId]);
        }

        $rows = count($grid);
        $cols = count($grid[0]);

        $player1Color = (int)$grid[0][0];
        $player2Color = (int)$grid[$rows - 1][$cols - 1];

        if ($isPlayer1) {
            if ($color === $player1Color) {
                $this->Flash->error('Tu as déjà cette couleur.');
                return $this->redirect(['action' => 'play', $sessionGameId]);
            }

            if ($color === $player2Color) {
                $this->Flash->error('Tu ne peux pas choisir la couleur actuelle du joueur 2.');
                return $this->redirect(['action' => 'play', $sessionGameId]);
            }

            $grid = $this->applyMove($grid, 1, $color);
            $nextTurnUserId = (int)$player2->user_id;
        } else {
            if ($color === $player2Color) {
                $this->Flash->error('Tu as déjà cette couleur.');
                return $this->redirect(['action' => 'play', $sessionGameId]);
            }

            if ($color === $player1Color) {
                $this->Flash->error('Tu peux pas choisir la couleur actuelle du joueur 1.');
                return $this->redirect(['action' => 'play', $sessionGameId]);
            }

            $grid = $this->applyMove($grid, 2, $color);
            $nextTurnUserId = (int)$player1->user_id;
        }

        $session->filler->grid = json_encode($grid);

        $remainingColors = $this->getRemainingColors($grid);

        if (count($remainingColors) <= 2) {
            $session->isfinish = true;
            $Sessionsgames->saveOrFail($session);

            $player1Cells = $this->getPlayerTerritory($grid, 1);
            $player2Cells = $this->getPlayerTerritory($grid, 2);

            $player1Score = count($player1Cells);
            $player2Score = count($player2Cells);

            $duration = $this->calculateDuration($session->created, new \DateTimeImmutable());

            $userSession1 = $UsersSessionsgames->find()
                ->where([
                    'user_id' => $player1->user_id,
                    'session_game_id' => $session->id,
                ])
                ->first();

            $userSession2 = $UsersSessionsgames->find()
                ->where([
                    'user_id' => $player2->user_id,
                    'session_game_id' => $session->id,
                ])
                ->first();

            if ($userSession1) {
                $userSession1->final_score = $player1Score;
                $userSession1->time_session = $duration;
                $userSession1->is_winner = $player1Score > $player2Score;
                $UsersSessionsgames->saveOrFail($userSession1);
            }

            if ($userSession2) {
                $userSession2->final_score = $player2Score;
                $userSession2->time_session = $duration;
                $userSession2->is_winner = $player2Score > $player1Score;
                $UsersSessionsgames->saveOrFail($userSession2);
            }

            $session->filler->current_turn_user_id = null;
            $Fillers->saveOrFail($session->filler);

            if ($player1Score > $player2Score) {
                $this->Flash->success('Partie terminée. Le joueur 1 gagne.');
            } elseif ($player2Score > $player1Score) {
                $this->Flash->success('Partie terminée. Le joueur 2 gagne.');
            } else {
                $this->Flash->success('Partie terminée. Égalité.');
            }

            return $this->redirect(['action' => 'play', $sessionGameId]);
        }

        $session->filler->current_turn_user_id = $nextTurnUserId;
        $Fillers->saveOrFail($session->filler);

        return $this->redirect(['action' => 'play', $sessionGameId]);
    }

    /**
     * Finds the Filler catalog record.
     *
     * @return \App\Model\Entity\Boardgame
     */
    private function findFillerBoardgame()
    {
        return $this->fetchTable('Boardgames')->find()
            ->where(['Boardgames.name' => 'Filler'])
            ->orderBy(['Boardgames.id' => 'ASC'])
            ->firstOrFail();
    }

    /**
     * Finds the current user's session or the oldest session with a free slot.
     *
     * @param int $boardgameId Boardgame catalog id.
     * @param int $userId Current user id.
     * @return \App\Model\Entity\Sessionsgame|null
     */
    private function findJoinableSession( $boardgameId, $userId): ?Sessionsgame
    {
        $sessions = $this->fetchTable('Sessionsgames')->find()
            ->where([
                'Sessionsgames.boardgames_id' => $boardgameId,
                'Sessionsgames.isfinish' => false,
            ])
            ->contain([
                'Fillers',
                'UsersSessionsgames' => function (SelectQuery $q) {
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

    /**
     * Loads a Filler session and its players.
     *
     * @param int $sessionGameId Session id.
     * @return \App\Model\Entity\Sessionsgame
     */
    private function loadFillerSession( $sessionGameId): Sessionsgame
    {
        $session = $this->fetchTable('Sessionsgames')->find()
            ->where(['Sessionsgames.id' => $sessionGameId])
            ->contain([
                'Boardgames',
                'Fillers',
                'UsersSessionsgames' => function (SelectQuery $q) {
                    return $q
                        ->contain(['Users'])
                        ->orderBy(['UsersSessionsgames.id' => 'ASC']);
                },
            ])
            ->first();

        if (!$session || ($session->boardgame->name ?? '') !== 'Filler') {
            throw new NotFoundException('Partie Filler introuvable.');
        }

        return $session;
    }

    /**
     * Attaches the current user to a session when needed.
     *
     * @param \App\Model\Entity\Sessionsgame $session Session entity.
     * @param int $userId Current user id.
     * @return void
     */
    private function attachCurrentUserToSession( $session,  $userId)
    {
        if ($this->isUserInSession($session->users_sessionsgames ?? [], $userId)) {
            return;
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

    /**
     * Ensures the Filler row and current turn are initialized.
     *
     * @param \App\Model\Entity\Sessionsgame $session Session entity.
     * @param int $fallbackUserId Fallback user id.
     * @return \App\Model\Entity\Filler
     */
    private function ensureFillerForSession( $session, $fallbackUserId)
    {
        $Fillers = $this->fetchTable('Fillers');
        $filler = $session->filler ?? null;
        $players = [];
        foreach ($session->users_sessionsgames ?? [] as $usersSession) {
            $players[] = $usersSession;
        }

        $player1 = $players[0] ?? null;
        $validTurnUserIds = [];

        foreach ($players as $usersSession) {
            if ($usersSession->user_id !== null) {
                $validTurnUserIds[] = (int)$usersSession->user_id;
            }
        }

        if ($filler === null) {
            $nbColonne = 8;
            $grid = $this->generateGrid($nbColonne, 4);
            $grid = $this->DifferentCornerColors($grid, 4);

            $currentTurnUserId = $player1 ? (int)$player1->user_id : (int)$fallbackUserId;

            $filler = $Fillers->newEntity([
                'nb_colonne' => $nbColonne,
                'session_game_id' => $session->id,
                'grid' => json_encode($grid),
            ]);
            $filler->current_turn_user_id = $currentTurnUserId;

            $Fillers->saveOrFail($filler);
            $session->filler = $filler;

            return $filler;
        }

        $currentTurnUserId = (int)($filler->current_turn_user_id ?? 0);
        if (!(bool)$session->isfinish && $player1 && !in_array($currentTurnUserId, $validTurnUserIds, true)) {
            $filler->current_turn_user_id = (int)$player1->user_id;
            $Fillers->saveOrFail($filler);
        }

        return $filler;
    }

    /**
     * Checks whether a user is already attached to a session.
     *
     * @param iterable $usersSessionsgames Join records.
     * @param int $userId User id.
     * @return bool
     */
    private function isUserInSession( $usersSessionsgames,  $userId)
    {
        $userId = (int)$userId;

        foreach ($usersSessionsgames as $usersSession) {
            if ((int)$usersSession->user_id === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the current authenticated user id.
     *
     * @return int
     */
    private function currentUserId()
    {
        if (is_object($this->currentUser) && method_exists($this->currentUser, 'getIdentifier')) {
            return (int)$this->currentUser->getIdentifier();
        }

        if (is_object($this->currentUser) && isset($this->currentUser->id)) {
            return (int)$this->currentUser->id;
        }

        if (is_array($this->currentUser) && isset($this->currentUser['id'])) {
            return (int)$this->currentUser['id'];
        }

        throw new ForbiddenException('Utilisateur introuvable.');
    }

    private function applyMove( $grid, $playerNumber, $newColor)
    {
        $territory = $this->getPlayerTerritory($grid, $playerNumber);

        foreach ($territory as [$x, $y]) {
            $grid[$x][$y] = $newColor;
        }

        $opponentCells = $this->getPlayerTerritory($grid, $playerNumber === 1 ? 2 : 1);
        $blocked = [];
        foreach ($opponentCells as [$x, $y]) {
            $blocked[$x . '-' . $y] = true;
        }

        [$startX, $startY] = $playerNumber === 1
            ? [0, 0]
            : [count($grid) - 1, count($grid[0]) - 1];

        $queue = [[$startX, $startY]];
        $visited = [];

        while (!empty($queue)) {
            [$x, $y] = array_shift($queue);
            $key = $x . '-' . $y;

            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            if (!$this->isInsideGrid($grid, $x, $y)) {
                continue;
            }

            if (isset($blocked[$key])) {
                continue;
            }

            if ($grid[$x][$y] !== $newColor) {
                continue;
            }

            $neighbors = $this->getNeighbors($x, $y);
            foreach ($neighbors as [$nx, $ny]) {
                if ($this->isInsideGrid($grid, $nx, $ny)) {
                    $queue[] = [$nx, $ny];
                }
            }
        }

        return $grid;
    }

    private function getPlayerTerritory( $grid, $playerNumber)
    {
        $rows = count($grid);
        $cols = count($grid[0]);

        if ($playerNumber === 1) {
            $startX = 0;
            $startY = 0;
        } else {
            $startX = $rows - 1;
            $startY = $cols - 1;
        }

        $startColor = $grid[$startX][$startY];
        $queue = [[$startX, $startY]];
        $visited = [];
        $territory = [];

        while (!empty($queue)) {
            [$x, $y] = array_shift($queue);
            $key = $x . '-' . $y;

            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            if (!$this->isInsideGrid($grid, $x, $y)) {
                continue;
            }

            if ($grid[$x][$y] !== $startColor) {
                continue;
            }

            $territory[] = [$x, $y];

            $neighbors = $this->getNeighbors($x, $y);
            foreach ($neighbors as [$nx, $ny]) {
                if ($this->isInsideGrid($grid, $nx, $ny)) {
                    $queue[] = [$nx, $ny];
                }
            }
        }

        return $territory;
    }

    private function getRemainingColors( $grid)
    {
        $colors = [];

        foreach ($grid as $row) {
            foreach ($row as $cell) {
                $colors[$cell] = $cell;
            }
        }

        sort($colors);

        return array_values($colors);
    }

    private function calculateDuration($start, \DateTimeImmutable $end)
    {
        $startTs = $start instanceof \DateTimeInterface
            ? $start->getTimestamp()
            : strtotime($start);

        $seconds = max(0, $end->getTimestamp() - $startTs);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    private function getNeighbors( $x,  $y)
    {
        return [
            [$x - 1, $y],
            [$x + 1, $y],
            [$x, $y - 1],
            [$x, $y + 1],
        ];
    }

    private function isInsideGrid( $grid, $x, $y)
    {
        $rows = count($grid);
        $cols = count($grid[0]);

        return $x >= 0 && $y >= 0 && $x < $rows && $y < $cols;
    }

    private function generateGrid( $size = 8,  $nbColors = 4)
    {
        $grid = [];

        for ($i = 0; $i < $size; $i++) {
            $row = [];

            for ($j = 0; $j < $size; $j++) {
                $row[] = random_int(1, $nbColors);
            }

            $grid[] = $row;
        }

        return $grid;
    }

    private function DifferentCornerColors( $grid, $nbColors = 4)
    {
        $rows = count($grid);
        $cols = count($grid[0]);

        while ($grid[0][0] === $grid[$rows - 1][$cols - 1]) {
            $newColor = random_int(1, $nbColors);

            if ($newColor !== $grid[0][0]) {
                $grid[$rows - 1][$cols - 1] = $newColor;
            }
        }

        return $grid;
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
