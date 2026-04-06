<?php
/**
 * Author: TB
 * admin/login.php
 */

declare(strict_types=1);
mb_internal_encoding('UTF-8');

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

$config = require dirname(__DIR__) . '/config.php';

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $user = (string)($_POST['user'] ?? '');
    $pass = (string)($_POST['pass'] ?? '');

    if (
        $user !== '' &&
        $pass !== '' &&
        hash_equals((string)($config['user'] ?? ''), $user) &&
        password_verify($pass, (string)($config['pass_hash'] ?? ''))
    ) {
        $_SESSION['login'] = true;
        header('Location: dashboard.php');
        exit;
    }

    $error = 'ログイン失敗';
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ログイン</title>
</head>
<body>
    <h1>ログイン</h1>

    <?php if ($error !== ''): ?>
        <p style="color:#c00;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <div>
            <label>ユーザー</label><br>
            <input type="text" name="user" autocomplete="username">
        </div>
        <div style="margin-top:10px;">
            <label>パスワード</label><br>
            <input type="password" name="pass" autocomplete="current-password">
        </div>
        <div style="margin-top:12px;">
            <button type="submit">ログイン</button>
        </div>
    </form>
</body>
</html>