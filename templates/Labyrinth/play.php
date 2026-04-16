<?php
$this->assign('title', 'Labyrinth');
$this->Html->script('auto-refresh', ['block' => true]);

$playersByPosition = [];
foreach ($state['players'] as $player) {
    if ($player['user_id'] === null) {
        continue;
    }

    $playersByPosition[$player['x'] . '-' . $player['y']] = $player;
}

$currentPlayer = $state['current_player'];
$status = $state['status'];
$availableMoves = $state['available_moves'] ?? [];
$apText = $currentPlayer ? $currentPlayer['ap'] . '/' . $status['max_ap'] : '--';
$message = 'Choisis une direction pour avancer vers le tresor.';

if ($status['finished']) {
    $message = $status['winner_user_id'] === ($currentPlayer['user_id'] ?? null)
        ? 'Victoire, tu as atteint le tresor.'
        : 'Partie terminee. Le tresor a deja ete trouve.';
} elseif ($status['waiting']) {
    $message = 'En attente du second joueur.';
} elseif ($currentPlayer && $currentPlayer['ap'] <= 0) {
    $message = $currentPlayer['next_ap_in']
        ? 'Plus de points d action. Prochain +5 dans ' . $currentPlayer['next_ap_in'] . 's.'
        : 'Plus de points d action. Ils remontent toutes les minutes.';
}

$directionLabels = [
    'up' => 'Haut',
    'left' => 'Gauche',
    'right' => 'Droite',
    'down' => 'Bas',
];
$moveUrl = ['controller' => 'Labyrinth', 'action' => 'move', $session->id];
?>

<section class="labyrinth-page labyrinth-game" data-auto-refresh data-refresh-interval="3000">
    <header class="labyrinth-header">
        <span class="labyrinth-eyebrow">Session <?= h($session->code) ?></span>
        <h1>Labyrinth</h1>
        <p><?= h($message) ?></p>
    </header>

    <div class="labyrinth-hud">
        <span>Points d action: <strong><?= h($apText) ?></strong></span>
        <span>Objectif: atteindre le tresor</span>
    </div>

    <div class="labyrinth-board-shell">
        <div
            class="labyrinth-board"
            aria-label="Plateau Labyrinth"
            style="--labyrinth-columns: <?= $state['map']['width'] ?>"
        >
            <?php foreach ($state['map']['tiles'] as $y => $row): ?>
                <?php foreach ($row as $x => $tile): ?>
                    <?php
                    $key = $x . '-' . $y;
                    $player = $playersByPosition[$key] ?? null;
                    $classes = [
                        'labyrinth-cell',
                        'labyrinth-cell--' . ($tile === '#' ? 'wall' : 'path'),
                    ];
                    $content = '';

                    if ($tile === 'T') {
                        $classes[] = 'labyrinth-cell--treasure';
                        $content = 'T';
                    }

                    if ($player) {
                        $classes[] = 'labyrinth-cell--player-' . $player['slot'];
                        $content = 'P' . $player['slot'];
                    }
                    ?>
                    <span
                        class="<?= h(implode(' ', $classes)) ?>"
                        data-x="<?= $x ?>"
                        data-y="<?= $y ?>"
                    ><?= h($content) ?></span>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="labyrinth-controls" aria-label="Deplacements">
        <?php foreach ($directionLabels as $direction => $label): ?>
            <?= $this->Form->create(null, ['url' => $moveUrl, 'class' => 'labyrinth-control-form']) ?>
            <?= $this->Form->hidden('direction', ['value' => $direction]) ?>
            <?= $this->Form->button($label, [
                'type' => 'submit',
                'disabled' => empty($availableMoves[$direction]),
            ]) ?>
            <?= $this->Form->end() ?>
        <?php endforeach; ?>
    </div>
</section>
