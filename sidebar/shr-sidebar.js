/* ==========================================================
 * shiritoru sidebar : shr-sidebar.js
 *
 * 【埋め込み方法】各HTMLの </body> の直前に1行追加
 *   <script src="assets/sidebar/shr-sidebar.js" defer></script>
 *
 * 【現在のページの判定】各HTMLの <head> 内に
 *   <meta name="shr-current" content="about">
 * を書く。content は下の menu の id と一致させる。
 * このmetaが無いページでは、現在地の表示は付かない。
 * ========================================================== */
(function () {
  'use strict';

  /* ===== 設定（ここだけ編集すればOK）===== */
  var CONFIG = {
    home: '../home.html',                                          // ロゴのリンク先
    logo: '../assets/icon/shiritoru_logo_vertical ver.svg',   // ロゴ画像
    siteName: 'shiritoru homepage',                     // 開くボタンの文字
    menu: [
      { id: 'home',    label: 'Home',    href: '../home.html' },
      { id: 'about',   label: 'About',   href: '../about.html' },
      { id: 'bbs',     label: 'BBS',     href: '../bbs/' },
      { id: 'blog',    label: 'Blog',    href: '../blog/' },
      { id: 'gallery', label: 'Gallery', href: '../gallery/' },
      { id: 'sonota',  label: 'Sonota',  href: '../sonota/' }
    ],
    social: [
      { label: 'Twitter (X)', href: 'https://x.com/shiritoru_1', svg: '<path d="M714.163 519.284L1160.89 0H1055.03L667.137 450.887L357.328 0H0L468.492 681.821L0 1226.37H105.866L515.491 750.218L842.672 1226.37H1200L714.137 519.284H714.163ZM569.165 687.828L521.697 619.934L144.011 79.6944H306.615L611.412 515.685L658.88 583.579L1055.08 1150.3H892.476L569.165 687.854V687.828Z"/>', viewBox: '0 0 1200 1227' },
      { label: 'GitHub', href: 'https://github.com/shiritoru-1', svg: '<path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>', viewBox: '0 0 24 24' },
      { label: 'Discord', href: 'https://discord.gg/NzWp9sEA6W', svg: '<path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z"/>', viewBox: '0 0 24 24' }
    ],
    copyright: '©shiritoru'
  };

  /* ===== 現在のページの判定（metaの存在と値で判定）===== */
  var currentMeta = document.querySelector('meta[name="shr-current"]');
  var currentId = currentMeta ? currentMeta.getAttribute('content') : null;

  /* ===== 読み込み元の場所からCSSのパスを作る ===== */
  var self = document.currentScript;
  var cssHref = self && self.src
    ? self.src.replace(/shr-sidebar\.js[^]*$/, 'shr-sidebar.css')
    : 'assets/sidebar/shr-sidebar.css';

  function addOnce(selector, create) {
    if (!document.querySelector(selector)) document.head.appendChild(create());
  }
  function link(href) {
    var el = document.createElement('link');
    el.rel = 'stylesheet';
    el.href = href;
    return el;
  }

  /* フォント & CSS の読み込み */
  addOnce('link[href*="fontshare.com"]', function () {
    return link('https://api.fontshare.com/v2/css?f[]=chillax@200,300,400,500&display=swap');
  });
  addOnce('link[href*="Noto+Sans+JP"]', function () {
    return link('https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500&display=swap');
  });
  addOnce('link[href="' + cssHref + '"]', function () { return link(cssHref); });

  /* ===== 前後ページ ===== */
  function findIndex() {
    for (var i = 0; i < CONFIG.menu.length; i++) {
      if (CONFIG.menu[i].id === currentId) return i;
    }
    return -1;
  }

  /* ===== HTML生成 ===== */
  function buildHTML() {
    var idx = findIndex();
    var cur = idx >= 0 ? CONFIG.menu[idx] : null;
    var prev = idx > 0 ? CONFIG.menu[idx - 1] : null;
    var next = idx >= 0 && idx < CONFIG.menu.length - 1 ? CONFIG.menu[idx + 1] : null;

    var menuHTML = CONFIG.menu.map(function (m) {
      var isCur = m.id === currentId;
      return '<li><a href="' + m.href + '"' + (isCur ? ' aria-current="page"' : '') + '>' + m.label + '</a></li>';
    }).join('');

    var socialHTML = CONFIG.social.map(function (s) {
      return '<li><a href="' + s.href + '" target="_blank" rel="noopener noreferrer" aria-label="' + s.label + '">' +
        '<svg viewBox="' + s.viewBox + '" aria-hidden="true">' + s.svg + '</svg></a></li>';
    }).join('');

    return '' +
      '<aside class="shr-sidebar" id="shrSidebar" aria-label="サイトメニュー">' +
        '<div class="shr-rail">' +
          '<a class="shr-logo" href="' + CONFIG.home + '" aria-label="トップページへ">' +
            '<img src="' + CONFIG.logo + '" alt="" width="26" height="172">' +
          '</a>' +
          '<button class="shr-open" type="button" aria-controls="shrPanel" aria-expanded="false">' +
            '<span class="shr-open__text">' + CONFIG.siteName + '</span>' +
          '</button>' +
          '<button class="shr-burger" type="button" aria-label="メニューを開く" aria-controls="shrPanel" aria-expanded="false">' +
            '<span></span><span></span><span></span>' +
          '</button>' +
          '<div class="shr-pager">' +
            '<p class="shr-pager__title">' + (cur ? cur.label : '') + '</p>' +
            '<div class="shr-pager__nav">' +
              pagerBtn(prev, 'prev', '前のページ') +
              pagerBtn(next, 'next', '次のページ') +
            '</div>' +
          '</div>' +
        '</div>' +
        '<div class="shr-panel" id="shrPanel">' +
          '<nav aria-label="メインナビゲーション"><ul class="shr-menu">' + menuHTML + '</ul></nav>' +
          '<ul class="shr-social">' + socialHTML + '</ul>' +
          '<footer class="shr-footer"><p>' + CONFIG.copyright + '</p></footer>' +
        '</div>' +
      '</aside>' +
      '<div class="shr-overlay" id="shrOverlay"></div>';
  }

  function pagerBtn(item, dir, label) {
    var chevron = '<svg viewBox="0 0 9 9" fill="none" aria-hidden="true"><path d="M0.5 6.5L4.5 2.5L8.5 6.5" stroke="currentColor"/></svg>';
    if (!item) {
      return '<span class="shr-pager__btn" data-dir="' + dir + '" aria-hidden="true" aria-disabled="true">' + chevron + '</span>';
    }
    return '<a class="shr-pager__btn" data-dir="' + dir + '" href="' + item.href + '" aria-label="' + label + '（' + item.label + '）">' + chevron + '</a>';
  }

  /* ===== 開閉 ===== */
  function init() {
    var wrap = document.createElement('div');
    wrap.innerHTML = buildHTML();
    var nodes = Array.prototype.slice.call(wrap.childNodes);
    nodes.forEach(function (n) { document.body.insertBefore(n, document.body.firstChild); });
    // 順序を整える（aside → overlay）
    var aside = document.getElementById('shrSidebar');
    var overlay = document.getElementById('shrOverlay');
    document.body.insertBefore(aside, document.body.firstChild);
    document.body.insertBefore(overlay, aside.nextSibling);

    var openBtns = aside.querySelectorAll('.shr-open, .shr-burger');
    var openTriggers = [aside.querySelector('.shr-open')];
    var burger = aside.querySelector('.shr-burger');

    function setOpen(state) {
      aside.classList.toggle('is-open', state);
      overlay.classList.toggle('is-open', state);
      openBtns.forEach(function (b) {
        b.setAttribute('aria-expanded', state ? 'true' : 'false');
      });
      burger.setAttribute('aria-label', state ? 'メニューを閉じる' : 'メニューを開く');
    }

    // 「shiritoru homepage」の文字をクリック → 開く
    aside.querySelector('.shr-open').addEventListener('click', function () { setOpen(true); });

    // ハンバーガー → 開閉を切り替え
    burger.addEventListener('click', function () { setOpen(!aside.classList.contains('is-open')); });

    // 閉じた状態のレール（余白）をクリック → 開く（ロゴ・ボタン・矢印は除く）
    aside.addEventListener('click', function (e) {
      if (aside.classList.contains('is-open')) return;
      if (e.target.closest('a, button')) return;
      setOpen(true);
    });

    // 開いた状態で、パネル外（オーバーレイ）をクリック → 閉じる
    overlay.addEventListener('click', function () { setOpen(false); });

    // Escキー → 閉じる
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && aside.classList.contains('is-open')) setOpen(false);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
