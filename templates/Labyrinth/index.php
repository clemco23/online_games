<?php
$this->assign('title', 'Labyrinth');
?>

<section class="labyrinth-page">
    <header class="labyrinth-header">
        <span class="labyrinth-eyebrow">Labyrinth</span>
        <h1>Atteins le tresor avant ton rival</h1>
        <p>Une course tactique ou chaque deplacement coute un point d action.</p>
        <?= $this->Html->link(
            'Lancer ou rejoindre',
            ['action' => 'new', $boardgame->id],
            ['class' => 'button labyrinth-primary-action']
        ) ?>
    </header>

    <section class="labyrinth-panel">
        <h2>Mes parties</h2>
        <?php if ($mySessions->isEmpty()): ?>
            <p class="labyrinth-muted">Tu n'as aucune partie Labyrinth pour le moment.</p>
        <?php else: ?>
            <div class="labyrinth-session-list">
                <?php foreach ($mySessions as $session): ?>
                    <?php
                    $isFinished = $session->isfinish;
                    $playerCount = count($session->users_sessionsgames);
                    ?>
                    <article class="labyrinth-session-card">
                        <div>
                            <strong>Session <?= h($session->code) ?></strong>
                            <span><?= h($playerCount) ?>/2 joueurs</span>
                            <span class="labyrinth-session-status <?= $isFinished ? 'is-finished' : 'is-active' ?>">
                                <?= $isFinished ? 'Terminee' : 'En cours' ?>
                            </span>
                        </div>
                        <?= $this->Html->link($isFinished ? 'Voir' : 'Reprendre', ['action' => 'play', $session->id], [
                            'class' => 'button labyrinth-session-action',
                        ]) ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="labyrinth-panel">
        <h2>Parties disponibles</h2>
        <?php if (empty($openSessions)): ?>
            <p class="labyrinth-muted">Aucune partie ouverte a rejoindre pour le moment.</p>
        <?php else: ?>
            <div class="labyrinth-session-list">
                <?php foreach ($openSessions as $session): ?>
                    <article class="labyrinth-session-card">
                        <div>
                            <strong>Session <?= h($session->code) ?></strong>
                            <span><?= h(count($session->users_sessionsgames)) ?>/2 joueurs</span>
                            <span class="labyrinth-session-status is-waiting">En attente</span>
                        </div>
                        <?= $this->Html->link('Rejoindre', ['action' => 'join', $session->id], [
                            'class' => 'button labyrinth-session-action',
                        ]) ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
