<?php
/**
 * Author: TB
 * admin/edit.php
 * 新規作成/編集フォーム（既存画像の削除・差し替え・並び替え + プレビュー）
 */

declare(strict_types=1);
mb_internal_encoding('UTF-8');

// 認証（あるなら読む。無いならスキップ）
$authPath = __DIR__ . '/auth.php';
if (file_exists($authPath)) {
    require_once $authPath;
}

$newsJsonPath = dirname(__DIR__) . '/news.json';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$news = [];
if (file_exists($newsJsonPath)) {
    $raw = file_get_contents($newsJsonPath);
    $decoded = json_decode($raw ?: '[]', true);
    if (is_array($decoded)) {
        $news = $decoded;
    }
}

$id = '';
if (isset($_GET['id'])) {
    $id = preg_replace('/[^0-9]/', '', (string)$_GET['id']);
}

/**
 * 互換性：
 * - 新仕様: datetime (Y-m-d H:i:s)
 * - 旧仕様: date (Y-m-d)
 */
$nowDatetimeLocal = date('Y-m-d\TH:i'); // datetime-local のvalue用

$data = [
    'id'       => '',
    'title'    => '',
    'body'     => '',
    'datetime' => date('Y-m-d H:i:s'),
    'images'   => [],
];

if ($id !== '') {
    foreach ($news as $n) {
        if (isset($n['id']) && (string)$n['id'] === $id) {
            $data = array_merge($data, $n);
            break;
        }
    }
}

// 旧データ救済：datetime が無ければ date を使う
if (empty($data['datetime'])) {
    $d = isset($data['date']) ? (string)$data['date'] : date('Y-m-d');
    // 日付しかない場合は 09:00:00 とかに固定してもOK。ここでは 00:00:00
    $data['datetime'] = $d . ' 00:00:00';
}

if (!isset($data['images']) || !is_array($data['images'])) {
    $data['images'] = [];
}

/**
 * admin配下から画像URLを作る（news.json には "images/uploads/xxx.png" を入れておく想定）
 */
function imageSrcFromAdmin(string $path): string
{
    $path = trim($path);
    if ($path === '') return '';

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (strpos($path, 'images/uploads/') === 0 || strpos($path, 'uploads/') === 0) {
        return '../' . $path; // admin/ から ../images/uploads/...
    }
    if (strpos($path, '../') === 0) {
        return $path;
    }
    return '../' . ltrim($path, '/');
}

