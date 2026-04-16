<?php

namespace App\Controller;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;

class MastermindsController extends AppController
{
    public function beforeFilter($event)
    {
        $this->requiresAuth = true;

        return parent::beforeFilter($event);
    }

    private const MAX_ATTEMPTS = 10;

    /**
     * Affiche les parties Mastermind du joueur connecte.
     *
     * @return void
     */
    public function index()
    {
        $boardgame = $this->findMastermindBoardgame();
        $userId = $this->currentUserId();
        $Sessionsgames = $this->fetchTable('Sessionsgames');

        $mySessions = $Sessionsgames->find()
            ->matching('UsersSessionsgames', function ($q) use ($userId) {
                return $q->where(['UsersSessionsgames.user_id' => $userId]);
            })
            ->where(['Sessionsgames.boardgames_id' => $boardgame->id])
            ->contain([
                'Masterminds',
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

        $sessionStates = [];
        foreach ($mySessions as $session) {
            $sessionStates[$session->id] = $this->buildAttemptState($session);
        }

        $maxAttempts = self::MAX_ATTEMPTS;

        $this->set(compact('boardgame', 'mySessions', 'sessionStates', 'maxAttempts'));
    }

    /**
     * Lance une nouvelle partie
     */
    public function start($boardgameId = null)
    {
        $userId = $this->currentUserId();
        if ($boardgameId === null) {
            $boardgameId = $this->findMastermindBoardgame()->id;
        }

        $possibleColors = ['red', 'blue', 'green', 'yellow', 'orange', 'purple', 'black', 'white'];
        $secretCode = [];
        for ($i = 0; $i < 4; $i++) {
            $secretCode[] = $possibleColors[array_rand($possibleColors)];
        }

        $data = [
            'boardgames_id' => $boardgameId,
            'isfinish' => false,
            'code' => strtoupper(substr(md5(microtime()), 0, 6)),
            'mastermind' => [
                'steps' => json_encode($secretCode),
            ],
            'users_sessionsgames' => [
                [
                    'user_id' => $userId,
                    'final_score' => 0,
                    'time_session' => '00:00:00',
                    'is_winner' => false,
                ],
            ],
        ];

        $SessionsgamesTable = $this->fetchTable('Sessionsgames');

        $sessionGame = $SessionsgamesTable->newEntity($data, [
            'associated' => ['Masterminds', 'UsersSessionsgames'],
        ]);

        if ($SessionsgamesTable->save($sessionGame)) {
            $this->request->getSession()->write("Game.started_at.$sessionGame->id", time());
            $this->Flash->success('La partie de Mastermind a commencé !');

            return $this->redirect(['action' => 'play', $sessionGame->id]);
        }

        $this->Flash->error('Erreur lors de la création de la partie. Vérifiez les validations.');

        return $this->redirect($this->referer());
    }

    /**
     * Affiche le plateau de jeu
     */
    public function play($id)
    {
        $sessionGame = $this->loadMastermindSession($id);
        if (!$this->isUserInSession($sessionGame->users_sessionsgames, $this->currentUserId())) {
            $this->Flash->error('Vous ne faites pas partie de cette partie.');

            return $this->redirect(['action' => 'index']);
        }

        $history = $this->request->getSession()->read("Game.history.$id") ?? [];
        $attemptState = $this->buildAttemptState($sessionGame);
        $attemptsMade = $attemptState['attemptsMade'];
        $attemptsLeft = $attemptState['attemptsLeft'];
        $isWon = $attemptState['isWon'];
        $isLost = $attemptState['isLost'];
        $maxAttempts = self::MAX_ATTEMPTS;

        $this->set(compact(
            'sessionGame',
            'history',
            'attemptsMade',
            'attemptsLeft',
            'maxAttempts',
            'isWon',
            'isLost',
        ));
    }

    /**
     * Vérifié la proposition de l'utilisateur
     */
    public function check($sessionId)
    {
        $this->request->allowMethod(['post']);

        $sessionsTable = $this->fetchTable('Sessionsgames');
        $sessionGame = $this->loadMastermindSession($sessionId);
        if (!$this->isUserInSession($sessionGame->users_sessionsgames, $this->currentUserId())) {
            $this->Flash->error('Vous ne faites pas partie de cette partie.');

            return $this->redirect(['action' => 'index']);
        }

        $session = $this->request->getSession();

        if ($sessionGame->isfinish) {
            $this->Flash->warning('Cette partie est deja terminee.');

            return $this->redirect(['action' => 'play', $sessionId]);
        }

        $secretCode = json_decode($sessionGame->mastermind->steps, true);
        $history = $session->read("Game.history.$sessionId") ?? [];

        if (count($history) >= self::MAX_ATTEMPTS) {
            $sessionGame->set('isfinish', true);
            $sessionsTable->save($sessionGame);
            $this->updateGameStats($sessionGame->id, count($history), false, $sessionGame->created?->getTimestamp());
            $this->Flash->error('Perdu ! 10 tentives et toujours pas trouvé le code. La honte !');

            return $this->redirect(['action' => 'play', $sessionId]);
        }

        $guess = $this->request->getData('guess');

        if (!is_array($guess) || count($guess) !== 4) {
            $this->Flash->error('Votre proposition doit contenir 4 couleurs.');

            return $this->redirect(['action' => 'play', $sessionId]);
        }

        $guess = array_values($guess);

        $wellPlaced = 0;
        $wrongPlaced = 0;

        $tempSecret = $secretCode;
        $tempGuess = $guess;

        foreach ($tempGuess as $i => $color) {
            if ($color === $tempSecret[$i]) {
                $wellPlaced++;
                $tempSecret[$i] = null;
                $tempGuess[$i] = null;
            }
        }

        foreach ($tempGuess as $color) {
            if ($color !== null && in_array($color, $tempSecret, true)) {
                $wrongPlaced++;
                $key = array_search($color, $tempSecret, true);
                $tempSecret[$key] = null;
            }
        }

        array_unshift($history, [
            'guess' => $guess,
            'well_placed' => $wellPlaced,
            'wrong_placed' => $wrongPlaced,
            'time' => date('H:i:s'),
        ]);

        $session->write("Game.history.$sessionId", $history);

        $attemptCount = count($history);

        if ($wellPlaced === 4) {
            $sessionGame->set('isfinish', true);
            $sessionsTable->save($sessionGame);
            $this->updateGameStats($sessionGame->id, $attemptCount, true, $sessionGame->created?->getTimestamp());
            $this->Flash->success('Félicitations ! Vous avez craqué le code !');
        } elseif ($attemptCount >= self::MAX_ATTEMPTS) {
            $sessionGame->set('isfinish', true);
            $sessionsTable->save($sessionGame);
            $this->updateGameStats($sessionGame->id, $attemptCount, false, $sessionGame->created?->getTimestamp());
            $this->Flash->error('Perdu ! Vous avez utilise vos 10 tentatives.');
        } else {
            $attemptsLeft = self::MAX_ATTEMPTS - $attemptCount;
            $this->Flash->info("Résultat : $wellPlaced bien placés (noir), $wrongPlaced mal placés (blanc). Il reste $attemptsLeft tentative(s).");
        }

        return $this->redirect(['action' => 'play', $sessionId]);
    }

    private function findMastermindBoardgame()
    {
        return $this->fetchTable('Boardgames')->find()
            ->where(['Boardgames.name' => 'Mastermind'])
            ->orderBy(['Boardgames.id' => 'ASC'])
            ->firstOrFail();
    }

    private function loadMastermindSession($sessionGameId)
    {
        $session = $this->fetchTable('Sessionsgames')->find()
            ->where(['Sessionsgames.id' => $sessionGameId])
            ->contain([
                'Boardgames',
                'Masterminds',
                'UsersSessionsgames' => function ($q) {
                    return $q
                        ->contain(['Users'])
                        ->orderBy(['UsersSessionsgames.id' => 'ASC']);
                },
            ])
            ->first();

        if (!$session || !$session->mastermind) {
            throw new NotFoundException('Partie Mastermind introuvable.');
        }

        return $session;
    }

    private function buildAttemptState($sessionGame)
    {
        $history = $this->request->getSession()->read('Game.history.' . $sessionGame->id) ?? [];
        $userSession = $this->findCurrentUserSession($sessionGame);
        $attemptsMade = count($history);

        if ($attemptsMade === 0 && $sessionGame->isfinish && $userSession !== null) {
            $attemptsMade = $userSession->final_score;
        }

        $isWon = $this->hasWinningAttempt($history)
            || ($sessionGame->isfinish && $userSession !== null && $userSession->is_winner);

        return [
            'attemptsMade' => $attemptsMade,
            'attemptsLeft' => max(0, self::MAX_ATTEMPTS - $attemptsMade),
            'isWon' => $isWon,
            'isLost' => $sessionGame->isfinish && !$isWon,
        ];
    }

    private function findCurrentUserSession($sessionGame)
    {
        $userId = $this->currentUserId();
        foreach ($sessionGame->users_sessionsgames as $usersSession) {
            if ($usersSession->user_id === $userId) {
                return $usersSession;
            }
        }

        return null;
    }

    private function isUserInSession($usersSessionsgames, $userId)
    {
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

    private function updateGameStats($sessionGameId, $attemptCount, $isWinner, $fallbackStartedAt = null)
    {
        $userId = $this->currentUserId();
        $usersSessionsgamesTable = $this->fetchTable('UsersSessionsgames');
        $usersSessionsgame = $usersSessionsgamesTable->find()
            ->where([
                'user_id' => $userId,
                'session_game_id' => $sessionGameId,
            ])
            ->first();

        $startedAt = $this->request->getSession()->read("Game.started_at.$sessionGameId");
        if (!is_int($startedAt)) {
            $startedAt = $fallbackStartedAt;
        }

        $elapsedSeconds = 0;
        if (is_int($startedAt)) {
            $elapsedSeconds = max(0, time() - $startedAt);
        }

        $statsData = [
            'final_score' => $attemptCount,
            'time_session' => gmdate('H:i:s', $elapsedSeconds),
            'is_winner' => $isWinner,
        ];

        if ($usersSessionsgame === null) {
            $usersSessionsgame = $usersSessionsgamesTable->newEntity([
                'user_id' => $userId,
                'session_game_id' => $sessionGameId,
            ] + $statsData);
        } else {
            $usersSessionsgame = $usersSessionsgamesTable->patchEntity($usersSessionsgame, $statsData);
        }

        $usersSessionsgamesTable->saveOrFail($usersSessionsgame);
    }

    private function hasWinningAttempt($history)
    {
        foreach ($history as $attempt) {
            if (($attempt['well_placed'] ?? 0) === 4) {
                return true;
            }
        }

        return false;
    }
}
