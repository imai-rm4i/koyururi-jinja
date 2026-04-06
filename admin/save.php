<?php
/**
 * Author: TB
 * admin/save.php
 * 保存処理（新規/編集 + 画像：追加/削除/差し替え/並び替え）
 * 追加対応：datetime（日時）を保存し、date（表示用）はdatetimeから自動生成
 */

declare(strict_types=1);
mb_internal_encoding('UTF-8');

// 認証（あるなら読む。無いならスキップ）
$authPath = __DIR__ . '/auth.php';
if (file_exists($authPath)) {
    require_once $authPath;
}

$newsJsonPath = dirname(__DIR__) . '/news.json';
$uploadsDir   = dirname(__DIR__) . '/images/uploads';

function sendBadRequest(string $msg): void
{
    http_response_code(400);
    echo $msg;
    exit;
}

function safeBasename(string $name): string
{
    $name = basename($name);
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    return $name ?: 'file';
}

function ensureUploadsDir(string $dir): void
{
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            sendBadRequest('images/uploads ディレクトリを作成できませんでした');
        }
    }
}

function isImageMime(string $tmpPath): bool
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) return false;
    $mime = finfo_file($finfo, $tmpPath) ?: '';
    finfo_close($finfo);
    return strpos($mime, 'image/') === 0;
}

/**
 * datetime-local (例: 2026-01-05T22:58) / "Y-m-d H:i" / "Y-m-d" を
 * news.json 保存用の "Y-m-d H:i" に正規化して返す
 */
function normalizeDatetime(string $input): string
{
    $v = trim($input);
    if ($v === '') {
        return date('Y-m-d H:i');
    }

    // datetime-local の "T" をスペースに
    $v = str_replace('T', ' ', $v);

    // 秒が来た場合は落とす（Y-m-d H:i:s -> Y-m-d H:i）
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $v)) {
        $v = substr($v, 0, 16);
    }

    // 日付だけなら、現在時刻（分まで）を補完
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        $v .= ' ' . date('H:i');
    }

    // 最終バリデーション
    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $v)) {
        sendBadRequest('日時の形式が不正です（例：2026-01-05 22:58）');
    }

    return $v;
}

/**
 * アップロード保存して "images/uploads/xxx.ext" を返す
 */
function saveUploadedImage(array $file, string $uploadsDir): string
{
    if (!isset($file['tmp_name'], $file['name'])) {
        sendBadRequest('アップロード情報が不正です');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        sendBadRequest('アップロードが不正です');
    }
    if (!isImageMime($file['tmp_name'])) {
        sendBadRequest('画像ファイルではありません（MIME）');
    }

    ensureUploadsDir($uploadsDir);

    $orig = safeBasename((string)$file['name']);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $ext  = $ext ? $ext : 'png';

    // ざっくり許可（必要なら絞る）
    $allow = ['jpg','jpeg','png','gif','webp','svg'];
    if (!in_array($ext, $allow, true)) {
        sendBadRequest('許可されていない拡張子です');
    }

    $newName = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destAbs = rtrim($uploadsDir, '/') . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
        sendBadRequest('画像の保存に失敗しました');
    }

    return 'images/uploads/' . $newName; // news.json にはこれを保存
}

function loadNews(string $path): array
{
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $decoded = json_decode($raw ?: '[]', true);
    return is_array($decoded) ? $decoded : [];
}

function saveNews(string $path, array $news): void
{
    $json = json_encode($news, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        sendBadRequest('JSONエンコードに失敗しました');
    }
    if (file_put_contents($path, $json) === false) {
        sendBadRequest('news.json の保存に失敗しました');
    }
}

/**
 * "images/uploads/xxx.png" または "uploads/xxx.png" なら物理削除する
 */
function deletePhysicalFile(string $relativePath, string $rootDir): void
{
    $relativePath = trim($relativePath);
    if (strpos($relativePath, 'images/uploads/') !== 0 && strpos($relativePath, 'uploads/') !== 0) return;

    $abs = rtrim($rootDir, '/') . '/' . ltrim($relativePath, '/');
    if (is_file($abs)) {
        @unlink($abs);
    }
}

// -----------------------------
// 削除処理（GETで受ける）
// -----------------------------
if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    $deleteId = preg_replace('/[^0-9]/', '', (string)$_GET['delete']);
    $news = loadNews($newsJsonPath);

    $found = false;
    $updated = [];
    foreach ($news as $item) {
        if (isset($item['id']) && (string)$item['id'] === $deleteId) {
            // 画像ファイルも物理削除
            if (!empty($item['images']) && is_array($item['images'])) {
                foreach ($item['images'] as $img) {
                    deletePhysicalFile($img, dirname(__DIR__));
                }
            }
            $found = true;
            continue; // この記事をスキップ＝削除
        }
        $updated[] = $item;
    }

    if ($found) {
        saveNews($newsJsonPath, $updated);
    }

    header('Location: dashboard.php');
    exit;
}

// -----------------------------
// 入力
// -----------------------------
$id = isset($_POST['id']) ? preg_replace('/[^0-9]/', '', (string)$_POST['id']) : '';

/**
 * ✅ datetime を主とする
 * - edit.php の input が date のままでも、まずは date を拾って補完できるようにしてある
 * - ただし理想は edit.php 側を datetime-local に変更して POST['datetime'] を送る
 */
$datetimeRaw = '';
if (isset($_POST['datetime']) && is_string($_POST['datetime'])) {
    $datetimeRaw = (string)$_POST['datetime'];
} elseif (isset($_POST['date']) && is_string($_POST['date'])) {
    $datetimeRaw = (string)$_POST['date']; // 日付だけ来るなら、時刻補完する
}
$datetime = normalizeDatetime($datetimeRaw);