// datetime-local の value に整形
$datetimeLocalValue = $nowDatetimeLocal;
if (!empty($data['datetime'])) {
    $ts = strtotime((string)$data['datetime']);
    if ($ts !== false) {
        $datetimeLocalValue = date('Y-m-d\TH:i', $ts);
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $id !== '' ? '編集' : '新規追加' ?></title>
    <style>
        body { font-family: system-ui, -apple-system, "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif; margin: 24px; }
        h1 { margin: 0 0 18px; }
        label { display: inline-block; min-width: 80px; }
        input[type="text"] { width: 520px; max-width: 100%; }
        textarea { width: 740px; max-width: 100%; height: 160px; }
        .row { margin: 14px 0; }
        .help { font-size: 12px; color: #666; margin-top: 6px; }
        .btn { padding: 8px 14px; }

        /* 新規追加プレビュー */
        .preview-area { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .preview-area img { width: 140px; border: 1px solid #ddd; border-radius: 6px; padding: 2px; background: #fff; }

        /* 追加アップロード：ドロップゾーン */
        .dropZone {
            margin-top: 10px;
            padding: 16px;
            border: 2px dashed #999;
            border-radius: 8px;
            background: #fafafa;
            text-align: center;
            cursor: pointer;
            user-select: none;
        }
        .dropZone.isDrag { background: #eef6ff; border-color: #339; }

        /* 既存画像 */
        #existingImages { display: grid; gap: 14px; max-width: 820px; }
        .imageItem {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            cursor: grab;
        }
        .imageThumb { width: 240px; flex: 0 0 240px; }
        .imageThumb img { width: 240px; height: auto; display: block; border-radius: 8px; border: 1px solid #eee; background: #fafafa; }
        .imageBody { flex: 1; }
        .fileName { font-size: 12px; color: #666; word-break: break-all; }
        .controls { display: flex; gap: 16px; align-items: center; margin-top: 10px; flex-wrap: wrap; }
        .note { display:none; font-size:12px; color:#0a7; }
        .back { margin-top: 14px; }

        /* 保存完了メッセージ */
        .saved-msg{
          padding: 12px 18px;
          margin: 0 0 18px;
          background: #e6f7e6;
          border: 1px solid #8fc98f;
          border-radius: 10px;
          color: #256625;
          font-weight: 700;
          display: flex;
          align-items: center;
          gap: 10px;
          animation: fadeIn .3s ease;
        }
        .saved-msg a{
          margin-left: auto;
          padding: 8px 14px;
          border: 1px solid #8fc98f;
          border-radius: 8px;
          text-decoration: none;
          color: #256625;
          background: #fff;
          font-weight: 500;
          font-size: 14px;
          white-space: nowrap;
        }
        .saved-msg a:hover{
          background: #f0faf0;
        }
        @keyframes fadeIn{
          from{ opacity:0; transform:translateY(-8px); }
          to{ opacity:1; transform:translateY(0); }
        }

        .preview-area { display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
        .preview-item{
          width:140px;
          border:1px solid #ddd;
          border-radius:6px;
          padding:6px;
          background:#fff;
          cursor:grab;
        }
        .preview-item.dragging{ opacity:.6; }
        .preview-item img{ width:100%; display:block; border-radius:4px; }
        .preview-item .cap{ font-size:12px; color:#666; margin-top:4px; word-break:break-all; }
        .preview-item .moveBtns{
          display:flex;
          gap:6px;
          margin-top:6px;
        }
        .preview-item .moveBtn{
          flex:1;
          padding:6px 8px;
          font-size:14px;
          border:1px solid #ccc;
          background:#f7f7f7;
          border-radius:6px;
        }
        .preview-item .moveBtn:active{
          transform: translateY(1px);
        }

        /* スマホ：既存画像カードのはみ出し防止（縦積み） */
        @media (max-width: 768px){
          #existingImages{ max-width: 100%; }

          .imageItem{
            flex-direction: column;
            gap: 10px;
            padding: 10px;
            cursor: default;
          }

          .imageThumb{ width: 100%; flex: 0 0 auto; }
          .imageThumb img{ width: 100%; max-width: 100%; height: auto; }

          .imageBody{ width: 100%; }

          .controls{ width: 100%; gap: 10px; }
          .controls label{
            width: 100%;
            min-width: 0;
            white-space: normal;
          }
          .controls input[type="file"]{ max-width: 100%; }

          body{ overflow-x: hidden; margin: 14px; }

          .fileName{ font-size: 14px; line-height: 1.4; }

          .controls{ gap: 12px; margin-top: 12px; }
          .controls label{ font-size: 16px; }
          .controls input[type="file"]{ width: 100%; font-size: 16px; }

          .preview-item{ width: calc(50% - 10px); padding: 10px; }
          .preview-item .cap{ font-size: 13px; }
          .preview-item .moveBtn{ min-height: 44px; font-size: 18px; }
        }

        /* 既存画像：スマホ用▲▼ */
        .existingMoveBtns{
          display:none;
          gap:6px;
          margin-top:10px;
        }
        @media (max-width: 768px){
          .existingMoveBtns{ display:flex; gap:10px; }
          .existingMoveBtns .moveBtn{
            min-height: 48px;
            font-size: 18px;
            border-radius: 10px;
          }
        }
    </style>
</head>
<body>
    <h1><?= $id !== '' ? '編集' : '新規追加' ?></h1>

    <?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
    <div class="saved-msg">
      ✅ 保存しました
      <a href="dashboard.php">← 一覧に戻る</a>
    </div>
    <?php endif; ?>

    <form method="post" action="save.php" enctype="multipart/form-data" id="newsForm">
        <input type="hidden" name="id" value="<?= h((string)($data['id'] ?? '')) ?>">

        <div class="row">
            <label for="datetime">日時</label>
            <input id="datetime" type="datetime-local" name="datetime" value="<?= h($datetimeLocalValue) ?>" required>
            <div class="help">※ 1日に複数回更新しても順番が崩れないよう「日時」で管理します。</div>
        </div>

        <div class="row">
            <label for="title">タイトル</label>
            <input id="title" type="text" name="title" value="<?= h((string)($data['title'] ?? '')) ?>">
        </div>

        <div class="row">
            <label for="body">本文</label><br>
            <textarea id="body" name="body"><?= h((string)($data['body'] ?? '')) ?></textarea>
        </div>

        <div class="row">
            <label>画像（追加：最大3枚）１枚目は一覧のサムネイルに使用されます</label><br>
            <input type="file" name="images[]" id="imagesInput" multiple accept="image/*">
            <div class="help">※ 追加アップロードは最大3枚まで。既存画像は下で「削除」「差し替え」。保存後は「並び替え」できます。</div>

            <div id="dropZone" class="dropZone">ここに画像をドラッグ＆ドロップ（最大3枚） / クリックでも選択できます</div>

            <div id="previewArea" class="preview-area" aria-live="polite"></div>
        </div>

        <?php if (!empty($data['images'])): ?>
            <div class="row" style="margin-top:18px;">
                <h3 style="margin:0 0 8px;">既存画像（ドラッグで順番変更）</h3>
                <div class="help">削除：チェックして保存。差し替え：ファイルを選んで保存。差し替えすると削除チェックは自動で外れます。</div>

                <div id="existingImages">
                    <?php foreach ($data['images'] as $img): ?>
                        <?php
                            $imgStr = (string)$img;
                            $src    = imageSrcFromAdmin($imgStr);
                        ?>
                        <div
                            class="imageItem"
                            draggable="true"
                            data-filename="<?= h($imgStr) ?>"
                        >
                            <div class="imageThumb">
                                <img
                                    src="<?= h($src) ?>"
                                    alt=""
                                    data-preview-img
                                    data-original-src="<?= h($src) ?>"
                                >
                            </div>

                            <div class="imageBody">
                                <div class="fileName"><?= h(basename($imgStr)) ?></div>

                                <!-- 並び替え/置換/削除の基準を filename で統一 -->
                                <input type="hidden" name="existing_images[]" value="<?= h($imgStr) ?>">

                                <div class="controls">
                                    <label style="display:flex;align-items:center;gap:6px;">
                                        <input
                                            type="checkbox"
                                            name="delete_images[]"
                                            value="<?= h($imgStr) ?>"
                                            data-delete-checkbox
                                        >
                                        削除
                                    </label>

                                    <label style="display:flex;align-items:center;gap:6px;">
                                        差し替え
                                        <input
                                            type="file"
                                            name="replace_images[<?= h($imgStr) ?>]"
                                            accept="image/*"
                                            data-replace-input
                                        >
                                    </label>

                                    <span class="note" data-replace-note>※差し替えプレビュー表示中（保存で反映）</span>
                                </div>

                                <div class="existingMoveBtns">
                                    <button type="button" class="moveBtn" data-move-up>▲</button>
                                    <button type="button" class="moveBtn" data-move-down>▼</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" name="image_order_json" id="imageOrderJson" value="">
            </div>
        <?php endif; ?>

        <div class="row">
            <button class="btn" type="submit">保存</button>
        </div>
    </form>

    <div class="back">
        <a href="dashboard.php">← 戻る</a>
    </div>

<script>
/**
 * Author: TB
 * edit.php inline JS
 * - 新規追加画像：プレビュー＋並び替え
 * - 既存画像：ドラッグ＆▲▼で並び替え → image_order_json
 * - 既存画像：差し替えプレビュー＆削除チェック連動
 */
(function () {
  // =======================
  // 新規追加：ドロップ＆プレビュー＋並び替え（▲▼＋ドラッグ）
  // =======================
  const input = document.getElementById('imagesInput');
  const previewArea = document.getElementById('previewArea');
  const dropZone = document.getElementById('dropZone');

  if (input && previewArea) {
    let selectedFiles = [];

    function syncInputFiles() {
      const dt = new DataTransfer();
      selectedFiles.forEach(f => dt.items.add(f));
      input.files = dt.files;
    }

    function moveFile(index, dir) {
      const to = index + dir;
      if (to < 0 || to >= selectedFiles.length) return;

      const tmp = selectedFiles[index];
      selectedFiles[index] = selectedFiles[to];
      selectedFiles[to] = tmp;

      syncInputFiles();
      renderPreview();
    }

    function renderPreview() {
      previewArea.innerHTML = '';

      selectedFiles.forEach((file, idx) => {
        if (!file.type || !file.type.startsWith('image/')) return;

        const item = document.createElement('div');
        item.className = 'preview-item';
        item.draggable = true;
        item.dataset.index = String(idx);

        const img = document.createElement('img');
        img.alt = '';
        img.src = URL.createObjectURL(file);
        img.onload = () => {
          try { URL.revokeObjectURL(img.src); } catch (e) {}
        };

        const cap = document.createElement('div');
        cap.className = 'cap';
        cap.textContent = file.name;

        const btns = document.createElement('div');
        btns.className = 'moveBtns';

        const upBtn = document.createElement('button');
        upBtn.type = 'button';
        upBtn.className = 'moveBtn';
        upBtn.textContent = '▲';
        upBtn.disabled = (idx === 0);
        upBtn.addEventListener('click', () => moveFile(idx, -1));

        const downBtn = document.createElement('button');
        downBtn.type = 'button';
        downBtn.className = 'moveBtn';
        downBtn.textContent = '▼';
        downBtn.disabled = (idx === selectedFiles.length - 1);
        downBtn.addEventListener('click', () => moveFile(idx, +1));

        btns.appendChild(upBtn);
        btns.appendChild(downBtn);

        item.appendChild(img);
        item.appendChild(cap);
        item.appendChild(btns);

        previewArea.appendChild(item);
      });

      bindDnD();
    }

    function bindDnD() {
      const items = Array.from(previewArea.querySelectorAll('.preview-item'));
      let draggingEl = null;

      items.forEach(el => {
        el.addEventListener('dragstart', (e) => {
          draggingEl = el;
          el.classList.add('dragging');
          e.dataTransfer.effectAllowed = 'move';
          try { e.dataTransfer.setData('text/plain', el.dataset.index || ''); } catch (err) {}
        });

        el.addEventListener('dragend', () => {
          if (draggingEl) draggingEl.classList.remove('dragging');
          draggingEl = null;

          const newOrder = Array.from(previewArea.querySelectorAll('.preview-item'))
            .map(x => Number(x.dataset.index))
            .filter(n => Number.isFinite(n));

          selectedFiles = newOrder.map(i => selectedFiles[i]).filter(Boolean);
          syncInputFiles();
          renderPreview();
        });
      });

      previewArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        const over = e.target.closest('.preview-item');
        if (!over || !draggingEl || over === draggingEl) return;

        const rect = over.getBoundingClientRect();
        const isAfter = (e.clientX - rect.left) > (rect.width / 2);

        if (isAfter) over.after(draggingEl);
        else over.before(draggingEl);
      });
    }

    input.addEventListener('change', () => {
      selectedFiles = Array.from(input.files || [])
        .filter(f => f.type && f.type.startsWith('image/'))
        .slice(0, 3);

      syncInputFiles();
      renderPreview();
    });

    if (dropZone) {
      dropZone.addEventListener('click', () => input.click());

      ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
          e.preventDefault();
          dropZone.classList.add('isDrag');
        });
      });

      ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
          e.preventDefault();
          dropZone.classList.remove('isDrag');
        });
      });

      dropZone.addEventListener('drop', (e) => {
        e.preventDefault();

        const files = Array.from(e.dataTransfer.files || [])
          .filter(f => f.type && f.type.startsWith('image/'))
          .slice(0, 3);

        if (files.length === 0) return;

        selectedFiles = files;
        syncInputFiles();
        renderPreview();
      });
    }
  }

  // =======================
  // 既存画像：ドラッグ＆スマホ▲▼で並び替え → image_order_json
  // =======================
  const existingImages = document.getElementById('existingImages');
  const orderInput = document.getElementById('imageOrderJson');
  const form = document.getElementById('newsForm');

  function updateImageOrderJson() {
    if (!existingImages || !orderInput) return;
    const items = Array.from(existingImages.querySelectorAll('.imageItem'));
    const filenames = items.map(el => el.getAttribute('data-filename')).filter(Boolean);
    orderInput.value = JSON.stringify(filenames);
  }

  // スマホ用 ▲▼
  if (existingImages && orderInput) {
    existingImages.addEventListener('click', (e) => {
      const upBtn = e.target.closest('[data-move-up]');
      const downBtn = e.target.closest('[data-move-down]');
      if (!upBtn && !downBtn) return;

      const item = e.target.closest('.imageItem');
      if (!item) return;

      if (upBtn) {
        const prev = item.previousElementSibling;
        if (prev) prev.before(item);
      }

      if (downBtn) {
        const next = item.nextElementSibling;
        if (next) next.after(item);
      }

      updateImageOrderJson();
    });
  }

  // PC用：ドラッグ
  if (existingImages && orderInput) {
    let draggingEl = null;

    existingImages.addEventListener('dragstart', (e) => {
      const item = e.target.closest('.imageItem');
      if (!item) return;

      draggingEl = item;
      item.style.opacity = '0.6';
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', item.getAttribute('data-filename') || ''); } catch (err) {}
    });

    existingImages.addEventListener('dragend', () => {
      if (draggingEl) draggingEl.style.opacity = '1';
      draggingEl = null;
      updateImageOrderJson();
    });

    existingImages.addEventListener('dragover', (e) => {
      e.preventDefault();
      const overItem = e.target.closest('.imageItem');
      if (!overItem || !draggingEl || overItem === draggingEl) return;

      const rect = overItem.getBoundingClientRect();
      const isAfter = (e.clientY - rect.top) > (rect.height / 2);

      if (isAfter) overItem.after(draggingEl);
      else overItem.before(draggingEl);
    });

    updateImageOrderJson();
  }

  // 保存直前にも必ず反映（事故防止）
  if (form) {
    form.addEventListener('submit', () => {
      updateImageOrderJson();
    });
  }

  // =======================
  // 既存画像：差し替えプレビュー + 事故防止
  // =======================
  document.querySelectorAll('[data-replace-input]').forEach((ri) => {
    ri.addEventListener('change', () => {
      const file = (ri.files && ri.files[0]) ? ri.files[0] : null;
      const item = ri.closest('.imageItem');
      if (!item) return;

      const previewImg = item.querySelector('[data-preview-img]');
      const deleteCb = item.querySelector('[data-delete-checkbox]');
      const note = item.querySelector('[data-replace-note]');
      const originalSrc = previewImg ? (previewImg.getAttribute('data-original-src') || '') : '';

      if (file && deleteCb) deleteCb.checked = false;

      if (!file) {
        if (previewImg && originalSrc) previewImg.src = originalSrc;
        if (note) note.style.display = 'none';
        return;
      }

      if (!file.type || !file.type.startsWith('image/')) {
        alert('画像ファイルを選んでください');
        ri.value = '';
        if (previewImg && originalSrc) previewImg.src = originalSrc;
        if (note) note.style.display = 'none';
        return;
      }

      const url = URL.createObjectURL(file);
      if (previewImg) previewImg.src = url;
      if (note) note.style.display = 'inline';
    });
  });

  document.querySelectorAll('[data-delete-checkbox]').forEach((cb) => {
    cb.addEventListener('change', () => {
      const item = cb.closest('.imageItem');
      if (!item) return;

      const replaceInput = item.querySelector('[data-replace-input]');
      const previewImg = item.querySelector('[data-preview-img]');
      const note = item.querySelector('[data-replace-note]');
      const originalSrc = previewImg ? (previewImg.getAttribute('data-original-src') || '') : '';

      if (cb.checked) {
        if (replaceInput) replaceInput.value = '';
        if (previewImg && originalSrc) previewImg.src = originalSrc;
        if (note) note.style.display = 'none';
      }
    });
  });
})();
</script>
</body>
</html>