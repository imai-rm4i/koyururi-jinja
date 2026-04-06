<?php
$log_dir = __DIR__ . '/logs';

/* // 管理者Cookieがない場合に404を返す（動作確認のため一時コメントアウト）
if (!isset($_COOKIE['admin_exclude'])) {
    header("HTTP/1.1 404 Not Found");
    exit;
}
*/

// 削除処理
if (isset($_POST['delete_log'])) {
    $target = $log_dir . '/' . basename($_POST['delete_log']);
    if (file_exists($target)) {
        unlink($target);
    }
    header("Location: logs-view.php");
    exit;
}

$files = glob($log_dir . '/*.log');
if ($files) {
    rsort($files); // 新しい順
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Access Logs</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; background: #f4f4f4; padding: 20px; }
        .log-file { background: #fff; margin-bottom: 20px; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        pre { background: #333; color: #fff; padding: 10px; overflow-x: auto; white-space: pre-wrap; font-size: 12px; }
        .del-btn { color: red; cursor: pointer; border: 1px solid red; background: none; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>Access Logs</h1>
    <?php if (empty($files)): ?>
        <p>ログはありません。</p>
    <?php else: ?>
        <?php foreach ($files as $file): ?>
            <div class="log-file">
                <strong><?php echo basename($file); ?></strong>
                <form method="POST" style="display:inline; margin-left:10px;">
                    <input type="hidden" name="delete_log" value="<?php echo basename($file); ?>">
                    <button type="submit" class="del-btn" onclick="return confirm('削除しますか？')">削除</button>
                </form>
                <pre><?php echo htmlspecialchars(file_get_contents($file)); ?></pre>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>