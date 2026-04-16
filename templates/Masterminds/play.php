<?php
$this->assign('title', 'Mastermind');
$attemptsMade = $attemptsMade ?? count($history);
$maxAttempts = $maxAttempts ?? 10;
$attemptsLeft = $attemptsLeft ?? max(0, $maxAttempts - $attemptsMade);
$isWon = $isWon ?? false;
$isLost = $isLost ?? false;
$isFinished = (bool)$sessionGame->isfinish;
?>

<section class="mastermind-page">
    <header class="mastermind-header">
        <span class="mastermind-up">Mastermind</span>
        <h1>Partie en cours</h1>
    </header>

    <div class="mastermind-game">
        <p class="mastermind-attempts">
            Tentatives : <?= h($attemptsMade) ?> / <?= h($maxAttempts) ?>
            <?php if (!$isFinished) : ?>
                - il te reste <?= h($attemptsLeft) ?> tentative(s).
            <?php endif; ?>
        </p>

        <?php if ($isFinished) : ?>
            <p class="mastermind-result">
                <?php if ($isWon) : ?>
                    Bravo, tu as trouvé le code.
                <?php elseif ($isLost) : ?>
                    Perdu, tu as utilisé tes 10 tentatives.
                <?php else : ?>
                    Partie terminée.
                <?php endif; ?>
            </p>
        <?php else : ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'check', $sessionGame->id]]) ?>
            <fieldset>
                <legend>Propose une combinaison :</legend>
                <div class="mastermind-guess-fields">
                    <?php for ($i = 0; $i < 4; $i++) : ?>
                        <?= $this->Form->control("guess.$i", [
                            'type' => 'select',
                            'options' => [
                                'red' => 'Rouge',
                                'blue' => 'Bleu',
                                'green' => 'Vert',
                                'yellow' => 'Jaune',
                                'orange' => 'Orange',
                                'purple' => 'Violet',
                                'black' => 'Noir',
                                'white' => 'Blanc',
                            ],
                            'label' => 'Position ' . ($i + 1),
                        ]) ?>
                    <?php endfor; ?>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Valider ma tentative'), ['class' => 'mastermind-submit']) ?>
            <?= $this->Form->end() ?>
        <?php endif; ?>
    </div>

    <section class="mastermind-history">
    <h2 class="mastermind-history-title">Historique des tentatives</h2>
    <div class="mastermind-history-table-wrap">
            <table class="mastermind-history-table">
    <thead>
        <tr>
            <th>Combinaison</th>
            <th>Bien placés</th>
            <th>Mal placés</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($history)) : ?>
            <?php foreach ($history as $attempt) : ?>
                <tr>
                    <td>
                        <span class="mastermind-pegs">
                        <?php foreach ($attempt['guess'] as $color) : ?>
                            <span
                                class="mastermind-peg mastermind-peg--<?= h($color) ?>"
                                aria-label="<?= h($color) ?>"
                            ></span>
                        <?php endforeach; ?>
                        </span>
                    </td>
                    <td>
                        <span class="mastermind-badge mastermind-badge--well">
                            <?= h($attempt['well_placed']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="mastermind-badge mastermind-badge--wrong">
                            <?= h($attempt['wrong_placed']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="3" class="mastermind-empty">Aucune tentative pour le moment. Bonne chance !</td>
            </tr>
        <?php endif; ?>
    </tbody>
            </table>
        </div>
    </section>
</section>
