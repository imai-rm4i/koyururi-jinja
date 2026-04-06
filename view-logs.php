<?php
$log_dir = __DIR__ . '/logs';
$log_file = $log_dir . '/access_' . date('Y-m') . '.log';
// 試験運用のための対象ドメイン
$domains = ['koyururi-jinja.com'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>アクセス解析 - koyururi-jinja.com</title>
    <style>
        body { font-family: sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { border-left: 5px solid #007bff; padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #eee; }
        .total { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>koyururi-jinja.com アクセス集計</h2>
    <?php
    if (!file_exists($log_file)) {
        echo "<p>ログデータがまだありません。サイトにアクセスしてください。</p>";
    } else {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [];
        foreach ($lines as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2}).+?\]\s(.+?)\s\|\s.+?\s\|\s.+?\s\|/', $line, $matches)) {
                $date = $matches[1]; $dom = trim($matches[2]);
                $stats[$date][$dom] = ($stats[$date][$dom] ?? 0) + 1;
            }
        }
        echo "<table><tr><th>日付</th><th>アクセス数</th></tr>";
        krsort($stats);
        foreach ($stats as $date => $data) {
            $count = $data['koyururi-jinja.com'] ?? 0;
            echo "<tr><td>$date</td><td>$count</td></tr>";
        }
        echo "</table>";
    }
    ?>
</div>
</body>
</html>