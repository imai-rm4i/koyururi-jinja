/* ======================
  Koyururi Shrine Scripts
  Author: TB
====================== */

/* ======================
  Slide-in SP Menu (right)
====================== */
(function(){
  const menuBtn = document.querySelector('.menu-btn');
  const menuImg = menuBtn ? menuBtn.querySelector('img') : null;

  const spNav   = document.getElementById('spNav');
  const closeBtn = spNav ? spNav.querySelector('.sp-close') : null;
  const panel = spNav ? spNav.querySelector('.sp-panel') : null;

  const openSrc = menuBtn ? menuBtn.getAttribute('data-open-src') : '';
  const closeSrc = menuBtn ? menuBtn.getAttribute('data-close-src') : '';

  const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
  let lastFocused = null;

  function setMenuIcon(isOpen){
    if(!menuImg) return;
    if(isOpen){
      menuImg.src = closeSrc || menuImg.src;
      menuBtn.setAttribute('aria-label', 'メニューを閉じる');
    }else{
      menuImg.src = openSrc || menuImg.src;
      menuBtn.setAttribute('aria-label', 'メニューを開く');
    }
  }

  function openMenu(){
    if(!spNav) return;

    lastFocused = document.activeElement;

    spNav.classList.add('is-open');
    spNav.setAttribute('aria-hidden', 'false');
    menuBtn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('is-locked');

    setMenuIcon(true);

    const first = spNav.querySelector(focusableSelector);
    if(first) first.focus();
  }

  function closeMenu(){
    if(!spNav) return;

    spNav.classList.remove('is-open');
    spNav.setAttribute('aria-hidden', 'true');
    menuBtn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('is-locked');

    setMenuIcon(false);

    if(lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
  }

  function isOpen(){
    return spNav && spNav.classList.contains('is-open');
  }

  if(menuBtn){
    menuBtn.addEventListener('click', function(){
      if(isOpen()) closeMenu();
      else openMenu();
    });
  }

  if(closeBtn){
    closeBtn.addEventListener('click', closeMenu);
  }

  if(spNav){
    spNav.addEventListener('click', function(e){
      if(e.target === spNav) closeMenu();
    });
  }

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && isOpen()){
      e.preventDefault();
      closeMenu();
    }
  });

  document.addEventListener('keydown', function(e){
    if(!isOpen()) return;
    if(e.key !== 'Tab') return;

    const focusables = Array.from(spNav.querySelectorAll(focusableSelector))
      .filter(el => el.offsetParent !== null);
    if(focusables.length === 0) return;

    const first = focusables[0];
    const last  = focusables[focusables.length - 1];

    if(e.shiftKey && document.activeElement === first){
      e.preventDefault();
      last.focus();
    }else if(!e.shiftKey && document.activeElement === last){
      e.preventDefault();
      first.focus();
    }
  });

  if(spNav){
    spNav.querySelectorAll('a[href^="#"]').forEach(function(link){
      link.addEventListener('click', function(){
        closeMenu();
      });
    });
  }

  if(panel){
    panel.addEventListener('click', function(e){
      e.stopPropagation();
    });
  }

  setMenuIcon(false);
})();


/* ======================
  お知らせ読み込み（news.json → 最新3件表示）
====================== */
(function(){
  const container = document.getElementById('newsList');
  if(!container) return;

  const MAX_ITEMS = 3;

  function escapeHtml(str){
    if(!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatDate(dateStr){
    if(!dateStr) return '';
    // "2026-01-05" → "2026年1月5日"
    const parts = dateStr.split('-');
    if(parts.length !== 3) return dateStr;
    const y = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10);
    const d = parseInt(parts[2], 10);
    return y + '年' + m + '月' + d + '日';
  }

  fetch('./news.json?t=' + Date.now())
    .then(function(res){
      if(!res.ok) throw new Error('news.json not found');
      return res.json();
    })
    .then(function(news){
      if(!Array.isArray(news) || news.length === 0){
        container.innerHTML = '<p class="news-empty">現在お知らせはありません</p>';
        return;
      }

      // 日時（datetime）または日付（date）で降順ソート
      news.sort(function(a, b){
        const aTime = a.datetime || a.date || '';
        const bTime = b.datetime || b.date || '';
        return bTime.localeCompare(aTime);
      });

      // 最新3件を取得
      const items = news.slice(0, MAX_ITEMS);

      let html = '';
      items.forEach(function(item){
        const date = formatDate(item.date || '');
        const title = escapeHtml(item.title || '');
        const body = escapeHtml(item.body || '');
        const images = item.images || [];
        const thumb = images.length > 0 ? images[0].replace(/\\\//g, '/') : '';

        html += '<article class="news-item">';
        if(thumb) html += '<div class="news-thumb"><img src="./' + escapeHtml(thumb) + '" alt=""></div>';
        html += '<div class="news-content">';
        if(date) html += '<div class="news-date">' + date + '</div>';
        if(title) html += '<div class="news-title">' + title + '</div>';
        if(body) html += '<div class="news-body">' + body + '</div>';
        html += '</div>';
        html += '</article>';
      });

      container.innerHTML = html;
    })
    .catch(function(){
      container.innerHTML = '<p class="news-empty">お知らせを読み込めませんでした</p>';
    });
})();


/* ======================
  Accordion（Q&A用）
====================== */
(function(){
  const accordions = document.querySelectorAll('.accordion-header');
  if(!accordions.length) return;

  accordions.forEach(function(btn){
    btn.addEventListener('click', function(){
      const item = btn.closest('.accordion-item');
      if(!item) return;

      const isOpen = item.classList.contains('is-open');

      // アコーディオンで「1つ開いたら他を閉じる」動作にしたい場合は以下を有効化
      // document.querySelectorAll('.accordion-item.is-open').forEach(function(openItem){
      //   if(openItem !== item){
      //     openItem.classList.remove('is-open');
      //     openItem.querySelector('.accordion-header').setAttribute('aria-expanded', 'false');
      //   }
      // });

      // トグル
      item.classList.toggle('is-open', !isOpen);
      btn.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
    });
  });
})();


/* ======================
  Back to Top
====================== */
(function(){
  const btn = document.getElementById('backToTop');
  if(!btn) return;

  function toggle(){
    if(window.scrollY > 220){
      btn.classList.add('is-show');
    }else{
      btn.classList.remove('is-show');
    }
  }

  window.addEventListener('scroll', toggle, { passive: true });
  window.addEventListener('load', toggle);

  btn.addEventListener('click', function(){
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();
