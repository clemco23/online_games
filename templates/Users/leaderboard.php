<?php
$this->assign('title', 'Leaderboard');
?>

<section class="page-shell">
    <div class="page-header">
        <span class="page-eyebrow">Community ranking</span>
        <h1>Leaderboard</h1>
        <p>Follow the players who win the most and keep the strongest overall momentum.</p>
    </div>

    <div class="leaderboard-card">
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <th>Wins</th>
                    <th>Games</th>
                    <th>Total score</th>
                    <th>Best score</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($leaders->count() === 0): ?>
                    <tr class="leaderboard-empty">
                        <td colspan="6">No players have finished a game yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaders as $index => $leader): ?>
                        <?php
                        $wins = (int)($leader->get('wins') ?? 0);
                        $gamesPlayed = (int)($leader->get('games_played') ?? 0);
                        $totalScore = (int)($leader->get('total_score') ?? 0);
                        $bestScore = $leader->get('best_score');
                        $initial = strtoupper(substr((string)$leader->username, 0, 1));
                        ?>
                        <tr>
                            <td class="leaderboard-rank"><?= $index + 1 ?></td>
                            <td>
                                <div class="leaderboard-player">
                                    <span class="leaderboard-player-avatar"><?= h($initial) ?></span>
                                    <span class="leaderboard-player-name"><?= h($leader->username) ?></span>
                                </div>
                            </td>
                            <td><?= $wins ?></td>
                            <td><?= $gamesPlayed ?></td>
                            <td><?= $totalScore ?></td>
                            <td><?= $bestScore !== null ? (int)$bestScore : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
