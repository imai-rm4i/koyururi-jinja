<?php
// Author: TB
session_start();
if (empty($_SESSION['login'])) {
  header('Location: login.php');
  exit;
}

$news = json_decode(file_get_contents('../news.json'), true) ?: [];
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>お知らせ管理</title>
  <style>
    body{
      font-family: system-ui, -apple-system, "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
      margin: 24px;
    }
    h1{
      margin: 0 0 14px;
    }
    .actions{
      margin: 0 0 16px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }
    .actions a{
      display: inline-block;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 10px;
      text-decoration: none;
      color: #111;
      background: #fff;
      line-height: 1;
    }

    table{
      width: 100%;
      border-collapse: collapse;
      max-width: 900px;
      background: #fff;
    }
    th, td{
      border: 1px solid #ddd;
      padding: 10px;
      vertical-align: top;
      text-align: left;
    }
    th{
      background: #f7f7f7;
      white-space: nowrap;
    }
    .op a{
      display: inline-block;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 10px;
      text-decoration: none;
      color: #111;
      background: #fff;
      margin-right: 6px;
      line-height: 1;
    }

    /* スマホ：テーブルをカード化 */
    @media (max-width: 768px){
      body{
        margin: 14px;
      }

      table, thead, tbody, th, td, tr{
        display: block;
        width: 100%;
      }

      thead{
        display: none;
      }

      tr{
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 12px;
        margin: 0 0 12px;
        background: #fff;
      }

      td{
        border: none;
        padding: 6px 0;
        font-size: 16px;
      }

      td[data-label]::before{
        content: attr(data-label) "：";
        font-weight: 700;
        display: inline-block;
        min-width: 4.5em;
      }

      .op{
        margin-top: 8px;
      }

      .op a{
        width: calc(26% - 6px);
        text-align: center;
        padding: 12px 10px;
        margin-right: 0;
      }

      .op a + a{
        margin-left: 12px;
      }
    }
  </style>
</head>
<body>
  <h1>お知らせ管理</h1>

  <p class="actions">
    <a href="edit.php">＋ 新規追加</a>
    <a href="logout.php">ログアウト</a>
  </p>

  <table>
    <thead>
      <tr>
        <th>日付</th>
        <th>タイトル</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($news as $item): ?>
      <tr>
        <td data-label="日付"><?= htmlspecialchars($item['date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td data-label="タイトル"><?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="op" data-label="操作">
          <a href="edit.php?id=<?= htmlspecialchars((string)($item['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">編集</a>
          <a href="save.php?delete=<?= htmlspecialchars((string)($item['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" onclick="return confirm('削除する？')">削除</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>