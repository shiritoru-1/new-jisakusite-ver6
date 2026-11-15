<?php
session_start();
require __DIR__ . '/lib.php';

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// 日付が変わるとIDも変わる（IPアドレスは保存せず、ハッシュの一部だけ使う）
function make_author_id(): string
{
    $key = cfg('id_secret') . date('Y-m-d');
    return substr(hash_hmac('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''), $key), 0, 8);
}

// ----- 書き込み処理 -----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $now = time();
    $wait = cfg('post_interval_sec') - ($now - ($_SESSION['last_post'] ?? 0));
    if ($wait > 0) {
        flash('err', "連続投稿の制限中です。あと{$wait}秒待ってから書き込んでください");
    } else {
        try {
            add_post(
                (string)($_POST['name'] ?? ''),
                (string)($_POST['body'] ?? ''),
                $_FILES['image'] ?? null,
                false,
                make_author_id()
            );
            $_SESSION['last_post'] = $now;
            flash('ok', '書き込みました');
        } catch (BbsException $e) {
            flash('err', $e->getMessage());
        }
    }
    header('Location: index.php#form');
    exit;
}

// ----- 表示データ（新しい投稿を上に） -----
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$posts = load_posts();
$total = count($posts);
$perPage = cfg('posts_per_page');
$showAll = isset($_GET['all']);
$visible = ($showAll || $total <= $perPage) ? $total : $perPage;
$lastPost = $total > 0 ? $posts[$total - 1]['created'] : null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BBS</title>
<link rel="preconnect" href="https://api.fontshare.com">
<link href="https://api.fontshare.com/v2/css?f[]=chillax@200,300,400,500,600,700&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --ink: #141414;
  --mid: #5c5c5c;
  --sub: #9a9a9a;
  --hair: #dcdcdc;
  --wall: #fafafa;
  --navy: rgb(17, 21, 54);
  --ok: #2f6b45;
  --err: #a3261e;
  --ease: cubic-bezier(.22, .61, .36, 1);
}
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fff; }
body {
  height: 100vh;
  overflow: hidden;
  color: var(--ink);
  font-family: "Chillax", "Noto Sans JP", "Hiragino Sans", "Yu Gothic", system-ui, sans-serif;
  font-weight: 400;
  font-size: 14px;
  line-height: 1.8;
  letter-spacing: .02em;
  -webkit-font-smoothing: antialiased;
}

/* ----- 全体（展示室：左に解説パネル、右に作品） ----- */
.main {
  height: 100vh;
  display: grid;
  grid-template-columns: minmax(300px, 380px) 1fr;
}

/* 左：タイトルと入場パネル（固定） */
.side {
  height: 100vh;
  overflow-y: auto;
  padding: 56px 44px 56px;
  border-right: 1px solid var(--hair);
  background: var(--wall);
}
.eyebrow {
  margin: 0 0 18px; font-size: 11px; font-weight: 500; letter-spacing: .32em; color: var(--sub);
  text-transform: uppercase;
}
.title {
  margin: 0; font-size: 44px; font-weight: 300; letter-spacing: .14em; line-height: 1;
}
.rule { width: 36px; height: 1px; background: var(--ink); margin: 24px 0 20px; }
.desc { margin: 0; font-size: 13px; color: var(--mid); line-height: 1.9; }
.meta {
  margin: 28px 0 40px; display: flex; flex-direction: column; gap: 6px;
  font-size: 11px; color: var(--sub); letter-spacing: .12em;
}
.meta b { color: var(--ink); font-weight: 500; font-size: 13px; letter-spacing: .06em; }

