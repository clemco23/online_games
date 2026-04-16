<?php
$this->assign('title', 'Mastermind');
?>

<section class="mastermind-page">
    <header class="mastermind-header">
        <span class="mastermind-up">Mastermind</span>
        <h1>Trouve le code en 10 tentatives</h1>
        <p>Chaque partie est solo. Tu peux reprendre une session en cours ou en lancer une nouvelle.</p>
        <?= $this->Html->link(
            'Nouvelle partie',
            ['action' => 'start', $boardgame->id],
            ['class' => 'button mastermind-primary-action'],
        ) ?>
    </header>

    <section class="mastermind-panel">
        <h2>Mes parties</h2>
        <?php if ($mySessions->isEmpty()) : ?>
            <p class="mastermind-muted">Tu n'as aucune partie Mastermind pour le moment.</p>
        <?php else : ?>
            <div class="mastermind-session-list">
                <?php foreach ($mySessions as $session) : ?>
                    <?php
                    $isFinished = $session->isfinish;
                    $state = $sessionStates[$session->id] ?? [
                        'attemptsMade' => 0,
                        'attemptsLeft' => $maxAttempts,
                        'isWon' => false,
                        'isLost' => false,
                    ];
                    ?>
                    <article class="mastermind-session-card">
                        <div>
                            <strong>Session <?= h($session->code) ?></strong>
                            <span>
                                <?= h($state['attemptsMade']) ?> / <?= h($maxAttempts) ?> tentatives
                            </span>
                            <span class="mastermind-session-status <?= $isFinished ? 'is-finished' : 'is-active' ?>">
                                <?php if (!$isFinished) : ?>
                                    En cours
                                <?php elseif ($state['isWon']) : ?>
                                    Gagnee
                                <?php elseif ($state['isLost']) : ?>
                                    Perdue
                                <?php else : ?>
                                    Terminee
                                <?php endif; ?>
                            </span>
                        </div>
                        <?= $this->Html->link($isFinished ? 'Voir' : 'Reprendre', ['action' => 'play', $session->id], [
                            'class' => 'button mastermind-session-action',
                        ]) ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
