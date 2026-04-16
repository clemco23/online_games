<?php
$this->assign('title', 'Filler');
$this->Html->script('auto-refresh', ['block' => true]);

$player1Map = [];
foreach ($player1Cells as [$x, $y]) {
    $player1Map[$x . '-' . $y] = true;
}

$player2Map = [];
foreach ($player2Cells as [$x, $y]) {
    $player2Map[$x . '-' . $y] = true;
}

function fillerColorClass(int $value): string {
    return match ($value) {
        1 => 'color-1',
        2 => 'color-2',
        3 => 'color-3',
        4 => 'color-4',
        default => '',
    };
}

function fillerColorLabel(int $value): string {
    return match ($value) {
        1 => 'Rouge',
        2 => 'Bleu',
        3 => 'Vert',
        4 => 'Jaune',
        default => 'Inconnue',
    };
}
?>

<div class="filler-wrapper" data-auto-refresh data-refresh-interval="3000">
    <h1>Filler</h1>

    <div class="filler-infos">
        <p><strong>Session :</strong> <?= h($session->code) ?></p>
        <p><strong>Jeu :</strong> <?= h($session->boardgame->name ?? 'Filler') ?></p>
        <p><strong>Nombre de colonnes :</strong> <?= h($filler->nb_colonne) ?></p>

        <p>
            <span class="player-badge">
                Joueur 1 :
                <?= $player1 && isset($player1->user->username) ? h($player1->user->username) : 'En attente...' ?>
            </span>
            <span class="player-badge">
                Joueur 2 :
                <?= $player2 && isset($player2->user->username) ? h($player2->user->username) : 'En attente...' ?>
            </span>
        </p>

        <?php if ($currentPlayerNumber !== null): ?>
            <p><strong>Tu es le joueur <?= h($currentPlayerNumber) ?></strong></p>
        <?php endif; ?>

        <?php if (!$isWaitingOpponent && !$session->isfinish): ?>
            <div class="status-box">
                <?php if ($isMyTurn): ?>
                    <strong>C’est ton tour.</strong>
                <?php else: ?>
                    <strong>Ce n’est pas ton tour.</strong>
                    La page se recharge automatiquement apres le coup adverse.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($session->isfinish): ?>
            <div class="status-box">
                <strong>Partie terminée.</strong><br>
                <?= h((string)$winnerMessage) ?><br>
                Score joueur 1 : <?= h((string)$player1Score) ?><br>
                Score joueur 2 : <?= h((string)$player2Score) ?><br>
                Couleurs restantes : <?= h(implode(', ', $remainingColors)) ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isWaitingOpponent): ?>
        <div class="waiting-box">
            <h3>En attente d'un adversaire...</h3>
            <p>
                La page se recharge automatiquement.
            </p>
        </div>
    <?php else: ?>
        <div class="legend">
            <span><strong>Contour blanc</strong> : territoire joueur 1</span>
            <span><strong>Contour sombre</strong> : territoire joueur 2</span>
        </div>

        <p>
            <strong>Score J1 :</strong> <?= h((string)$player1Score) ?>
            &nbsp; | &nbsp;
            <strong>Score J2 :</strong> <?= h((string)$player2Score) ?>
            &nbsp; | &nbsp;
            <strong>Couleurs restantes :</strong> <?= h(implode(', ', $remainingColors)) ?>
        </p>

        <h3>Grille</h3>
        <table class="filler-grid">
            <tbody>
            <?php foreach ($grid as $rowIndex => $row): ?>
                <tr>
                    <?php foreach ($row as $colIndex => $cell): ?>
                        <?php
                        $key = $rowIndex . '-' . $colIndex;
                        $classes = [fillerColorClass((int)$cell)];

                        if (isset($player1Map[$key])) {
                            $classes[] = 'owned-p1';
                        }
                        if (isset($player2Map[$key])) {
                            $classes[] = 'owned-p2';
                        }
                        ?>
                        <td class="<?= h(implode(' ', $classes)) ?>">
                            <?php
                            if ($rowIndex === 0 && $colIndex === 0) {
                                echo 'J1';
                            } elseif ($rowIndex === count($grid) - 1 && $colIndex === count($row) - 1) {
                                echo 'J2';
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$session->isfinish): ?>
            <h3>Choix des couleurs</h3>
            <div class="actions">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <?php
                    $btnClass = match ($i) {
                        1 => 'btn-red',
                        2 => 'btn-blue',
                        3 => 'btn-green',
                        4 => 'btn-yellow',
                    };
                    ?>

                    <?php if ($isMyTurn && in_array($i, $allowedColors, true)): ?>
                        <?= $this->Html->link(
                            fillerColorLabel($i),
                            ['action' => 'chooseColor', $session->id, $i],
                            ['class' => $btnClass]
                        ) ?>
                    <?php else: ?>
                        <span class="<?= h($btnClass) ?> disabled"><?= h(fillerColorLabel($i)) ?></span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <p>
            La page se recharge automatiquement toutes les 3 secondes.
        </p>
    <?php endif; ?>
</div>
