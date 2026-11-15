<?php
session_start();
require __DIR__ . '/lib.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ----- ログイン -----
$loginError = '';
if (isset($_POST['login'])) {
    if (password_verify((string)($_POST['password'] ?? ''), cfg('admin_password_hash'))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        header('Location: admin.php');
        exit;
    }
    sleep(1);
    $loginError = 'パスワードが違います';
}

if (empty($_SESSION['admin'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>BBS 管理</title>
    <style>
      body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
             background:#f2f2f7; font-family: system-ui, "Hiragino Sans", sans-serif; color:#1d1d1f; }
      .card { background:#fff; border-radius:22px; padding:28px 24px; width:300px; box-shadow:0 10px 30px rgba(0,0,0,.08); }
      h1 { font-size:18px; margin:0 0 14px; }
      input { width:100%; padding:11px 14px; border:0; border-radius:14px; background:#f2f2f7; font:inherit; margin-bottom:10px; }
      button { width:100%; background:#1d1d1f; color:#fff; border:0; border-radius:999px; padding:11px; font:inherit; font-weight:600; cursor:pointer; }
      .err { color:#b3261e; font-size:13px; }
    </style>
    </head>
    <body>
      <form class="card" method="post" action="admin.php">
        <h1>BBS 管理画面</h1>
        <?php if ($loginError !== ''): ?><p class="err"><?= h($loginError) ?></p><?php endif; ?>
        <input type="password" name="password" placeholder="管理用パスワード" required>
        <button type="submit" name="login" value="1">ログイン</button>
      </form>
    </body>
    </html>
    <?php
    exit;
}

// ----- 管理操作 -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        exit('不正なリクエストです');
    }
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'post') {
        try {
            add_post((string)($_POST['name'] ?? ''), (string)($_POST['body'] ?? ''), $_FILES['image'] ?? null, true, null);
            $_SESSION['notice'] = '管理者として書き込みました';
        } catch (BbsException $e) {
            $_SESSION['notice'] = $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $ok = delete_post_by_id((string)($_POST['id'] ?? ''));
        $_SESSION['notice'] = $ok ? '投稿を削除しました' : '投稿が見つかりませんでした';
    } elseif ($action === 'prune') {
        $n = with_lock(function () {
            $posts = load_posts();
            $n = prune_old_images($posts);
            save_posts($posts);
            return $n;
        });
        $_SESSION['notice'] = "容量チェックを実行しました（削除した画像: {$n}件）";
    }

    header('Location: admin.php');
    exit;
}

// ----- 表示 -----
$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);

$posts = load_posts();
$imageCount = count(array_filter($posts, fn ($p) => !empty($p['image'])));
$usage = image_storage_bytes($posts);
$limit = cfg('storage_limit_bytes');
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>BBS 管理</title>
<style>
:root { --ink:#1d1d1f; --sub:#8e8e93; --bg:#f2f2f7; --card:#fff; --navy:rgb(17,21,54); }
* { box-sizing:border-box; }
body { margin:0; font-family: system-ui, "Hiragino Sans", "Yu Gothic", sans-serif; background:var(--bg); color:var(--ink); font-size:14px; line-height:1.6; }
header { position:sticky; top:0; z-index:5; background:rgba(242,242,247,.85); backdrop-filter:blur(16px); padding:14px 16px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(0,0,0,.06); }
header h1 { margin:0; font-size:17px; }
header a { color:var(--sub); font-size:13px; text-decoration:none; }
.wrap { max-width:720px; margin:0 auto; padding:14px 14px 40px; }
.card { background:var(--card); border-radius:20px; padding:16px; margin-bottom:12px; box-shadow:0 2px 10px rgba(0,0,0,.04); }
.card h2 { font-size:13px; color:var(--sub); margin:0 0 10px; font-weight:600; letter-spacing:.04em; }
.notice { background:#fff; border-radius:14px; padding:10px 14px; margin-bottom:12px; font-weight:600; box-shadow:0 2px 10px rgba(0,0,0,.04); }
input[type=text], textarea { width:100%; padding:10px 12px; border:0; border-radius:14px; background:var(--bg); font:inherit; margin-bottom:8px; }
textarea { min-height:80px; resize:vertical; }
input[type=file] { font-size:13px; margin-bottom:8px; }
button { background:var(--ink); color:#fff; border:0; border-radius:999px; padding:8px 18px; font:inherit; font-weight:600; cursor:pointer; }
button.ghost { background:var(--bg); color:var(--ink); }
button.danger { background:#fdecea; color:#b3261e; padding:6px 14px; font-size:13px; }
.stats { display:flex; gap:16px; flex-wrap:wrap; font-size:13px; color:var(--sub); margin-bottom:10px; }
.stats b { color:var(--ink); font-size:15px; }
.item { padding:12px 0; }
.item + .item { border-top:1px solid rgba(0,0,0,.06); }
.meta { font-size:12px; color:var(--sub); display:flex; flex-wrap:wrap; gap:4px 8px; align-items:center; }
.meta .no { color:var(--ink); font-weight:700; }
.badge { background:var(--navy); color:#fff; border-radius:999px; padding:1px 8px; font-size:11px; font-weight:700; }
.row { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-top:4px; }
.body { word-break:break-word; }
</style>
</head>
<body>
<header>
  <h1>BBS 管理</h1>
  <a href="admin.php?logout=1">ログアウト</a>
</header>

<div class="wrap">

  <?php if ($notice !== ''): ?><div class="notice"><?= h($notice) ?></div><?php endif; ?>

  <section class="card">
    <h2>管理者として書き込む</h2>
    <form method="post" action="admin.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="post">
      <input type="text" name="name" maxlength="20" placeholder="名前（省略で「名無しさん」）">
      <textarea name="body" required maxlength="1000" placeholder="本文"></textarea>
      <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
      <button type="submit">書き込む</button>
    </form>
  </section>

  <section class="card">
    <h2>容量</h2>
    <div class="stats">
      <span>投稿 <b><?= count($posts) ?></b></span>
      <span>画像 <b><?= $imageCount ?></b></span>
      <span>画像合計 <b><?= h(format_bytes($usage)) ?></b> / <?= h(format_bytes($limit)) ?></span>
    </div>
    <form method="post" action="admin.php">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="prune">
      <button type="submit" class="ghost">今すぐ容量チェック</button>
    </form>
  </section>

  <section class="card">
    <h2>書き込み一覧（新しい順）</h2>
    <?php if (empty($posts)): ?>
      <p class="meta">書き込みはまだありません。</p>
    <?php endif; ?>
    <?php for ($i = count($posts) - 1; $i >= 0; $i--): $p = $posts[$i]; ?>
      <div class="item">
        <div class="meta">
          <span class="no">#<?= (int)$p['no'] ?></span>
          <span><?= h($p['name']) ?></span>
          <span><?= h($p['created']) ?></span>
          <?php if (!empty($p['is_admin'])): ?><span class="badge">管理者</span>
          <?php elseif (!empty($p['author_id'])): ?><span>ID:<?= h($p['author_id']) ?></span><?php endif; ?>
          <?php if (!empty($p['image'])): ?><a href="uploads/<?= h($p['image']) ?>" target="_blank" rel="noopener">画像</a>
          <?php elseif (!empty($p['image_removed'])): ?><span>画像は削除済み</span><?php endif; ?>
        </div>
        <div class="row">
          <div class="body"><?= h(mb_strimwidth($p['body'], 0, 140, '…')) ?></div>
          <form method="post" action="admin.php" onsubmit="return confirm('この投稿を削除しますか？');">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($p['id']) ?>">
            <button type="submit" class="danger">削除</button>
          </form>
        </div>
      </div>
    <?php endfor; ?>
  </section>

</div>
</body>
</html>