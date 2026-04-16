<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

$cakeDescription = 'NexPlay';
$currentController = $this->request->getParam('controller');
$currentAction = $this->request->getParam('action');
$isGamesActive = in_array($currentController, ['Boardgames', 'Fillers', 'Masterminds', 'Labyrinth'], true);
$isLeaderboardActive = $currentController === 'Users' && $currentAction === 'leaderboard';
$username = !empty($auth) && !empty($auth->username) ? (string)$auth->username : null;
$userId = !empty($auth) && !empty($auth->id) ? (int)$auth->id : null;
$avatarInitial = $username !== null ? strtoupper(substr($username, 0, 1)) : 'G';
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css(['normalize.min', 'milligram.min', 'fonts', 'cake', ]) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->Html->css('style') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-shell">
            <div class="top-nav-brand">
                <a class="top-nav-brand-link" href="<?= $this->Url->build('/') ?>">
                    <span class="top-nav-brand-badge" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false">
                            <path d="M7.5 9.5h9a3 3 0 0 1 2.9 2.2l1 3.5a2 2 0 0 1-3.1 2.2l-2.3-1.6H8.9l-2.3 1.6a2 2 0 0 1-3.1-2.2l1-3.5a3 3 0 0 1 3-2.2Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                            <path d="M8.5 13h4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/>
                            <path d="M10.5 11v4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/>
                            <circle cx="15.7" cy="12.3" r="1"/>
                            <circle cx="17.8" cy="14.2" r="1"/>
                        </svg>
                    </span>
                    <span class="top-nav-brand-name"><?= h($cakeDescription) ?></span>
                </a>
            </div>

            <div class="top-nav-menu" aria-label="Primary">
                <?= $this->Html->link('Jeux', ['controller' => 'Boardgames', 'action' => 'index'], [
                    'class' => 'top-nav-link' . ($isGamesActive ? ' is-active' : ''),
                ]) ?>
                <?= $this->Html->link('Classement', ['controller' => 'Users', 'action' => 'leaderboard'], [
                    'class' => 'top-nav-link' . ($isLeaderboardActive ? ' is-active' : ''),
                ]) ?>
            </div>

            <div class="top-nav-user">
                <?php if ($username !== null): ?>
                    <?= $this->Html->link(
                        '<span class="top-nav-avatar">' . h($avatarInitial) . '</span><span class="top-nav-username">' . h($username) . '</span>',
                        ['controller' => 'Users', 'action' => 'view', $userId],
                        ['class' => 'top-nav-user-card', 'escape' => false]
                    ) ?>
                    <?= $this->Html->link('Logout', ['controller' => 'Users', 'action' => 'logout'], [
                        'class' => 'top-nav-auth-link',
                    ]) ?>
                <?php else: ?>
                    <?= $this->Html->link('Login', ['controller' => 'Users', 'action' => 'login'], [
                        'class' => 'top-nav-auth-link is-highlight',
                    ]) ?>
                    <?= $this->Html->link('Sign up', ['controller' => 'Users', 'action' => 'add'], [
                        'class' => 'top-nav-auth-link',
                    ]) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="top-nav-title">
            <a href="<?= $this->Url->build('/') ?>"><span>NexPlay</span></a>
        </div>
        <div class="top-nav-links">
            <?= $this->Html->link('Jeux', ['controller' => 'Boardgames', 'action' => 'index']) ?>
            <?php if (!empty($auth)): ?>
                <?= $this->Html->link('Déconnexion', ['controller' => 'Users', 'action' => 'logout']) ?>
            <?php else: ?>
                <?= $this->Html->link('Connexion', ['controller' => 'Users', 'action' => 'login']) ?>
            <?php endif; ?>
        </div>
    </nav>
    <main class="main">
        <div class="container">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <footer>
    </footer>
</body>
</html>