/**
 * ✅ date（表示用・互換用）は datetime から必ず作る
 * 既存の dashboard/index.js が date を見てても壊れへん
 */
$date = substr($datetime, 0, 10);

$title = isset($_POST['title']) ? (string)$_POST['title'] : '';
$body  = isset($_POST['body']) ? (string)$_POST['body'] : '';

$existingImages = isset($_POST['existing_images']) && is_array($_POST['existing_images'])
    ? array_values(array_filter(array_map('strval', $_POST['existing_images'])))
    : [];

$deleteImages = isset($_POST['delete_images']) && is_array($_POST['delete_images'])
    ? array_values(array_filter(array_map('strval', $_POST['delete_images'])))
    : [];

// 並び順（JSON配列）
$order = [];
if (isset($_POST['image_order_json']) && is_string($_POST['image_order_json']) && $_POST['image_order_json'] !== '') {
    $decoded = json_decode($_POST['image_order_json'], true);
    if (is_array($decoded)) {
        $order = array_values(array_filter(array_map('strval', $decoded)));
    }
}

// -----------------------------
// news 読込＆対象取得
// -----------------------------
$news = loadNews($newsJsonPath);

$idx = null;
if ($id !== '') {
    foreach ($news as $i => $n) {
        if (isset($n['id']) && (string)$n['id'] === $id) {
            $idx = $i;
            break;
        }
    }
}

// 新規ならID作る（✅同一秒衝突対策で乱数足す）
if ($id === '') {
    $id = date('YmdHis') . (string)random_int(100, 999);
}

// 現在の画像リスト（フォームの existing_images を信頼基準にする）
$images = $existingImages;

// -----------------------------
// 1) 並び替え反映
// -----------------------------
if (!empty($order)) {
    $set = array_flip($images);
    $sorted = [];

    foreach ($order as $p) {
        if (isset($set[$p])) {
            $sorted[] = $p;
            unset($set[$p]);
        }
    }
    // orderに無い残り（念のため）
    foreach (array_keys($set) as $rest) {
        $sorted[] = $rest;
    }
    $images = $sorted;
}

// -----------------------------
// 2) 削除反映（物理削除も）
// -----------------------------
if (!empty($deleteImages)) {
    $delSet = array_flip($deleteImages);

    $kept = [];
    foreach ($images as $p) {
        if (isset($delSet[$p])) {
            deletePhysicalFile($p, dirname(__DIR__));
            continue;
        }
        $kept[] = $p;
    }
    $images = $kept;
}

// -----------------------------
// 3) 差し替え反映（filenameキー）
// -----------------------------
if (isset($_FILES['replace_images']) && is_array($_FILES['replace_images'])) {
    // $_FILES['replace_images'] は name/tmp_name が filenameキーで入る
    $names = $_FILES['replace_images']['name'] ?? [];
    $tmps  = $_FILES['replace_images']['tmp_name'] ?? [];
    $errs  = $_FILES['replace_images']['error'] ?? [];
    $sizes = $_FILES['replace_images']['size'] ?? [];

    foreach ($names as $key => $name) {
        // key は "images/uploads/xxx.png" のはず
        $k = (string)$key;

        if (!isset($errs[$key]) || (int)$errs[$key] !== UPLOAD_ERR_OK) {
            continue; // 未選択やエラーは無視
        }
        if (!in_array($k, $images, true)) {
            continue; // 既存に無いものは無視
        }

        $file = [
            'name'     => $name,
            'tmp_name' => $tmps[$key] ?? '',
            'error'    => $errs[$key] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $sizes[$key] ?? 0,
        ];

        $newPath = saveUploadedImage($file, $uploadsDir);

        // 置換：同じ位置で差し替える（旧ファイルは削除）
        foreach ($images as $i => $p) {
            if ($p === $k) {
                deletePhysicalFile($p, dirname(__DIR__));
                $images[$i] = $newPath;
                break;
            }
        }
    }
}

// -----------------------------
// 4) 新規追加（最大3枚になるように）
// -----------------------------
$maxImages = 3;
$remain = $maxImages - count($images);
if ($remain > 0 && isset($_FILES['images']) && is_array($_FILES['images'])) {
    $names = $_FILES['images']['name'] ?? [];
    $tmps  = $_FILES['images']['tmp_name'] ?? [];
    $errs  = $_FILES['images']['error'] ?? [];
    $sizes = $_FILES['images']['size'] ?? [];

    $added = 0;
    foreach ($names as $i => $name) {
        if ($added >= $remain) break;

        if (!isset($errs[$i]) || (int)$errs[$i] !== UPLOAD_ERR_OK) continue;

        $file = [
            'name'     => $name,
            'tmp_name' => $tmps[$i] ?? '',
            'error'    => $errs[$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $sizes[$i] ?? 0,
        ];
        $newPath = saveUploadedImage($file, $uploadsDir);
        $images[] = $newPath;
        $added++;
    }
}

// -----------------------------
// 5) news 更新
// -----------------------------
$item = [
    'id'       => $id,
    'datetime' => $datetime, // ✅追加：日時（主キー）
    'date'     => $date,     // ✅互換：表示・古いJS用
    'title'    => $title,
    'body'     => $body,
    'images'   => array_values($images),
];

if ($idx === null) {
    $news[] = $item;
} else {
    $news[$idx] = $item;
}

saveNews($newsJsonPath, $news);

// 保存後：編集画面へ戻す
header('Location: edit.php?id=' . urlencode($id) . '&saved=1');
exit;