/* 入場パネル（書き込みフォーム） */
.label { font-size: 11px; letter-spacing: .28em; color: var(--sub); margin: 0 0 16px; text-transform: uppercase; }
.composer { display: flex; flex-direction: column; gap: 22px; }
.field { display: block; }
.field__label {
  display: block; font-size: 11px; color: var(--sub); letter-spacing: .18em; margin-bottom: 4px;
}
.field input[type=text], .field textarea {
  display: block; width: 100%; border: 0; border-bottom: 1px solid var(--hair);
  border-radius: 0; outline: 0; background: transparent; font: inherit; color: var(--ink);
  padding: 6px 0; transition: border-color .4s var(--ease);
}
.field input[type=text]:focus, .field textarea:focus { border-bottom-color: var(--ink); }
.field textarea { min-height: 120px; resize: vertical; line-height: 1.8; }
.field input[type=text]::placeholder, .field textarea::placeholder { color: #c2c2c2; }
.field input[type=file] { font-size: 12px; color: var(--mid); width: 100%; }

/* 注意書き（昔の掲示板風） */
.notice {
  font-family: "MS PGothic", "Noto Sans JP", "Hiragino Sans", sans-serif;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
  letter-spacing: 0;
  color: #b00020;
  border: 2px dashed #b00020;
  background: #fff8f8;
  padding: 6px 8px;
  margin: 4px 0 0;
  animation: notice-blink 1.6s steps(1, end) infinite;
}
@keyframes notice-blink {
  50% { border-color: transparent; }
}

.send {
  align-self: flex-start;
  background: transparent; color: var(--ink); border: 1px solid var(--ink); border-radius: 0;
  padding: 10px 30px; font: inherit; font-size: 12px; letter-spacing: .24em; cursor: pointer;
  transition: background .4s var(--ease), color .4s var(--ease);
}
.send:hover { background: var(--ink); color: #fff; }
.send:disabled { opacity: .4; cursor: default; }

.flash { font-size: 12px; padding: 10px 12px; margin: 0 0 8px; letter-spacing: .04em; border-left: 2px solid; }
.flash.ok { color: var(--ok); border-color: var(--ok); }
.flash.err { color: var(--err); border-color: var(--err); }

/* 右：展示（スクロール） */
.feed { height: 100vh; overflow-y: auto; padding: 56px 64px 120px; }
.feed-inner { max-width: 820px; }
.count {
  display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
  font-size: 11px; color: var(--sub); letter-spacing: .18em; margin: 0 0 56px;
  padding-bottom: 14px; border-bottom: 1px solid var(--hair);
}
.count a { color: var(--ink); text-decoration: none; border-bottom: 1px solid var(--ink); padding-bottom: 1px; }
.empty { color: var(--sub); padding: 80px 0; font-size: 13px; letter-spacing: .08em; text-align: center; }

/* 作品（キャプション） */
.post {
  padding: 0 0 72px; scroll-margin-top: 40px;
  animation: reveal 1.1s var(--ease) both;
  animation-timeline: view();
}
@supports not (animation-timeline: view()) {
  .post { animation: none; }
}
@keyframes reveal {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: none; }
}
.post:target .post-head { border-top-color: var(--navy); }
.post-head {
  display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 14px;
  padding-top: 14px; border-top: 1px solid var(--ink);
  font-size: 11px; color: var(--sub); letter-spacing: .14em;
}
.no { color: var(--ink); font-size: 12px; font-weight: 500; letter-spacing: .16em; text-decoration: none; }
.no:hover { text-decoration: underline; text-underline-offset: 3px; }
.name { color: var(--ink); font-size: 14px; font-weight: 500; letter-spacing: .06em; }
.date { font-size: 11px; color: var(--sub); }
.id { font-size: 11px; color: var(--mid); letter-spacing: .08em; }
.body { margin-top: 18px; font-size: 15px; line-height: 2; word-break: break-word; max-width: 62ch; }
.body a { color: var(--ink); text-decoration: underline; text-underline-offset: 4px; text-decoration-color: var(--sub); }
a.reply { color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--navy); }
.post img {
  display: block; max-width: 300px; max-height: 300px; height: auto;
  margin-top: 22px; box-shadow: 0 1px 0 var(--hair), 0 18px 40px -22px rgba(0,0,0,.35);
}
.removed { display: inline-block; margin-top: 16px; font-size: 11px; color: var(--sub); letter-spacing: .08em; }

/* 管理者の目印（王冠の札） */
.crown {
  position: relative; display: inline-flex; align-items: center; gap: 8px; margin-top: 22px;
  background: var(--navy); color: #fff; padding: 5px 12px 5px 10px;
  font-size: 10px; font-weight: 500; letter-spacing: .24em; cursor: help; outline: none;
}
.crown svg { display: block; }
.crown::after {
  content: attr(data-tip); position: absolute; left: 0; bottom: 135%; white-space: nowrap;
  background: var(--ink); color: #fff; font-size: 11px; letter-spacing: .04em; padding: 5px 10px;
  opacity: 0; transform: translateY(4px); pointer-events: none; transition: opacity .3s var(--ease), transform .3s var(--ease);
}
.crown:hover::after, .crown:focus::after { opacity: 1; transform: none; }

/* 送信アニメーション（封筒が飛ぶ） */
.envelope {
  position: fixed; width: 44px; height: 30px; z-index: 99; pointer-events: none;
  animation: fly .85s cubic-bezier(.5, 0, .8, .4) forwards;
}
.envelope svg { width: 100%; height: 100%; display: block; }
@keyframes fly {
  0%   { transform: translate(0, 0) scale(1) rotate(0deg); opacity: 1; }
  60%  { opacity: 1; }
  100% { transform: translate(var(--dx, 0), -110vh) scale(.35) rotate(-12deg); opacity: 0; }
}

/* 狭い画面：縦に積む */
@media (max-width: 899px) {
  body { height: auto; overflow: auto; }
  .main { grid-template-columns: 1fr; height: auto; }
  .side { height: auto; border-right: 0; border-bottom: 1px solid var(--hair); padding: 40px 24px; }
  .feed { height: auto; overflow: visible; padding: 40px 24px 60px; }
  .feed-inner { max-width: none; }
  .side.is-hidden { display: none; }
}
@media (max-width: 480px) {
  .title { font-size: 34px; }
}
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation: none !important; transition-duration: .01s !important; }
}
</style>
</head>
<body id="top">

