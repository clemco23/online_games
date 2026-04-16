<?php
$this->assign('title', 'Filler');
?>

<section class="filler-page">
    <header class="filler-header">
        <span class="filler-eyebrow">Filler</span>
        <h1>Capture plus de cases que ton adversaire</h1>
        <p>Choisis une couleur, etends ton territoire, puis bloque la progression adverse.</p>
        <?= $this->Html->link(
            'Lancer ou rejoindre',
            ['action' => 'new', $boardgame->id],
            ['class' => 'button filler-primary-action'],
        ) ?>
    </header>

    <section class="filler-panel">
        <h2>Mes parties</h2>
        <?php if ($mySessions->isEmpty()) : ?>
            <p class="filler-muted">Tu n'as aucune partie Filler pour le moment.</p>
        <?php else : ?>
            <div class="filler-session-list">
                <?php foreach ($mySessions as $session) : ?>
                    <?php
                    $isFinished = $session->isfinish;
                    $playerCount = count($session->users_sessionsgames);
                    ?>
                    <article class="filler-session-card">
                        <div>
                            <strong>Session <?= h($session->code) ?></strong>
                            <span><?= h($playerCount) ?>/2 joueurs</span>
                            <span class="filler-session-status <?= $isFinished ? 'is-finished' : 'is-active' ?>">
                                <?= $isFinished ? 'Terminee' : 'En cours' ?>
                            </span>
                        </div>
                        <?= $this->Html->link($isFinished ? 'Voir' : 'Reprendre', ['action' => 'play', $session->id], [
                            'class' => 'button filler-session-action',
                        ]) ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="filler-panel">
        <h2>Parties disponibles</h2>
        <?php if (empty($openSessions)) : ?>
            <p class="filler-muted">Aucune partie ouverte a rejoindre pour le moment.</p>
        <?php else : ?>
            <div class="filler-session-list">
                <?php foreach ($openSessions as $session) : ?>
                    <article class="filler-session-card">
                        <div>
                            <strong>Session <?= h($session->code) ?></strong>
                            <span><?= h(count($session->users_sessionsgames)) ?>/2 joueurs</span>
                            <span class="filler-session-status is-waiting">En attente</span>
                        </div>
                        <?= $this->Html->link('Rejoindre', ['action' => 'join', $session->id], [
                            'class' => 'button filler-session-action',
                        ]) ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
