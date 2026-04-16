<?php
$this->assign('title', 'Games');
$gameCount = is_countable($boardgames) ? count($boardgames) : 0;
$badgeLabel = $gameCount . ' Jeux' . ($gameCount === 1 ? '' : 's') . ' prêts à mettre ton esprit à l’épreuve';
?>

<section class="boardgames-page">
    <div class="boardgames-page__inner">
        <header class="boardgames-hero">
            <span class="boardgames-badge"><?= h($badgeLabel) ?></span>
            <h1 class="boardgames-title">
                Joue, Rivalise, <span>Domine</span>
            </h1>
            <p class="boardgames-subtitle">
                Défie ton esprit avec des puzzles classiques, monte dans le leaderboard et montre que tu es le plus malin.
            </p>
        </header>

        <div class="boardgames-grid">
            <?php foreach ($boardgames as $boardgame): ?>
                <?php
                $gameUrl = ['controller' => 'Boardgames', 'action' => 'infos', $boardgame->id];
                $imageUrl = $this->Url->build('/img/' . ltrim((string)$boardgame->picture, '/'));
                ?>
                <a class="boardgame-card" href="<?= $this->Url->build($gameUrl) ?>">
                    <span class="boardgame-card-media" style="background-image: linear-gradient(180deg, rgba(10, 12, 18, 0.02) 0%, rgba(10, 12, 18, 0.2) 42%, rgba(10, 12, 18, 0.82) 100%), url('<?= h($imageUrl) ?>')"></span>
                    <span class="boardgame-card-body">
                        <span class="boardgame-card-title-row">
                            <span class="boardgame-card-title"><?= h($boardgame->name) ?></span>
                            <span class="boardgame-card-arrow" aria-hidden="true">-></span>
                        </span>
                        <span class="boardgame-card-text"><?= h($boardgame->description) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