<div class="main">

  <aside class="side" id="side">
    <p class="eyebrow">Bulletin Board</p>
    <h1 class="title">BBS</h1>
    <div class="rule"></div>
    <p class="desc"><?= h(cfg('site_description')) ?></p>

    <div class="meta">
      <span>収蔵 <b><?= $total ?></b> 件</span>
      <?php if ($lastPost): ?><span>最終更新 <b><?= h($lastPost) ?></b></span><?php endif; ?>
    </div>

    <p class="label">Submit</p>

    <?php if ($flash): ?>
      <div class="flash <?= $flash['type'] === 'ok' ? 'ok' : 'err' ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>

    <form class="composer" id="form" method="post" action="index.php" enctype="multipart/form-data">
      <label class="field">
        <span class="field__label">NAME</span>
        <input type="text" name="name" maxlength="20" placeholder="名無しさん">
      </label>
      <label class="field">
        <span class="field__label">MESSAGE</span>
        <textarea name="body" required maxlength="1000"></textarea>
      </label>
      <label class="field">
        <span class="field__label">IMAGE（任意・1枚まで）</span>
        <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
      </label>
      <p class="notice">【注意】住所・電話番号・本名など、個人情報は書き込まないでください。</p>
      <button type="submit" class="send" id="sendBtn">書き込む</button>
    </form>
  </aside>

  <main class="feed" id="feed">
    <div class="feed-inner">

      <?php if ($total > 0): ?>
        <div class="count">
          <span>新しい順</span>
          <?php if ($visible < $total): ?>
            <span>直近 <?= $visible ?> 件を展示中</span>
            <a href="index.php?all=1#top">すべて見る</a>
          <?php elseif ($showAll && $total > $perPage): ?>
            <a href="index.php#top">直近 <?= $perPage ?> 件に戻す</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="empty">展示はまだ空です。最初の作品をどうぞ。</div>
      <?php endif; ?>

      <?php for ($i = $total - 1; $i >= $total - $visible; $i--): $p = $posts[$i]; ?>
        <article class="post" id="p<?= (int)$p['no'] ?>">
          <div class="post-head">
            <a class="no" href="#p<?= (int)$p['no'] ?>">No.<?= str_pad((string)(int)$p['no'], 3, '0', STR_PAD_LEFT) ?></a>
            <span class="name"><?= h($p['name']) ?></span>
            <?php if (!empty($p['author_id']) && empty($p['is_admin'])): ?>
              <span class="id">ID:<?= h($p['author_id']) ?></span>
            <?php endif; ?>
            <span class="date"><?= h($p['created']) ?></span>
          </div>

          <div class="body"><?= render_body($p['body']) ?></div>

          <?php if (!empty($p['image'])): ?>
            <a href="uploads/<?= h($p['image']) ?>" target="_blank" rel="noopener">
              <img src="uploads/<?= h($p['image']) ?>" alt="投稿画像" loading="lazy">
            </a>
          <?php elseif (!empty($p['image_removed'])): ?>
            <div class="removed">※容量管理のため画像を削除しました</div>
          <?php endif; ?>

          <?php if (!empty($p['is_admin'])): ?>
            <div class="crown" tabindex="0" data-tip="管理者による書き込みです">
              <svg width="13" height="10" viewBox="0 0 16 12" aria-hidden="true"><path d="M1 11 L2 3 L5.5 6.5 L8 1 L10.5 6.5 L14 3 L15 11 Z" fill="currentColor"/></svg>
              ADMIN
            </div>
          <?php endif; ?>
        </article>
      <?php endfor; ?>

    </div>
  </main>

</div>

<script>
(function () {
  // 狭い画面では、下にスクロールしたら入力欄を隠す
  var side = document.getElementById('side');
  var last = window.scrollY;
  var narrow = window.matchMedia('(max-width: 899px)');
  window.addEventListener('scroll', function () {
    if (!narrow.matches) { side.classList.remove('is-hidden'); return; }
    var y = window.scrollY;
    if (y > last + 6 && y > 160) side.classList.add('is-hidden');
    else if (y < last - 6) side.classList.remove('is-hidden');
    last = y;
  }, { passive: true });

  // 書き込み時に封筒を飛ばしてから送信
  var formEl = document.getElementById('form');
  var btn = document.getElementById('sendBtn');
  formEl.addEventListener('submit', function (e) {
    if (formEl.dataset.sending === '1') return;
    e.preventDefault();
    formEl.dataset.sending = '1';
    btn.disabled = true;
    btn.textContent = 'SENDING…';

    var r = btn.getBoundingClientRect();
    var env = document.createElement('div');
    env.className = 'envelope';
    env.style.left = (r.left + r.width / 2 - 22) + 'px';
    env.style.top = r.top + 'px';
    env.style.setProperty('--dx', (Math.random() * 80 - 40) + 'px');
    env.innerHTML = '<svg viewBox="0 0 44 30"><rect x="1" y="1" width="42" height="28" fill="#fff" stroke="#141414" stroke-width="1.2"/><path d="M1.5 3 L22 17 L42.5 3" fill="none" stroke="#141414" stroke-width="1.2" stroke-linejoin="round"/></svg>';
    document.body.appendChild(env);
    setTimeout(function () { formEl.submit(); }, 620);
  });
})();
</script>
</body>
</html>