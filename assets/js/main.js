// FILE: assets/js/main.js
document.addEventListener('DOMContentLoaded', function () {
  const body = document.body;

  const mainBtn = document.querySelector('.single_add_to_cart_button');
  const stickyBtn = document.getElementById('sticky-add-btn');
  const sticky = document.querySelector('.sticky-add');
  const variationForm = document.querySelector('.variations_form');
  const mainImage = document.getElementById('product-main-image');
  const thumbs = document.querySelectorAll('.product-thumb');

  const cartDrawer = document.getElementById('cart-drawer');
  const cartOverlay = cartDrawer ? cartDrawer.querySelector('.cart-drawer__overlay') : null;
  const cartCloseBtn = cartDrawer ? cartDrawer.querySelector('.cart-drawer__close') : null;
  const cartTrigger = document.getElementById('header-cart-trigger');

  const megaMenuDesktop = document.getElementById('kangoo-mega-menu-desktop');
  const megaDrawer = document.getElementById('kangoo-mega-menu-drawer');
  const megaOpenBtn = document.getElementById('header-menu-toggle');
  const megaCloseButtons = document.querySelectorAll('[data-mega-menu-close]');
  const megaPanelTriggers = document.querySelectorAll('[data-mega-panel-trigger]');
  const megaPanels = document.querySelectorAll('[data-mega-panel]');

  function initUrlCoupon() {
    if (!window.kangooRewards || !window.kangooRewards.ajax_url || !window.kangooRewards.url_coupon_nonce) {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    const coupon = params.get('coupon');

    if (!coupon) {
      return;
    }

    params.delete('coupon');
    const cleanQuery = params.toString();
    const cleanUrl = window.location.pathname + (cleanQuery ? `?${cleanQuery}` : '') + window.location.hash;

    const formData = new FormData();
    formData.set('action', 'kangoo_apply_url_coupon');
    formData.set('nonce', window.kangooRewards.url_coupon_nonce);
    formData.set('coupon', coupon);

    fetch(window.kangooRewards.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).catch(function (error) {
      console.warn('[Kangoo] Unable to apply URL coupon', error);
    }).finally(function () {
      if (window.history && window.history.replaceState) {
        window.history.replaceState({}, document.title, cleanUrl);
      }
    });
  }

  initUrlCoupon();

  function initRewardsForms() {
    return;

    const forms = document.querySelectorAll('[data-rewards-form]');

    forms.forEach(function (form) {
      const input = form.querySelector('[data-rewards-discount]');
      const points = form.querySelector('[data-rewards-points]');
      const submit = form.querySelector('[data-rewards-submit]');
      const decrease = form.querySelector('[data-rewards-decrease]');
      const increase = form.querySelector('[data-rewards-increase]');

      if (!input || !points || !submit) {
        return;
      }

      const min = Math.max(1, parseFloat(input.getAttribute('min')) || 1);
      const max = Math.max(min, parseFloat(input.getAttribute('max')) || min);

      function clamp(value) {
        if (!Number.isFinite(value)) {
          return max;
        }

        return Math.min(max, Math.max(min, Math.round(value)));
      }

      function sync(value) {
        const discount = clamp(value);
        input.value = String(discount);
        points.value = String(discount * 100);
        submit.textContent = 'Apply £' + discount.toFixed(2) + ' off';
      }

      sync(parseFloat(input.value));

      input.addEventListener('input', function () {
        const cleanValue = input.value.replace(/[^\d.]/g, '');
        input.value = cleanValue;
        sync(parseFloat(cleanValue));
      });

      input.addEventListener('blur', function () {
        sync(parseFloat(input.value));
      });

      if (decrease) {
        decrease.addEventListener('click', function () {
          sync((parseFloat(input.value) || max) - 1);
        });
      }

      if (increase) {
        increase.addEventListener('click', function () {
          sync((parseFloat(input.value) || min) + 1);
        });
      }
    });
  }

  initRewardsForms();

  function getRewardsFormValue(form) {
    const input = form ? form.querySelector('[data-rewards-discount]') : null;
    return input ? parseFloat(String(input.value).replace(/[^\d.]/g, '')) : 0;
  }

  function syncRewardsForm(form, value) {
    const input = form ? form.querySelector('[data-rewards-discount]') : null;
    const points = form ? form.querySelector('[data-rewards-points]') : null;
    const submit = form ? form.querySelector('[data-rewards-submit]') : null;
    const decrease = form ? form.querySelector('[data-rewards-decrease]') : null;
    const increase = form ? form.querySelector('[data-rewards-increase]') : null;
    const summary = form ? form.parentElement.querySelector('[data-rewards-summary]') : null;
    const summaryPoints = summary ? summary.querySelector('[data-rewards-summary-points]') : null;
    const summaryDiscount = summary ? summary.querySelector('[data-rewards-summary-discount]') : null;

    if (!input || !points || !submit) {
      return;
    }

    const min = Math.max(1, parseFloat(input.getAttribute('min')) || 1);
    const max = Math.max(min, parseFloat(input.getAttribute('max')) || parseFloat(form.getAttribute('data-max-discount')) || min);
    const parsed = Number.isFinite(value) ? value : max;
    const discount = Math.min(max, Math.max(min, Math.round(parsed)));

    input.value = discount.toFixed(2);
    points.value = String(discount * 100);
    const isActive = form.classList.contains('kangoo-rewards-cart__form--active');
    const fullSubmitText = (isActive ? 'Update to ' : 'Apply ') + '\u00a3' + discount.toFixed(2) + ' off';

    submit.textContent = form.closest('.kangoo-rewards-cart--checkout, .kangoo-rewards-cart--cart') ? (isActive ? 'Update' : 'Apply') : fullSubmitText;
    submit.setAttribute('aria-label', fullSubmitText);

    if (summaryPoints) {
      summaryPoints.textContent = String(discount * 100);
    }

    if (summaryDiscount) {
      summaryDiscount.textContent = '\u00a3' + discount.toFixed(2);
    }

    if (decrease) {
      decrease.disabled = discount <= min;
    }

    if (increase) {
      increase.disabled = discount >= max;
    }
  }

  document.querySelectorAll('[data-rewards-form]').forEach(function (form) {
    syncRewardsForm(form, getRewardsFormValue(form));
  });

  document.addEventListener('click', function (event) {
    if (!event.target || !event.target.closest) {
      return;
    }

    const decrease = event.target.closest('[data-rewards-decrease]');
    const increase = event.target.closest('[data-rewards-increase]');

    if (!decrease && !increase) {
      return;
    }

    const form = event.target.closest('[data-rewards-form]');

    if (!form) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    syncRewardsForm(form, getRewardsFormValue(form) + (increase ? 1 : -1));
  });

  document.addEventListener('input', function (event) {
    if (!event.target || !event.target.matches || !event.target.matches('[data-rewards-discount]')) {
      return;
    }

    const form = event.target.closest('[data-rewards-form]');

    if (!form) {
      return;
    }

    event.target.value = event.target.value.replace(/[^\d.]/g, '');
    syncRewardsForm(form, getRewardsFormValue(form));
  });

  document.addEventListener('blur', function (event) {
    if (!event.target || !event.target.matches || !event.target.matches('[data-rewards-discount]')) {
      return;
    }

    const form = event.target.closest('[data-rewards-form]');

    if (form) {
      syncRewardsForm(form, getRewardsFormValue(form));
    }
  }, true);

  function parseRewardsResponse(text) {
    try {
      return JSON.parse(text);
    } catch (error) {
      throw new Error('Unable to update rewards. Please refresh the page and try again.');
    }
  }

  function getStoreApiNonce(response) {
    const nextNonce = response.headers.get('Nonce');

    if (nextNonce && window.kangooRewards) {
      window.kangooRewards.store_api_nonce = nextNonce;
    }
  }

  function callStoreApiCoupon(action) {
    console.info('[Kangoo Rewards] Store API coupon sync skipped', {
      action: action,
      reason: 'Rewards are applied as a cart discount, not a WooCommerce coupon.'
    });

    return Promise.resolve();
  }

  document.addEventListener('submit', function (event) {
    if (!event.target || !event.target.closest) {
      return;
    }

    const form = event.target.closest('.kangoo-rewards-cart form');

    if (!form || !window.kangooRewards || !window.kangooRewards.ajax_url) {
      return;
    }

    event.preventDefault();

    const submit = event.submitter && event.submitter.matches && event.submitter.matches('button[type="submit"]') ? event.submitter : form.querySelector('button[type="submit"]');
    const originalText = submit ? submit.textContent : '';
    const formData = new FormData(form);
    const clickedSubmit = event.submitter;

    if (clickedSubmit && clickedSubmit.name === 'kangoo_rewards_action') {
      formData.set('kangoo_rewards_action', clickedSubmit.value);
    }

    const rewardsAction = formData.get('kangoo_rewards_action') === 'remove' ? 'remove' : 'apply';

    formData.set('action', 'kangoo_rewards_set_coupon_state');
    formData.set('nonce', window.kangooRewards.ajax_nonce || '');

    console.info('[Kangoo Rewards] Submit', {
      action: rewardsAction,
      discount: formData.get('kangoo_rewards_discount'),
      points: formData.get('kangoo_rewards_points')
    });

    if (submit) {
      submit.disabled = true;
      submit.textContent = 'Updating...';
    }

    fetch(window.kangooRewards.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then(function (response) {
        return response.text().then(parseRewardsResponse).then(function (data) {
          if (!response.ok || !data || !data.success) {
            const message = data && data.data && data.data.message ? data.data.message : 'Unable to update rewards.';
            throw new Error(message);
          }

          console.info('[Kangoo Rewards] AJAX success', data.data || data);

          return data;
        });
      })
      .then(function () {
        return callStoreApiCoupon(rewardsAction);
      })
      .then(function () {
        console.info('[Kangoo Rewards] Updated, reloading checkout');
        window.location.reload();
      })
      .catch(function (error) {
        console.error('[Kangoo Rewards] Update failed', error);
        window.alert(error.message || 'Unable to update rewards.');

        if (submit) {
          submit.disabled = false;
          submit.textContent = originalText;
        }
      });
  });

  function isDesktop() {
    return window.innerWidth >= 1024;
  }

  function openCartDrawer() {
    if (!cartDrawer) {
      return;
    }

    closeMegaDrawer();
    closeDesktopMegaMenu();

    cartDrawer.classList.add('is-open');
    body.classList.add('no-scroll');
  }

  function closeCartDrawer() {
    if (!cartDrawer) {
      return;
    }

    cartDrawer.classList.remove('is-open');

    if (!megaDrawer || !megaDrawer.classList.contains('is-open')) {
      body.classList.remove('no-scroll');
    }
  }

  function openMegaDrawer() {
    if (!megaDrawer) {
      return;
    }

    closeCartDrawer();
    closeDesktopMegaMenu();

    megaDrawer.classList.add('is-open');
    megaDrawer.setAttribute('aria-hidden', 'false');

    if (megaOpenBtn) {
      megaOpenBtn.setAttribute('aria-expanded', 'true');
    }

    body.classList.add('no-scroll');
  }

  function closeMegaDrawer() {
    if (!megaDrawer) {
      return;
    }

    megaDrawer.classList.remove('is-open');
    megaDrawer.setAttribute('aria-hidden', 'true');

    if (megaOpenBtn) {
      megaOpenBtn.setAttribute('aria-expanded', 'false');
    }

    if (!cartDrawer || !cartDrawer.classList.contains('is-open')) {
      body.classList.remove('no-scroll');
    }
  }

  function openDesktopMegaMenu() {
    if (!megaMenuDesktop) {
      return;
    }

    closeCartDrawer();
    closeMegaDrawer();

    megaMenuDesktop.classList.add('is-open');

    if (megaOpenBtn) {
      megaOpenBtn.setAttribute('aria-expanded', 'true');
    }
  }

  function closeDesktopMegaMenu() {
    if (!megaMenuDesktop) {
      return;
    }

    megaMenuDesktop.classList.remove('is-open');

    if (megaOpenBtn) {
      megaOpenBtn.setAttribute('aria-expanded', 'false');
    }
  }

  function setActiveMegaPanel(panelKey) {
    if (!panelKey) {
      return;
    }

    megaPanelTriggers.forEach(function (trigger) {
      const isActive = trigger.getAttribute('data-mega-panel-trigger') === panelKey;
      trigger.classList.toggle('is-active', isActive);
      trigger.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    megaPanels.forEach(function (panel) {
      const isActive = panel.getAttribute('data-mega-panel') === panelKey;
      panel.classList.toggle('is-active', isActive);
    });
  }

  if (cartTrigger) {
    cartTrigger.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      openCartDrawer();
    });
  }

  if (cartCloseBtn) {
    cartCloseBtn.addEventListener('click', closeCartDrawer);
  }

  if (cartOverlay) {
    cartOverlay.addEventListener('click', closeCartDrawer);
  }

  if (megaOpenBtn) {
    megaOpenBtn.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();

      if (isDesktop()) {
        if (megaMenuDesktop && megaMenuDesktop.classList.contains('is-open')) {
          closeDesktopMegaMenu();
        } else {
          openDesktopMegaMenu();
        }
      } else {
        if (megaDrawer && megaDrawer.classList.contains('is-open')) {
          closeMegaDrawer();
        } else {
          openMegaDrawer();
        }
      }
    });
  }

  megaCloseButtons.forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      closeMegaDrawer();
    });
  });

  megaPanelTriggers.forEach(function (trigger) {
    const panelKey = trigger.getAttribute('data-mega-panel-trigger');

    trigger.addEventListener('mouseenter', function () {
      if (!isDesktop()) {
        return;
      }

      setActiveMegaPanel(panelKey);
    });

    trigger.addEventListener('focus', function () {
      setActiveMegaPanel(panelKey);
    });

    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      setActiveMegaPanel(panelKey);
    });
  });

  document.addEventListener('click', function (event) {
    const clickedMegaButton = megaOpenBtn && megaOpenBtn.contains(event.target);
    const clickedDesktopMega = megaMenuDesktop && megaMenuDesktop.contains(event.target);
    const clickedMobileMega = megaDrawer && megaDrawer.contains(event.target);
    const clickedCart = cartDrawer && cartDrawer.contains(event.target);
    const clickedCartButton = cartTrigger && cartTrigger.contains(event.target);

    if (!clickedMegaButton && !clickedDesktopMega && isDesktop()) {
      closeDesktopMegaMenu();
    }

    if (!clickedCart && !clickedCartButton) {
      closeCartDrawer();
    }

    if (!clickedMegaButton && !clickedMobileMega && !isDesktop()) {
      closeMegaDrawer();
    }
  });

  window.addEventListener('resize', function () {
    closeMegaDrawer();
    closeDesktopMegaMenu();
  });
	
	/* HEADER HIDE / SHOW ON SCROLL */
	const siteHeader = document.querySelector('.site-header');
	let lastScrollY = window.scrollY;

  function setSiteHeaderHidden(isHidden) {
    if (!siteHeader) {
      return;
    }

    siteHeader.classList.toggle('site-header--hidden', isHidden);
    body.classList.toggle('site-header-is-hidden', isHidden);
  }

	window.addEventListener('scroll', function () {
	  if (!siteHeader || body.classList.contains('no-scroll')) return;

	  const currentScrollY = window.scrollY;

	  if (currentScrollY <= 80 || currentScrollY < lastScrollY) {
		setSiteHeaderHidden(false);
	  }

	  if (currentScrollY > lastScrollY && currentScrollY > 120) {
		setSiteHeaderHidden(true);
		closeDesktopMegaMenu();
	  }

	  lastScrollY = Math.max(currentScrollY, 0);
	}, { passive: true });

  if (mainBtn && stickyBtn) {
    const setStickyButtonText = function (text) {
      const label = stickyBtn.querySelector('[data-sticky-button-text]');

      if (label) {
        label.textContent = text;
        return;
      }

      stickyBtn.textContent = text;
    };

    stickyBtn.addEventListener('click', function () {
      if (stickyBtn.disabled) {
        return;
      }

      stickyBtn.classList.remove('is-added');
      stickyBtn.classList.add('is-loading');
      mainBtn.click();
    });
  }

  if (sticky && mainBtn) {
    const observer = new IntersectionObserver(
      function ([entry]) {
        sticky.style.transform = entry.isIntersecting ? 'translateY(100%)' : 'translateY(0)';
      },
      { threshold: 0 }
    );

    observer.observe(mainBtn);
  }

  if (variationForm && mainImage && window.jQuery) {
    const originalSrc = mainImage.getAttribute('src');
    const originalAlt = mainImage.getAttribute('alt');

    jQuery(variationForm).on('found_variation', function (event, variation) {
      if (!variation || !variation.image || !variation.image.src) {
        return;
      }

      mainImage.setAttribute('src', variation.image.src);
      mainImage.setAttribute('alt', variation.image.alt || originalAlt);

      thumbs.forEach(function (thumb) {
        thumb.classList.toggle('is-active', thumb.dataset.image === variation.image.src);
      });
    });

    jQuery(variationForm).on('reset_image', function () {
      mainImage.setAttribute('src', originalSrc);
      mainImage.setAttribute('alt', originalAlt);

      thumbs.forEach(function (thumb, index) {
        thumb.classList.toggle('is-active', index === 0);
      });
    });
  }

  if (mainImage && thumbs.length) {
    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        const newSrc = this.dataset.image;
        const newAlt = this.dataset.alt || mainImage.getAttribute('alt');

        if (!newSrc) {
          return;
        }

        mainImage.setAttribute('src', newSrc);
        mainImage.setAttribute('alt', newAlt);

        thumbs.forEach(function (item) {
          item.classList.remove('is-active');
        });

        this.classList.add('is-active');
      });
    });
  }

  const searchOverlay = document.getElementById('kangoo-search-overlay');
  const searchOpenButtons = document.querySelectorAll('[data-search-open]');
  const searchCloseButtons = document.querySelectorAll('[data-search-close]');
  const searchForm = searchOverlay ? searchOverlay.querySelector('[data-search-form]') : null;
  const searchInput = searchOverlay ? searchOverlay.querySelector('[data-search-input]') : null;
  const searchResults = searchOverlay ? searchOverlay.querySelector('[data-search-results]') : null;
  const searchSuggestions = searchOverlay ? searchOverlay.querySelectorAll('[data-search-suggestion]') : [];
  let searchTimer = null;
  let searchAbort = null;

  if (searchOverlay && searchOverlay.parentElement !== document.body) {
    document.body.appendChild(searchOverlay);
  }

  function openSearchOverlay() {
    if (!searchOverlay) {
      return;
    }

    searchOverlay.classList.add('is-open');
    searchOverlay.setAttribute('aria-hidden', 'false');
    body.classList.add('no-scroll');

    searchOpenButtons.forEach(function (button) {
      button.setAttribute('aria-expanded', 'true');
    });

    window.setTimeout(function () {
      if (searchInput) {
        searchInput.focus();
      }
    }, 80);
  }

  function closeSearchOverlay() {
    if (!searchOverlay) {
      return;
    }

    searchOverlay.classList.remove('is-open');
    searchOverlay.setAttribute('aria-hidden', 'true');
    body.classList.remove('no-scroll');

    searchOpenButtons.forEach(function (button) {
      button.setAttribute('aria-expanded', 'false');
    });
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char];
    });
  }

  function setSearchMessage(message) {
    if (searchResults) {
      searchResults.innerHTML = '<p class="kangoo-search__empty">' + escapeHtml(message) + '</p>';
    }
  }

  function renderSearchResults(data, query) {
    if (!searchResults) {
      return;
    }

    const products = data && Array.isArray(data.products) ? data.products : [];
    const guides = data && Array.isArray(data.guides) ? data.guides : [];

    if (!products.length && !guides.length) {
      setSearchMessage('No results found for "' + query + '". Try VELO, ZYN, mint, berry or strong.');
      return;
    }

    let html = '';

    if (products.length) {
      html += '<section class="kangoo-search__section"><h3>Products</h3><div class="kangoo-search__list">';

      products.forEach(function (item) {
        html += '<a class="kangoo-search__product" href="' + escapeHtml(item.url) + '">' +
          '<img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.title) + '">' +
          '<span class="kangoo-search__product-body">' +
          '<strong>' + escapeHtml(item.title) + '</strong>' +
          '<span class="kangoo-search__product-meta">' +
          (item.strength ? '<em>' + escapeHtml(item.strength) + '</em>' : '') +
          (item.stock ? '<em class="is-muted">' + escapeHtml(item.stock) + '</em>' : '') +
          '</span>' +
          '</span>' +
          '<span class="kangoo-search__price">' + (item.price_html || '') + '</span>' +
          '</a>';
      });

      html += '</div></section>';
    }

    if (guides.length) {
      html += '<section class="kangoo-search__section"><h3>Guides</h3><div class="kangoo-search__guide-list">';

      guides.forEach(function (item) {
        html += '<a class="kangoo-search__guide" href="' + escapeHtml(item.url) + '">' +
          '<strong>' + escapeHtml(item.title) + '</strong>' +
          '<span>' + escapeHtml(item.excerpt) + '</span>' +
          '</a>';
      });

      html += '</div></section>';
    }

    searchResults.innerHTML = html;
  }

  function runSearch(query) {
    if (!searchResults || typeof kangooSearch === 'undefined') {
      return;
    }

    if (query.length < 2) {
      setSearchMessage('Start typing to search products and guides.');
      return;
    }

    if (searchAbort) {
      searchAbort.abort();
    }

    searchAbort = new AbortController();
    setSearchMessage('Searching...');

    const url = kangooSearch.ajax_url + '?action=kangoo_ajax_search&nonce=' + encodeURIComponent(kangooSearch.nonce) + '&query=' + encodeURIComponent(query);

    fetch(url, {
      credentials: 'same-origin',
      signal: searchAbort.signal
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.success) {
          throw new Error('Search failed');
        }

        renderSearchResults(payload.data, query);
      })
      .catch(function (error) {
        if (error.name === 'AbortError') {
          return;
        }

        setSearchMessage('Search is temporarily unavailable. Press enter to view full search results.');
      });
  }

  searchOpenButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      openSearchOverlay();
    });
  });

  searchCloseButtons.forEach(function (button) {
    button.addEventListener('click', closeSearchOverlay);
  });

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const query = searchInput.value.trim();

      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(function () {
        runSearch(query);
      }, 220);
    });
  }

  if (searchForm) {
    searchForm.addEventListener('submit', function (event) {
      if (searchInput && searchInput.value.trim().length < 2) {
        event.preventDefault();
        setSearchMessage('Type at least 2 characters to search.');
      }
    });
  }

  searchSuggestions.forEach(function (button) {
    button.addEventListener('click', function () {
      if (!searchInput) {
        return;
      }

      searchInput.value = button.getAttribute('data-search-suggestion') || '';
      searchInput.focus();
      runSearch(searchInput.value.trim());
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeCartDrawer();
      closeMegaDrawer();
      closeDesktopMegaMenu();
      closeSearchOverlay();
    }
  });

  const ageGate = document.getElementById('kangoo-age-gate');
  const ageGateAccept = document.querySelector('[data-age-gate-accept]');
  const ageGateReject = document.querySelector('[data-age-gate-reject]');
  const ageGateLeave = document.querySelector('[data-age-gate-leave]');
  const ageGateConfirmScreen = document.querySelector('[data-age-gate-confirm-screen]');
  const ageGateBlockedScreen = document.querySelector('[data-age-gate-blocked-screen]');
  const ageGateKey = 'kangooAgeGateAccepted';
  const ageGateParams = new URLSearchParams(window.location.search);
  const shouldPreviewAgeGate = ageGateParams.get('age_gate') === 'preview';
  const shouldResetAgeGate = ageGateParams.get('age_gate') === 'reset';

  function isAgeGateAccepted() {
    try {
      if (localStorage.getItem(ageGateKey) === '1') {
        return true;
      }
    } catch (error) {
      return document.cookie.indexOf(ageGateKey + '=1') !== -1;
    }

    return document.cookie.indexOf(ageGateKey + '=1') !== -1;
  }

  function showAgeGate() {
    if (!ageGate) {
      return;
    }

    ageGate.classList.add('is-visible');
    ageGate.setAttribute('aria-hidden', 'false');
    body.classList.add('no-scroll');

    window.setTimeout(function () {
      if (ageGateAccept) {
        ageGateAccept.focus();
      }
    }, 80);
  }

  function hideAgeGate() {
    if (!ageGate) {
      return;
    }

    ageGate.classList.remove('is-visible');
    ageGate.setAttribute('aria-hidden', 'true');
    body.classList.remove('no-scroll');
  }

  function blockAgeGate() {
    if (!ageGateConfirmScreen || !ageGateBlockedScreen) {
      window.location.href = 'https://www.google.com/';
      return;
    }

    ageGateConfirmScreen.hidden = true;
    ageGateBlockedScreen.hidden = false;

    if (ageGateLeave) {
      ageGateLeave.focus();
    }
  }

  if (shouldResetAgeGate) {
    try {
      localStorage.removeItem(ageGateKey);
    } catch (error) {
      document.cookie = ageGateKey + '=;path=/;max-age=0;samesite=lax';
    }

    document.cookie = ageGateKey + '=;path=/;max-age=0;samesite=lax';
  }

  if (ageGate && (shouldPreviewAgeGate || !isAgeGateAccepted())) {
    showAgeGate();
  }

  if (ageGateAccept) {
    ageGateAccept.addEventListener('click', function () {
      try {
        localStorage.setItem(ageGateKey, '1');
      } catch (error) {
        document.cookie = ageGateKey + '=1;path=/;max-age=31536000;samesite=lax';
      }

      hideAgeGate();
    });
  }

  if (ageGateReject) {
    ageGateReject.addEventListener('click', function () {
      blockAgeGate();
    });
  }

  if (ageGateLeave) {
    ageGateLeave.addEventListener('click', function () {
      window.location.href = 'https://www.google.com/';
    });
  }

  if (ageGate) {
    ageGate.addEventListener('keydown', function (event) {
      if (event.key !== 'Tab') {
        return;
      }

      const focusable = Array.from(ageGate.querySelectorAll('button:not([hidden]):not([disabled]), [href]:not([hidden]), input:not([hidden]):not([disabled]), select:not([hidden]):not([disabled]), textarea:not([hidden]):not([disabled]), [tabindex]:not([tabindex="-1"]):not([hidden])'))
        .filter(function (element) {
          return element.offsetParent !== null;
        });

      if (!focusable.length) {
        return;
      }

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
        return;
      }

      if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  }

  const params = ageGateParams;

  if (params.has('add-to-cart')) {
    openCartDrawer();
  }

  function setupCheckoutAgeVerificationRow() {
    const checkout = document.querySelector('.woocommerce-checkout');

    if (!checkout) {
      return;
    }

    const nativeDay = checkout.querySelector('[id*="kangoo-age-day"]');
    const nativeMonth = checkout.querySelector('[id*="kangoo-age-month"]');
    const nativeYear = checkout.querySelector('[id*="kangoo-age-year"]');

    const hideNativeAgeFields = function () {
      [nativeDay, nativeMonth, nativeYear].forEach(function (nativeInput) {
        if (!nativeInput) {
          return;
        }

        const nativeWrapper = nativeInput.closest('.wc-block-components-text-input, .wc-block-components-combobox, .wc-block-components-select, div, p');

        if (nativeWrapper) {
          nativeWrapper.classList.add('kangoo-native-age-field-hidden');
        }
      });
    };

    if (!nativeDay || !nativeMonth || !nativeYear) {
      return;
    }

    hideNativeAgeFields();

    const savedDob = getStoredCheckoutDob();

    if (!savedDob || !isValidCheckoutDob(savedDob)) {
      return;
    }

    [
      [nativeDay, savedDob.day.padStart(2, '0')],
      [nativeMonth, savedDob.month.padStart(2, '0')],
      [nativeYear, savedDob.year]
    ].forEach(function (entry) {
      const nativeInput = entry[0];
      const value = entry[1];

      if (!nativeInput || nativeInput.value === value) {
        return;
      }

      const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
      setter.call(nativeInput, value);
      nativeInput.dispatchEvent(new Event('input', { bubbles: true }));
      nativeInput.dispatchEvent(new Event('change', { bubbles: true }));
      nativeInput.dispatchEvent(new Event('blur', { bubbles: true }));
    });
  }

  function linkCheckoutTermsText() {
    const termsBlock = document.querySelector('.wc-block-checkout__terms .wc-block-components-checkbox__label');

    if (!termsBlock || termsBlock.dataset.kangooTermsLinked === '1') {
      return;
    }

    termsBlock.innerHTML = [
      'By proceeding with your purchase you agree to our ',
      '<a href="/terms-and-conditions/" target="_blank" rel="noopener">Terms and Conditions</a>',
      ', ',
      '<a href="/returns-and-refunds/" target="_blank" rel="noopener">Returns and Refunds Policy</a>',
      ' and ',
      '<a href="/privacy-policy/" target="_blank" rel="noopener">Privacy Policy</a>'
    ].join('');
    termsBlock.dataset.kangooTermsLinked = '1';
  }

  function parseCheckoutMoney(text) {
    const value = String(text || '').replace(/,/g, '').replace(/[^\d.-]+/g, '');
    const parsed = parseFloat(value);

    return Number.isFinite(parsed) ? parsed : null;
  }

  function formatCheckoutMoney(amount) {
    return '\u00a3' + Math.max(0, amount).toFixed(2);
  }

  function getCheckoutSubtotal() {
    const directSubtotal = document.querySelector('.wc-block-components-totals-item--subtotal .wc-block-components-totals-item__value');

    if (directSubtotal) {
      return parseCheckoutMoney(directSubtotal.textContent);
    }

    const rows = Array.from(document.querySelectorAll('.wc-block-components-totals-item, .wc-block-components-totals-wrapper, tr'));
    const subtotalRow = rows.find(function (row) {
      return /\bsubtotal\b/i.test(row.textContent || '');
    });
    const value = subtotalRow ? subtotalRow.querySelector('.wc-block-components-totals-item__value, .amount, td:last-child') : null;

    return value ? parseCheckoutMoney(value.textContent) : null;
  }

  function getCartOrCheckoutSubtotal() {
    const blockSubtotal = document.querySelector('.wc-block-components-totals-item--subtotal .wc-block-components-totals-item__value');

    if (blockSubtotal) {
      return parseCheckoutMoney(blockSubtotal.textContent);
    }

    const classicSubtotal = document.querySelector('.cart-subtotal .amount');

    if (classicSubtotal) {
      return parseCheckoutMoney(classicSubtotal.textContent);
    }

    if (document.body.classList.contains('woocommerce-cart')) {
      const cartTotal = document.querySelector([
        '.wc-block-components-totals-footer-item .wc-block-components-totals-item__value',
        '.order-total .amount',
        '.cart_totals .amount'
      ].join(', '));

      if (cartTotal) {
        return parseCheckoutMoney(cartTotal.textContent);
      }

      const cartRows = Array.from(document.querySelectorAll('.wc-block-components-totals-item, .wc-block-components-totals-wrapper, tr, .cart_totals > *'));
      const visibleTotalRow = cartRows.find(function (row) {
        const text = row.textContent || '';
        const hidden = row.offsetParent === null && getComputedStyle(row).position !== 'fixed';

        return !hidden && /\b(subtotal|estimated total|total)\b/i.test(text);
      });
      const visibleTotalValue = visibleTotalRow ? visibleTotalRow.querySelector('.wc-block-components-totals-item__value, .amount, td:last-child') : null;

      if (visibleTotalValue) {
        return parseCheckoutMoney(visibleTotalValue.textContent);
      }
    }

    const miniCartSubtotal = document.querySelector('.woocommerce-mini-cart__total .amount');

    if (miniCartSubtotal) {
      return parseCheckoutMoney(miniCartSubtotal.textContent);
    }

    return getCheckoutSubtotal();
  }

  function getFreeShippingNudgeAnchor(container) {
    const headings = Array.from(container.querySelectorAll('h2, h3'));
    const shippingHeading = headings.find(function (heading) {
      return /shipping options/i.test(heading.textContent || '');
    });

    if (shippingHeading) {
      return shippingHeading;
    }

    const shippingOption = container.querySelector('.wc-block-checkout__shipping-option, .wc-block-components-shipping-rates-control');

    if (shippingOption) {
      return shippingOption.parentElement;
    }

    const cartSubmit = container.querySelector('.wc-block-cart__submit-container, .wc-proceed-to-checkout, .wp-block-woocommerce-proceed-to-checkout-block');

    if (cartSubmit) {
      return cartSubmit;
    }

    return null;
  }

  function getConfiguredFreeShippingThreshold(key, fallback) {
    const value = window.kangooRewards && Number(window.kangooRewards[key]) ? Number(window.kangooRewards[key]) : fallback;
    return value > 0 ? value : fallback;
  }

  function getFirstOrderShippingCouponCode() {
    return window.kangooRewards && window.kangooRewards.first_order_shipping_coupon_code
      ? String(window.kangooRewards.first_order_shipping_coupon_code).trim().toLowerCase()
      : 'firstfree';
  }

  function hasFirstOrderShippingCoupon(container) {
    const couponCode = getFirstOrderShippingCouponCode();

    if (!couponCode || !container) {
      return false;
    }

    const couponText = container.textContent ? container.textContent.toLowerCase() : '';

    if (couponText.includes(couponCode)) {
      return true;
    }

    return Boolean(
      window.kangooRewards
      && window.kangooRewards.first_order_free_shipping_active
      && !couponText.includes('coupon')
    );
  }

  function setupFreeShippingNudge() {
    const container = document.querySelector('.woocommerce-checkout, .woocommerce-cart');

    if (!container) {
      return;
    }

    const firstOrderOfferActive = hasFirstOrderShippingCoupon(container);
    const threshold = firstOrderOfferActive
      ? getConfiguredFreeShippingThreshold('first_order_free_shipping_threshold', 9.99)
      : getConfiguredFreeShippingThreshold('standard_free_shipping_threshold', 14.95);
    const subtotal = getCartOrCheckoutSubtotal();
    const anchor = getFreeShippingNudgeAnchor(container);

    if (!anchor || subtotal === null) {
      return;
    }

    let nudge = container.querySelector('[data-kangoo-free-shipping-nudge]:not(.kangoo-free-shipping-nudge--cart-drawer)');
    const isCartPage = container.classList.contains('woocommerce-cart');
    const cartBlock = isCartPage ? anchor.closest('.wp-block-woocommerce-cart, .wc-block-cart, .woocommerce-cart-form, .woocommerce') : null;
    const nudgeAnchor = cartBlock || anchor;

    if (!nudge) {
      nudge = document.createElement('div');
      nudge.className = 'kangoo-free-shipping-nudge ' + (isCartPage ? 'kangoo-free-shipping-nudge--cart' : 'kangoo-free-shipping-nudge--checkout');
      nudge.setAttribute('data-kangoo-free-shipping-nudge', '');
      nudgeAnchor.insertAdjacentElement(isCartPage ? 'beforebegin' : 'afterend', nudge);
    } else if (isCartPage && nudge.nextElementSibling !== nudgeAnchor) {
      nudgeAnchor.insertAdjacentElement('beforebegin', nudge);
    } else if (!isCartPage && nudge.previousElementSibling !== anchor) {
      anchor.insertAdjacentElement('afterend', nudge);
    }

    const remaining = threshold - subtotal;
    const progress = Math.max(0, Math.min(100, (subtotal / threshold) * 100));
    const stateKey = [firstOrderOfferActive ? 'first-order' : 'standard', threshold.toFixed(2), subtotal.toFixed(2), remaining > 0 ? 'locked' : 'unlocked'].join('|');

    if (nudge.dataset.kangooNudgeState === stateKey) {
      return;
    }

    nudge.dataset.kangooNudgeState = stateKey;

    if (remaining > 0) {
      nudge.classList.remove('is-unlocked');
      nudge.innerHTML = [
        '<div class="kangoo-free-shipping-nudge__copy">',
        '<strong>', firstOrderOfferActive ? 'New customer: ' : '', formatCheckoutMoney(remaining), ' away from free delivery</strong>',
        '<span>', firstOrderOfferActive ? 'First-order free UK delivery' : 'Free UK delivery', ' unlocks at ', formatCheckoutMoney(threshold), '.</span>',
        '</div>',
        '<div class="kangoo-free-shipping-nudge__track" aria-hidden="true">',
        '<span style="width: ', progress.toFixed(2), '%"></span>',
        '</div>'
      ].join('');
      return;
    }

    nudge.classList.add('is-unlocked');
    nudge.innerHTML = [
      '<div class="kangoo-free-shipping-nudge__copy">',
      '<strong>', firstOrderOfferActive ? 'First-order free delivery unlocked' : 'Free delivery unlocked', '</strong>',
      '<span>', firstOrderOfferActive ? 'Your first order qualifies for free UK delivery.' : 'Your order qualifies for free UK delivery.', '</span>',
      '</div>',
      '<div class="kangoo-free-shipping-nudge__track" aria-hidden="true"><span style="width: 100%"></span></div>'
    ].join('');
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
  }

  function getStoredCheckoutEmail() {
    const localizedEmail = window.kangooRewards && window.kangooRewards.checkout_email ? window.kangooRewards.checkout_email : '';
    const localEmail = window.localStorage ? window.localStorage.getItem('kangoo_checkout_email') : '';
    return isValidEmail(localEmail) ? localEmail : localizedEmail;
  }

  function parseCheckoutDob(value) {
    const cleanValue = String(value || '').trim();
    const match = cleanValue.match(/^(\d{4})-(\d{2})-(\d{2})$/);

    if (!match) {
      return null;
    }

    return {
      day: match[3],
      month: match[2],
      year: match[1]
    };
  }

  function getStoredCheckoutDob() {
    const localizedDob = window.kangooRewards && window.kangooRewards.checkout_dob ? window.kangooRewards.checkout_dob : '';
    const localDob = window.localStorage ? window.localStorage.getItem('kangoo_checkout_dob') : '';
    const parsedLocal = parseCheckoutDob(localDob);

    if (parsedLocal && isValidCheckoutDob(parsedLocal)) {
      return parsedLocal;
    }

    const parsedLocalized = parseCheckoutDob(localizedDob);

    return parsedLocalized && isValidCheckoutDob(parsedLocalized) ? parsedLocalized : null;
  }

  function normalizeCheckoutDob(day, month, year) {
    return {
      day: String(day || '').replace(/\D+/g, '').slice(0, 2),
      month: String(month || '').replace(/\D+/g, '').slice(0, 2),
      year: String(year || '').replace(/\D+/g, '').slice(0, 4)
    };
  }

  function parseFlexibleCheckoutDob(value) {
    const cleanValue = String(value || '').trim();

    if (!cleanValue) {
      return null;
    }

    const separated = cleanValue.match(/^(\d{1,2})\D+(\d{1,2})\D+(\d{4})$/);

    if (separated) {
      return normalizeCheckoutDob(separated[1], separated[2], separated[3]);
    }

    const compact = cleanValue.replace(/\D+/g, '');

    if (compact.length === 8) {
      return normalizeCheckoutDob(compact.slice(0, 2), compact.slice(2, 4), compact.slice(4));
    }

    return null;
  }

  function isValidCheckoutDob(dob) {
    if (!dob || !dob.day || !dob.month || !dob.year) {
      return false;
    }

    const yearValue = dob.year.length === 2 ? (Number(dob.year) > Number(String(new Date().getFullYear()).slice(-2)) ? '19' + dob.year : '20' + dob.year) : dob.year;
    const day = Number(dob.day);
    const month = Number(dob.month);
    const year = Number(yearValue);
    const date = new Date(year, month - 1, day);

    if (dob.year.length !== 4 || date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
      return false;
    }

    const today = new Date();
    let age = today.getFullYear() - year;
    const birthdayThisYear = new Date(today.getFullYear(), month - 1, day);

    if (today < birthdayThisYear) {
      age -= 1;
    }

    return age >= 18;
  }

  function formatCheckoutDob(dob) {
    return [
      String(dob.year || ''),
      String(dob.month || '').padStart(2, '0'),
      String(dob.day || '').padStart(2, '0')
    ].join('-');
  }

  function formatCheckoutDobDisplay(dob) {
    if (!dob || !dob.day || !dob.month || !dob.year) {
      return '';
    }

    return [
      String(dob.day || '').padStart(2, '0'),
      String(dob.month || '').padStart(2, '0'),
      String(dob.year || '')
    ].join(' / ');
  }

  function saveCheckoutEmail(email, dob) {
    const cleanEmail = String(email || '').trim();

    if (!isValidEmail(cleanEmail)) {
      return Promise.reject(new Error('Enter a valid email address.'));
    }

    if (!isValidCheckoutDob(dob)) {
      return Promise.reject(new Error('Enter a valid date of birth. You must be 18 or over.'));
    }

    const cleanDob = normalizeCheckoutDob(dob.day, dob.month, dob.year);
    const formattedDob = formatCheckoutDob(cleanDob);

    if (window.localStorage) {
      window.localStorage.setItem('kangoo_checkout_email', cleanEmail);
      window.localStorage.setItem('kangoo_checkout_dob', formattedDob);
    }

    if (!window.kangooRewards || !window.kangooRewards.ajax_url || !window.kangooRewards.ajax_nonce) {
      return Promise.resolve({
        email: cleanEmail,
        dob: formattedDob
      });
    }

    const formData = new FormData();
    formData.append('action', 'kangoo_store_checkout_email');
    formData.append('nonce', window.kangooRewards.ajax_nonce);
    formData.append('email', cleanEmail);
    formData.append('dob_day', cleanDob.day);
    formData.append('dob_month', cleanDob.month);
    formData.append('dob_year', cleanDob.year);

    return fetch(window.kangooRewards.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Could not save email.');
      }

      return response.json();
    }).then(function (payload) {
      if (!payload || !payload.success) {
        throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Could not save email.');
      }

      return {
        email: cleanEmail,
        dob: formattedDob
      };
    });
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (character) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[character];
    });
  }

  function syncCartCheckoutButton(button, isEnabled) {
    return Boolean(button) && Boolean(isEnabled);
  }

  function setupCartEmailCapture() {
    const cartPage = document.querySelector('.woocommerce-cart');

    if (!cartPage) {
      return;
    }

    let panel = cartPage.querySelector('[data-kangoo-cart-email-capture]');
    const checkoutAnchor = cartPage.querySelector('.wc-proceed-to-checkout, .wp-block-woocommerce-proceed-to-checkout-block, .wc-block-cart__submit-container');
    const cartBlock = checkoutAnchor
      ? checkoutAnchor.closest('.wp-block-woocommerce-cart, .wc-block-cart, .woocommerce-cart-form, .woocommerce')
      : null;

    if (!panel && checkoutAnchor) {
      const storedEmail = getStoredCheckoutEmail();
      const storedDob = getStoredCheckoutDob() || { day: '', month: '', year: '' };
      panel = document.createElement('div');
      panel.className = 'kangoo-cart-email-capture' + (isValidEmail(storedEmail) && isValidCheckoutDob(storedDob) ? ' has-email' : ' is-editing');
      panel.setAttribute('data-kangoo-cart-email-capture', '');
      panel.innerHTML = [
        '<div class="kangoo-cart-email-capture__header">',
        '<span class="kangoo-cart-email-capture__status" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 12.5l4.2 4.2L19 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>',
        '<strong>Checkout Details</strong>',
        '<button type="button" class="kangoo-cart-email-capture__edit" data-kangoo-cart-edit><span>Edit</span><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 8l2 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>',
        '</div>',
        '<div class="kangoo-cart-email-capture__form">',
        '<label class="kangoo-cart-email-capture__field" for="kangoo-cart-email-js"><span>Email address</span>',
        '<input id="kangoo-cart-email-js" type="email" value="', escapeHtml(storedEmail), '" placeholder="Email address" autocomplete="email" data-kangoo-cart-email></label>',
        '<div class="kangoo-cart-email-capture__dob" data-kangoo-cart-dob>',
        '<span class="kangoo-cart-email-capture__dob-title">Date of birth</span>',
        '<label><span>DD</span><input type="text" inputmode="numeric" maxlength="2" value="', escapeHtml(storedDob.day), '" autocomplete="bday-day" data-kangoo-cart-dob-day></label>',
        '<label><span>MM</span><input type="text" inputmode="numeric" maxlength="2" value="', escapeHtml(storedDob.month), '" autocomplete="bday-month" data-kangoo-cart-dob-month></label>',
        '<label><span>YYYY</span><input type="text" inputmode="numeric" maxlength="4" value="', escapeHtml(storedDob.year), '" autocomplete="bday-year" data-kangoo-cart-dob-year></label>',
        '</div>',
        '</div>',
        '<p class="kangoo-cart-email-capture__message" data-kangoo-cart-email-message>',
        isValidEmail(storedEmail) && isValidCheckoutDob(storedDob) ? 'Checkout details saved.' : 'Enter your email and date of birth to unlock checkout.',
        '</p>'
      ].join('');

      if (cartBlock && cartBlock.parentElement) {
        cartBlock.insertAdjacentElement('beforebegin', panel);
      } else {
        checkoutAnchor.insertAdjacentElement('beforebegin', panel);
      }
    }

    if (!panel) {
      return;
    }

    const editButton = panel.querySelector('[data-kangoo-cart-edit]');

    if (editButton && editButton.dataset.kangooEditReady !== '1') {
      editButton.dataset.kangooEditReady = '1';
      editButton.addEventListener('click', function () {
        const activeInput = panel.querySelector('[data-kangoo-cart-email]');
        panel.classList.add('is-editing');

        if (activeInput) {
          activeInput.focus();
        }
      });
    }

    if (panel.dataset.kangooEmailReady === '1') {
      const existingInput = panel.querySelector('[data-kangoo-cart-email]');
      const existingDob = normalizeCheckoutDob(
        panel.querySelector('[data-kangoo-cart-dob-day]') ? panel.querySelector('[data-kangoo-cart-dob-day]').value : '',
        panel.querySelector('[data-kangoo-cart-dob-month]') ? panel.querySelector('[data-kangoo-cart-dob-month]').value : '',
        panel.querySelector('[data-kangoo-cart-dob-year]') ? panel.querySelector('[data-kangoo-cart-dob-year]').value : ''
      );
      const existingValid = existingInput && isValidEmail(existingInput.value) && isValidCheckoutDob(existingDob);
      const existingCheckoutSelector = '.wc-proceed-to-checkout a.checkout-button, a.checkout-button, .wp-block-woocommerce-proceed-to-checkout-block a, .wp-block-woocommerce-proceed-to-checkout-block button, .wc-block-cart__submit-button, .wc-block-cart__submit-container .wc-block-components-button';

      cartPage.querySelectorAll(existingCheckoutSelector).forEach(function (button) {
        syncCartCheckoutButton(button, existingValid);
      });
      return;
    }

    panel.dataset.kangooEmailReady = '1';

    const input = panel.querySelector('[data-kangoo-cart-email]');
    const dobDay = panel.querySelector('[data-kangoo-cart-dob-day]');
    const dobMonth = panel.querySelector('[data-kangoo-cart-dob-month]');
    const dobYear = panel.querySelector('[data-kangoo-cart-dob-year]');
    const message = panel.querySelector('[data-kangoo-cart-email-message]');
    const emailSummary = panel.querySelector('[data-kangoo-cart-email-summary]');
    const dobSummary = panel.querySelector('[data-kangoo-cart-dob-summary]');
    const checkoutSelector = '.wc-proceed-to-checkout a.checkout-button, a.checkout-button, .wp-block-woocommerce-proceed-to-checkout-block a, .wp-block-woocommerce-proceed-to-checkout-block button, .wc-block-cart__submit-button, .wc-block-cart__submit-container .wc-block-components-button';

    if (!input) {
      return;
    }

    const storedEmail = getStoredCheckoutEmail();

    if (storedEmail && !input.value) {
      input.value = storedEmail;
    }

    const storedDob = getStoredCheckoutDob();

    if (storedDob) {
      if (dobDay && !dobDay.value) {
        dobDay.value = storedDob.day;
      }
      if (dobMonth && !dobMonth.value) {
        dobMonth.value = storedDob.month;
      }
      if (dobYear && !dobYear.value) {
        dobYear.value = storedDob.year;
      }
    }

    function setMessage(text, isError) {
      if (!message) {
        return;
      }

      message.textContent = text;
      message.classList.toggle('is-error', !!isError);
    }

    function updateSummaryDisplay(email, dob) {
      if (emailSummary) {
        emailSummary.textContent = isValidEmail(email) ? email : '';
      }

      if (dobSummary) {
        dobSummary.textContent = isValidCheckoutDob(dob) ? formatCheckoutDobDisplay(dob) : '';
      }
    }

    function setDobInputValues(dob, shouldPad) {
      const cleanDob = normalizeCheckoutDob(dob && dob.day, dob && dob.month, dob && dob.year);

      if (dobDay) {
        dobDay.value = shouldPad && cleanDob.day ? cleanDob.day.padStart(2, '0') : cleanDob.day;
      }

      if (dobMonth) {
        dobMonth.value = shouldPad && cleanDob.month ? cleanDob.month.padStart(2, '0') : cleanDob.month;
      }

      if (dobYear) {
        dobYear.value = cleanDob.year;
      }
    }

    function cleanDobInput(field) {
      if (!field) {
        return;
      }

      const maxLength = Number(field.maxLength) > 0 ? Number(field.maxLength) : 4;
      field.value = field.value.replace(/\D+/g, '').slice(0, maxLength);
    }

    function padDobInput(field) {
      if (!field || field === dobYear || field.value.length !== 1) {
        return;
      }

      field.value = field.value.padStart(2, '0');
    }

    function focusDobInput(field) {
      if (!field) {
        return;
      }

      window.setTimeout(function () {
        field.focus();
        field.select();
      }, 0);
    }

    function maybeAdvanceDobInput(field) {
      if (field === dobDay && field.value.length >= 2) {
        focusDobInput(dobMonth);
      } else if (field === dobMonth && field.value.length >= 2) {
        focusDobInput(dobYear);
      }
    }

    function getDobFromPanel() {
      return normalizeCheckoutDob(
        dobDay ? dobDay.value : '',
        dobMonth ? dobMonth.value : '',
        dobYear ? dobYear.value : ''
      );
    }

    function markSaved(payload) {
      const email = payload && payload.email ? payload.email : input.value;
      const activeDob = getDobFromPanel();
      const dob = payload && payload.dob ? payload.dob : formatCheckoutDob(activeDob);

      panel.classList.add('has-email');
      panel.classList.remove('needs-email');
      panel.classList.remove('is-editing');
      setDobInputValues(activeDob, true);
      setMessage('Checkout details saved.', false);
      updateSummaryDisplay(email, activeDob);

      if (window.kangooRewards) {
        window.kangooRewards.checkout_email = email;
        window.kangooRewards.checkout_dob = dob;
      }
    }

    function clearCheckoutLoadingState(button) {
      if (!button) {
        return;
      }

      button.classList.remove('is-loading', 'loading', 'wc-block-components-button--loading');
      button.removeAttribute('aria-disabled');
      button.removeAttribute('disabled');

      const spinner = button.querySelector('.wc-block-components-spinner, .components-spinner, .button-spinner');

      if (spinner) {
        spinner.remove();
      }
    }

    function setCheckoutEnabled(isEnabled) {
      cartPage.querySelectorAll(checkoutSelector).forEach(function (button) {
        syncCartCheckoutButton(button, isEnabled);
      });
    }

    function refreshCheckoutState() {
      const activeDob = getDobFromPanel();
      const valid = isValidEmail(input.value) && isValidCheckoutDob(activeDob);
      updateSummaryDisplay(input.value, activeDob);
      setCheckoutEnabled(valid);

      if (valid) {
        saveCheckoutEmail(input.value, activeDob).then(markSaved).catch(function () {});
      } else {
        panel.classList.remove('has-email');
        panel.classList.add('is-editing');
        setMessage('Enter your email and date of birth to unlock checkout.', false);
      }

      return valid;
    }

    [input, dobDay, dobMonth, dobYear].forEach(function (field) {
      if (!field) {
        return;
      }

      if (field !== input) {
        field.addEventListener('paste', function (event) {
          const clipboard = event.clipboardData || window.clipboardData;
          const pasted = clipboard ? clipboard.getData('text') : '';
          const parsedDob = parseFlexibleCheckoutDob(pasted);

          if (!parsedDob) {
            return;
          }

          event.preventDefault();
          setDobInputValues(parsedDob, true);
          focusDobInput(dobYear);
          refreshCheckoutState();
        });

        field.addEventListener('keydown', function (event) {
          if (!['/', '-', '.', ' '].includes(event.key)) {
            return;
          }

          event.preventDefault();
          cleanDobInput(field);
          padDobInput(field);

          if (field === dobDay) {
            focusDobInput(dobMonth);
          } else if (field === dobMonth) {
            focusDobInput(dobYear);
          }

          refreshCheckoutState();
        });
      }

      field.addEventListener('input', function () {
        if (field.type !== 'email') {
          cleanDobInput(field);
          maybeAdvanceDobInput(field);
        }

        refreshCheckoutState();
      });

      field.addEventListener('blur', function () {
        if (field.type !== 'email') {
          cleanDobInput(field);
          padDobInput(field);
        }

        refreshCheckoutState();
      });
    });

    refreshCheckoutState();

    if (cartPage.dataset.kangooEmailGateReady !== '1') {
      cartPage.dataset.kangooEmailGateReady = '1';

      cartPage.addEventListener('click', function (event) {
        const link = event.target.closest(checkoutSelector);

        if (!link || !cartPage.contains(link)) {
          return;
        }

        const activePanel = cartPage.querySelector('[data-kangoo-cart-email-capture]');
        const activeInput = activePanel ? activePanel.querySelector('[data-kangoo-cart-email]') : input;
        const activeMessage = activePanel ? activePanel.querySelector('[data-kangoo-cart-email-message]') : message;
        const activeDob = activePanel ? normalizeCheckoutDob(
          activePanel.querySelector('[data-kangoo-cart-dob-day]') ? activePanel.querySelector('[data-kangoo-cart-dob-day]').value : '',
          activePanel.querySelector('[data-kangoo-cart-dob-month]') ? activePanel.querySelector('[data-kangoo-cart-dob-month]').value : '',
          activePanel.querySelector('[data-kangoo-cart-dob-year]') ? activePanel.querySelector('[data-kangoo-cart-dob-year]').value : ''
        ) : getDobFromPanel();
        const email = activeInput ? (activeInput.value || getStoredCheckoutEmail()) : '';

        if (!activePanel || !activeInput || !isValidEmail(email) || !isValidCheckoutDob(activeDob)) {
          event.preventDefault();
          event.stopPropagation();
          event.stopImmediatePropagation();
          clearCheckoutLoadingState(link);

          if (!activePanel || !activeInput) {
            return;
          }
          activePanel.classList.add('needs-email');
          activePanel.classList.add('is-editing');
          if (activeMessage) {
            activeMessage.textContent = 'Add your email and date of birth first so checkout is ready for you.';
            activeMessage.classList.add('is-error');
          }
          (isValidEmail(email) ? (activePanel.querySelector('[data-kangoo-cart-dob-day]') || activeInput) : activeInput).focus();
          activePanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
          window.setTimeout(function () {
            activePanel.classList.remove('needs-email');
          }, 1800);
          return;
        }

        if (activePanel.classList.contains('has-email')) {
          return;
        }

        event.preventDefault();
        saveCheckoutEmail(email, activeDob).then(function () {
          window.location.href = link.href || link.getAttribute('data-href') || '/checkout/';
        }).catch(function (error) {
          setMessage(error.message || 'Enter a valid email address.', true);
          input.focus();
        });
      }, true);
    }
  }

  function setupCheckoutEmailPrefill() {
    if (!document.body.classList.contains('woocommerce-checkout')) {
      return;
    }

    const email = getStoredCheckoutEmail();

    if (!isValidEmail(email)) {
      return;
    }

    const fields = document.querySelectorAll('input[type="email"], input[name="billing_email"], #billing_email');

    fields.forEach(function (field) {
      if (!field.value) {
        const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
        setter.call(field, email);
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        field.dispatchEvent(new Event('blur', { bubbles: true }));

        const wrapper = field.closest('.wc-block-components-text-input, .form-row');
        if (wrapper) {
          wrapper.classList.add('has-value', 'is-active');
        }
      }
    });
  }

  function setupCheckoutGuestNotice() {
    if (!document.body.classList.contains('woocommerce-checkout') || document.body.classList.contains('logged-in')) {
      return;
    }

    const checkout = document.querySelector('.woocommerce-checkout');

    if (!checkout) {
      return;
    }

    function hideNativeGuestNotice() {
      Array.from(checkout.querySelectorAll('p, div, span')).forEach(function (element) {
        if (element.closest('[data-kangoo-checkout-guest-notice]')) {
          return;
        }

        const text = String(element.textContent || '').replace(/\s+/g, ' ').trim();

        if (/^you are currently checking out as a guest\.?$/i.test(text)) {
          element.classList.add('kangoo-native-guest-notice-hidden');
        }
      });
    }

    hideNativeGuestNotice();

    if (checkout.querySelector('[data-kangoo-checkout-guest-notice]')) {
      return;
    }

    const contactBlock = checkout.querySelector([
      '.wp-block-woocommerce-checkout-contact-information-block',
      '.wc-block-checkout__contact-fields',
      '.wc-block-checkout__contact-information'
    ].join(', '));

    if (!contactBlock) {
      return;
    }

    const notice = document.createElement('div');
    notice.className = 'kangoo-checkout-guest-notice';
    notice.setAttribute('data-kangoo-checkout-guest-notice', '');
    notice.innerHTML = '<span aria-hidden="true">i</span><strong>You are currently checking out as a guest.</strong>';
    contactBlock.insertAdjacentElement('afterend', notice);
    hideNativeGuestNotice();
  }

  function setupCheckoutAddressToggle() {
    if (!document.body.classList.contains('woocommerce-checkout')) {
      return;
    }

    document.querySelectorAll('.wc-block-components-address-form__address_2-toggle').forEach(function (toggle) {
      if (toggle.dataset.kangooAddressToggleReady === '1') {
        return;
      }

      toggle.textContent = 'Add apartment, suite, etc. (optional)';
      toggle.dataset.kangooAddressToggleReady = '1';
    });
  }

  function setupCategoryMobileControls() {
    const filter = document.querySelector('[data-category-filter]');
    const readMore = document.querySelector('[data-category-readmore]');
    const readMoreToggle = document.querySelector('[data-category-readmore-toggle]');
    const productGrid = document.querySelector('.category-page__products .woo-grid');

    if (filter) {
      filter.addEventListener('submit', function (event) {
        const emptyFields = filter.querySelectorAll('select[name], input[name]');
        let hasValue = false;

        emptyFields.forEach(function (field) {
          if (field.value === '') {
            field.disabled = true;
            return;
          }

          hasValue = true;
        });

        if (!hasValue && filter.action) {
          event.preventDefault();
          window.location.assign(filter.action);
          return;
        }

        window.setTimeout(function () {
          emptyFields.forEach(function (field) {
            field.disabled = false;
          });
        }, 0);
      });
    }

    document.querySelectorAll('[data-category-filter-open]').forEach(function (button) {
      button.addEventListener('click', function () {
        document.body.classList.add('category-filter-open');
        if (filter) {
          const firstField = filter.querySelector('select, button, a');
          if (firstField) {
            window.setTimeout(function () {
              firstField.focus();
            }, 120);
          }
        }
      });
    });

    document.querySelectorAll('[data-category-filter-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        document.body.classList.remove('category-filter-open');
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        document.body.classList.remove('category-filter-open');
      }
    });

    if (readMore && readMoreToggle) {
      readMoreToggle.addEventListener('click', function () {
        const expanded = readMore.classList.toggle('is-expanded');
        readMoreToggle.textContent = expanded ? 'Read less' : 'Read more';
      });
    }

    document.addEventListener('click', function (event) {
      const button = event.target.closest('[data-category-show-more-button]');

      if (!button || !productGrid) {
        return;
      }

      event.preventDefault();

      if (button.classList.contains('is-loading')) {
        return;
      }

      const originalText = button.textContent;
      const nextUrl = button.getAttribute('href');

      if (!nextUrl) {
        return;
      }

      button.classList.add('is-loading');
      button.textContent = 'Loading...';

      fetch(nextUrl, {
        credentials: 'same-origin'
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Could not load more products.');
          }

          return response.text();
        })
        .then(function (html) {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const nextProducts = doc.querySelectorAll('.category-page__products .woo-grid > .product-card');
          const nextShowMore = doc.querySelector('[data-category-show-more]');
          const currentShowMore = button.closest('[data-category-show-more]');

          if (!nextProducts.length) {
            throw new Error('No products found.');
          }

          nextProducts.forEach(function (product) {
            productGrid.appendChild(document.importNode(product, true));
          });

          if (currentShowMore && nextShowMore) {
            currentShowMore.replaceWith(document.importNode(nextShowMore, true));
          } else if (currentShowMore) {
            currentShowMore.remove();
          }

          window.history.replaceState({}, '', nextUrl);

          if (window.jQuery) {
            window.jQuery(document.body).trigger('kangoo_products_loaded');
          }
        })
        .catch(function () {
          button.classList.remove('is-loading');
          button.textContent = originalText;
        });
    });
  }

  function applyThemePreference(targetTheme) {
    if (!targetTheme) {
      return;
    }

    document.cookie = 'kangoo_theme_preference=' + encodeURIComponent(targetTheme) + ';path=/;max-age=31536000;samesite=lax';

    try {
      window.localStorage.removeItem('kangooThemePreferenceDismissed:dark');
      window.localStorage.removeItem('kangooThemePreferenceDismissed:light-first');
    } catch (error) {}

    const nextUrl = new URL(window.location.href);
    nextUrl.searchParams.delete('kangoo_theme_preview');
    window.location.assign(nextUrl.toString());
  }

  function setupThemePreferenceControls() {
    document.querySelectorAll('[data-kangoo-theme-menu-toggle]').forEach(function (button) {
      if (button.dataset.kangooThemeToggleReady === '1') {
        return;
      }

      button.dataset.kangooThemeToggleReady = '1';
      button.addEventListener('click', function () {
        applyThemePreference(button.getAttribute('data-target-theme') || '');
      });
    });
  }

  function setupThemePreferencePrompt() {
    const prompt = document.querySelector('[data-kangoo-theme-preference]');

    if (!prompt || document.cookie.indexOf('kangoo_theme_preference=') !== -1) {
      return;
    }

    const targetTheme = prompt.getAttribute('data-target-theme') || '';
    const closeButton = prompt.querySelector('[data-kangoo-theme-preference-close]');
    const actionButton = prompt.querySelector('[data-kangoo-theme-preference-action]');
    const dismissedKey = 'kangooThemePreferenceDismissed:' + targetTheme;

    function isDismissed() {
      try {
        return window.localStorage.getItem(dismissedKey) === '1';
      } catch (error) {
        return false;
      }
    }

    function dismissPrompt() {
      prompt.classList.remove('is-visible');
      prompt.setAttribute('aria-hidden', 'true');

      try {
        window.localStorage.setItem(dismissedKey, '1');
      } catch (error) {}
    }

    if (isDismissed()) {
      return;
    }

    window.setTimeout(function () {
      if (!isDismissed()) {
        prompt.classList.add('is-visible');
        prompt.setAttribute('aria-hidden', 'false');
      }
    }, 5000);

    if (closeButton) {
      closeButton.addEventListener('click', dismissPrompt);
    }

    if (actionButton && targetTheme) {
      actionButton.addEventListener('click', function () {
        applyThemePreference(targetTheme);
      });
    }
  }

  setupCheckoutAgeVerificationRow();
  linkCheckoutTermsText();
  setupFreeShippingNudge();
  setupCartEmailCapture();
  setupCheckoutEmailPrefill();
  setupCheckoutGuestNotice();
  setupCheckoutAddressToggle();
  setupCategoryMobileControls();
  setupThemePreferenceControls();
  setupThemePreferencePrompt();

  if (document.querySelector('.woocommerce-checkout, .woocommerce-cart')) {
    const checkoutAgeObserver = new MutationObserver(function () {
      setupCheckoutAgeVerificationRow();
      linkCheckoutTermsText();
      setupFreeShippingNudge();
      setupCartEmailCapture();
      setupCheckoutEmailPrefill();
      setupCheckoutGuestNotice();
      setupCheckoutAddressToggle();
    });
    checkoutAgeObserver.observe(document.body, { childList: true, subtree: true });
  }
});
