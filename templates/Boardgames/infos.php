<?php
$this->assign('title', (string)$boardgame->name);

$gameController = match ($boardgame->name) {
    'Mastermind' => 'Masterminds',
    'Filler' => 'Fillers',
    'Labyrinth', 'Labyrinthe' => 'Labyrinth',
    default => null,
};
$gameUrl = in_array($gameController, ['Fillers', 'Labyrinth', 'Masterminds'], true)
    ? ['controller' => $gameController, 'action' => 'index']
    : ['controller' => $gameController, 'action' => 'start', $boardgame->id];
?>

<section class="boardgame-instructions-page">

<p><?= $this->Html->link('Retour à la liste', ['action' => 'index']) ?></p>

<div class="boardgame-instructions-hero">
    <div class="boardgame-instructions-copy">
        <span class="boardgame-instructions-eyebrow">Regles du jeu</span>
        <h1 class="boardgame-instructions-title"><?= h($boardgame->name) ?></h1>
        <p class="boardgame-instructions-description"><?= h($boardgame->description) ?></p>
    </div>

    <figure class="boardgame-instructions-media">
        <?= $this->Html->image($boardgame->picture, [
            'alt' => $boardgame->name,
            'class' => 'boardgame-instructions-image',
        ]) ?>
    </figure>
</div>

<section class="boardgame-instructions-panel">
<h2>Comment jouer</h2>

<ol class="boardgame-instructions-list">
    <?php foreach ($boardgame->boardgame_instructions as $instruction) : ?>
        <li><?= h($instruction->content) ?></li>
    <?php endforeach; ?>
</ol>
</section>



<?= $this->Html->link(
    'Jouer à ' . $boardgame->name,
    $gameUrl,
    ['class' => 'button boardgame-instructions-button'],
) ?>
</section>
    
