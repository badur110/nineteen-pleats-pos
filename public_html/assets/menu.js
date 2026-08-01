(function () {
  'use strict';

  const initial = window.__GARBALIA_MENU__ || {categories: [], version: '', restaurant: {}};
  const state = {
    data: initial,
    category: 'all',
    search: '',
    lastRefresh: Date.now()
  };

  const els = {
    categories: document.getElementById('menuCategories'),
    sections: document.getElementById('menuSections'),
    search: document.getElementById('menuSearch'),
    clear: document.getElementById('menuSearchClear'),
    empty: document.getElementById('menuEmpty'),
    status: document.getElementById('menuStatusText'),
    share: document.getElementById('menuShare'),
    toast: document.getElementById('menuToast'),
    top: document.getElementById('menuTop')
  };

  let toastTimer = null;
  let observer = null;

  function formatPrice(value) {
    const number = Number(value || 0);
    return number.toLocaleString('ka-GE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }

  function normalize(value) {
    return String(value || '').toLocaleLowerCase('ka-GE').trim();
  }

  function showToast(message) {
    if (!els.toast) return;
    els.toast.textContent = message;
    els.toast.classList.add('is-visible');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () {
      els.toast.classList.remove('is-visible');
    }, 2400);
  }

  function categoryProductCount() {
    return (state.data.categories || []).reduce(function (sum, category) {
      return sum + Number(category.count || (category.products || []).length || 0);
    }, 0);
  }

  function createCategoryButton(id, name, initial, count) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'menu-category-button' + (state.category === id ? ' is-active' : '');
    button.dataset.category = id;
    button.setAttribute('aria-pressed', state.category === id ? 'true' : 'false');

    const icon = document.createElement('span');
    icon.textContent = initial;
    const label = document.createTextNode(name + ' · ' + count);
    button.appendChild(icon);
    button.appendChild(label);

    button.addEventListener('click', function () {
      state.category = id;
      render();
      const toolbar = document.querySelector('.menu-toolbar');
      if (toolbar && window.scrollY > toolbar.offsetTop + 120) {
        toolbar.scrollIntoView({behavior: 'smooth', block: 'start'});
      }
    });
    return button;
  }

  function renderCategories() {
    if (!els.categories) return;
    els.categories.innerHTML = '';
    els.categories.appendChild(createCategoryButton('all', 'ყველა', '★', categoryProductCount()));
    (state.data.categories || []).forEach(function (category) {
      els.categories.appendChild(createCategoryButton(
        category.id,
        category.name,
        category.initial || '•',
        category.count || (category.products || []).length
      ));
    });
  }

  function productMatches(product, category) {
    if (state.category !== 'all' && state.category !== category.id) return false;
    if (!state.search) return true;
    return normalize(product.name + ' ' + category.name).indexOf(state.search) !== -1;
  }

  function createProductCard(product, category) {
    const card = document.createElement('article');
    card.className = 'menu-product-card menu-reveal';
    card.dataset.tone = String(category.tone || 0);

    const visual = document.createElement('div');
    visual.className = 'menu-product-visual';
    const initial = document.createElement('span');
    initial.textContent = category.initial || '•';
    visual.appendChild(initial);

    const copy = document.createElement('div');
    copy.className = 'menu-product-copy';
    const categoryText = document.createElement('span');
    categoryText.className = 'menu-product-category';
    categoryText.textContent = category.name;
    const title = document.createElement('h3');
    title.textContent = product.name;
    const price = document.createElement('div');
    price.className = 'menu-product-price';
    price.appendChild(document.createTextNode(formatPrice(product.price)));
    const currency = document.createElement('small');
    currency.textContent = '₾';
    price.appendChild(currency);

    copy.appendChild(categoryText);
    copy.appendChild(title);
    copy.appendChild(price);
    card.appendChild(visual);
    card.appendChild(copy);

    if (window.matchMedia('(pointer:fine)').matches) {
      card.addEventListener('mousemove', function (event) {
        const rect = card.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width - 0.5;
        const y = (event.clientY - rect.top) / rect.height - 0.5;
        card.style.setProperty('--ry', (x * 5).toFixed(2) + 'deg');
        card.style.setProperty('--rx', (-y * 4).toFixed(2) + 'deg');
      });
      card.addEventListener('mouseleave', function () {
        card.style.setProperty('--ry', '0deg');
        card.style.setProperty('--rx', '0deg');
      });
    }

    return card;
  }

  function createSection(category, products) {
    const section = document.createElement('section');
    section.className = 'menu-section';
    section.id = 'menu-' + category.id;

    const head = document.createElement('div');
    head.className = 'menu-section-head';
    const titleWrap = document.createElement('div');
    titleWrap.className = 'menu-section-title';
    const orb = document.createElement('span');
    orb.className = 'menu-section-orb';
    orb.dataset.tone = String(category.tone || 0);
    orb.textContent = category.initial || '•';
    const text = document.createElement('div');
    const title = document.createElement('h2');
    title.textContent = category.name;
    const hint = document.createElement('p');
    hint.textContent = 'ყოველთვის აქტუალური ფასი';
    text.appendChild(title);
    text.appendChild(hint);
    titleWrap.appendChild(orb);
    titleWrap.appendChild(text);
    const count = document.createElement('span');
    count.className = 'menu-section-count';
    count.textContent = products.length + ' პროდუქტი';
    head.appendChild(titleWrap);
    head.appendChild(count);

    const grid = document.createElement('div');
    grid.className = 'menu-product-grid';
    products.forEach(function (product) {
      grid.appendChild(createProductCard(product, category));
    });

    section.appendChild(head);
    section.appendChild(grid);
    return section;
  }

  function observeCards() {
    if (observer) observer.disconnect();
    if (!('IntersectionObserver' in window)) {
      document.querySelectorAll('.menu-reveal').forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          observer.unobserve(entry.target);
        }
      });
    }, {rootMargin: '0px 0px -30px 0px', threshold: 0.05});
    document.querySelectorAll('.menu-reveal').forEach(function (el) { observer.observe(el); });
  }

  function renderProducts() {
    if (!els.sections) return;
    els.sections.innerHTML = '';
    let visibleCount = 0;
    const fragment = document.createDocumentFragment();

    (state.data.categories || []).forEach(function (category) {
      const products = (category.products || []).filter(function (product) {
        return productMatches(product, category);
      });
      if (!products.length) return;
      visibleCount += products.length;
      fragment.appendChild(createSection(category, products));
    });

    els.sections.appendChild(fragment);
    if (els.empty) els.empty.classList.toggle('is-visible', visibleCount === 0);
    observeCards();
  }

  function render() {
    renderCategories();
    renderProducts();
    if (els.clear) els.clear.hidden = !state.search;
  }

  function updateStatus(label) {
    if (els.status) els.status.textContent = label;
  }

  async function refreshData(options) {
    const silent = options && options.silent;
    if (!silent) updateStatus('ახლდება…');
    try {
      const response = await fetch('/menu-data?ts=' + Date.now(), {
        cache: 'no-store',
        credentials: 'same-origin',
        headers: {'Accept': 'application/json'}
      });
      if (!response.ok) throw new Error('Menu refresh failed');
      const next = await response.json();
      if (!next || next.ok === false || !Array.isArray(next.categories)) throw new Error('Invalid menu response');
      state.lastRefresh = Date.now();
      if (next.version !== state.data.version) {
        state.data = next;
        render();
        showToast('მენიუ და ფასები განახლდა');
      }
      updateStatus('ფასები განახლებულია');
    } catch (error) {
      updateStatus('ონლაინ მენიუ');
      if (!silent) showToast('განახლება ვერ მოხერხდა — ნაჩვენებია ბოლო მონაცემები');
    }
  }

  async function shareMenu() {
    const restaurant = state.data.restaurant || {};
    const shareData = {
      title: (restaurant.name || 'GARBALIA') + ' — QR მენიუ',
      text: 'იხილე ჩვენი მენიუ და ფასები',
      url: window.location.href
    };
    try {
      if (navigator.share) {
        await navigator.share(shareData);
        return;
      }
      await navigator.clipboard.writeText(window.location.href);
      showToast('მენიუს ბმული დაკოპირდა');
    } catch (error) {
      if (error && error.name === 'AbortError') return;
      showToast('ბმულის გაზიარება ვერ მოხერხდა');
    }
  }

  function bind() {
    if (els.search) {
      els.search.addEventListener('input', function () {
        state.search = normalize(els.search.value);
        render();
      });
    }
    if (els.clear) {
      els.clear.addEventListener('click', function () {
        if (els.search) {
          els.search.value = '';
          els.search.focus();
        }
        state.search = '';
        render();
      });
    }
    if (els.share) els.share.addEventListener('click', shareMenu);
    if (els.top) {
      els.top.addEventListener('click', function () { window.scrollTo({top: 0, behavior: 'smooth'}); });
      window.addEventListener('scroll', function () {
        els.top.classList.toggle('is-visible', window.scrollY > 520);
      }, {passive: true});
    }

    document.addEventListener('visibilitychange', function () {
      if (!document.hidden && Date.now() - state.lastRefresh > 60000) refreshData({silent: true});
    });
    window.addEventListener('online', function () { refreshData({silent: true}); });
  }

  bind();
  render();
  updateStatus('ფასები განახლებულია');
  window.setInterval(function () { refreshData({silent: true}); }, 5 * 60 * 1000);
})();
