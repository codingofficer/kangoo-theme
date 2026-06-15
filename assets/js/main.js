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

    if (params.has('remove_coupon')) {
      return;
    }

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

  document.querySelectorAll('[data-product-read-more]').forEach(function (link) {
    link.addEventListener('click', function () {
      const target = document.querySelector(link.getAttribute('href'));

      if (target && target.tagName.toLowerCase() === 'details') {
        target.open = true;
      }
    });
  });

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
  let ageGateScrollY = 0;
  let ageGateScrollLocked = false;
  let ageGateHadNoScroll = false;
  let ageGatePreviousBodyStyles = null;

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

    lockAgeGateScroll();
    ageGate.classList.add('is-visible');
    ageGate.setAttribute('aria-hidden', 'false');

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
    unlockAgeGateScroll();
  }

  function lockAgeGateScroll() {
    if (ageGateScrollLocked) {
      return;
    }

    ageGateScrollLocked = true;
    ageGateHadNoScroll = body.classList.contains('no-scroll');
    ageGateScrollY = window.scrollY || document.documentElement.scrollTop || 0;
    ageGatePreviousBodyStyles = {
      position: body.style.position,
      top: body.style.top,
      left: body.style.left,
      right: body.style.right,
      width: body.style.width
    };

    document.documentElement.classList.add('kangoo-age-gate-open');
    body.classList.add('no-scroll', 'kangoo-age-gate-open');
    body.style.position = 'fixed';
    body.style.top = '-' + ageGateScrollY + 'px';
    body.style.left = '0';
    body.style.right = '0';
    body.style.width = '100%';
  }

  function unlockAgeGateScroll() {
    if (!ageGateScrollLocked) {
      return;
    }

    const restoreY = ageGateScrollY;
    ageGateScrollLocked = false;
    document.documentElement.classList.remove('kangoo-age-gate-open');
    body.classList.remove('kangoo-age-gate-open');

    if (!ageGateHadNoScroll) {
      body.classList.remove('no-scroll');
    }

    if (ageGatePreviousBodyStyles) {
      body.style.position = ageGatePreviousBodyStyles.position;
      body.style.top = ageGatePreviousBodyStyles.top;
      body.style.left = ageGatePreviousBodyStyles.left;
      body.style.right = ageGatePreviousBodyStyles.right;
      body.style.width = ageGatePreviousBodyStyles.width;
    }

    window.scrollTo(0, restoreY);
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

  function getFreeShippingTruckIcon() {
    return [
      '<span class="kangoo-free-shipping-nudge__icon" aria-hidden="true">',
      '<svg viewBox="0 0 24 24" focusable="false">',
      '<path d="M3 7h11v10H3zM14 11h3.5l2.5 3v3h-6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
      '<path d="M6.5 19a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4ZM17.5 19a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4Z" fill="none" stroke="currentColor" stroke-width="1.8"/>',
      '</svg>',
      '</span>'
    ].join('');
  }

  function setupFreeShippingNudge() {
    const container = document.querySelector('.woocommerce-checkout, .woocommerce-cart');

    if (!container) {
      return;
    }

    const storedEmail = getStoredCheckoutEmail();
    const existingCustomer = isStoredCheckoutEmailExistingCustomer();
    const firstOrderOfferActive = !existingCustomer && hasFirstOrderShippingCoupon(container);
    const threshold = firstOrderOfferActive
      ? getConfiguredFreeShippingThreshold('first_order_free_shipping_threshold', 9.99)
      : getConfiguredFreeShippingThreshold('standard_free_shipping_threshold', 14.95);
    const subtotal = getCartOrCheckoutSubtotal();
    const anchor = getFreeShippingNudgeAnchor(container);

    if (!anchor || subtotal === null) {
      return;
    }

    if (isValidEmail(storedEmail)) {
      lookupExistingCustomerEmail(storedEmail).then(function (isExisting) {
        if (isExisting !== existingCustomer) {
          setupFreeShippingNudge();
        }
      });
    }

    let nudge = container.querySelector('[data-kangoo-free-shipping-nudge]:not(.kangoo-free-shipping-nudge--cart-drawer)');
    const isCartPage = container.classList.contains('woocommerce-cart');
    const cartSidebar = isCartPage ? getCartSidebar() : null;
    const cartTotals = getCartTotalsPanel(cartSidebar);

    function placeCartNudge() {
      if (!cartSidebar) {
        const cartBlock = anchor.closest('.wp-block-woocommerce-cart, .wc-block-cart, .woocommerce-cart-form, .woocommerce');
        const nudgeAnchor = cartBlock || anchor;
        nudgeAnchor.insertAdjacentElement('beforebegin', nudge);
        return;
      }

      if (cartTotals && cartTotals.parentElement === cartSidebar) {
        cartSidebar.insertBefore(nudge, cartTotals);
        return;
      }

      cartSidebar.appendChild(nudge);
    }

    if (!nudge) {
      nudge = document.createElement('div');
      nudge.className = 'kangoo-free-shipping-nudge ' + (isCartPage ? 'kangoo-free-shipping-nudge--cart' : 'kangoo-free-shipping-nudge--checkout');
      nudge.setAttribute('data-kangoo-free-shipping-nudge', '');
      if (isCartPage) {
        placeCartNudge();
      } else {
        anchor.insertAdjacentElement('afterend', nudge);
      }
    } else if (isCartPage && (nudge.parentElement !== cartSidebar || (cartSidebar && cartTotals && nudge.nextElementSibling !== cartTotals))) {
      placeCartNudge();
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
        getFreeShippingTruckIcon(),
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
      getFreeShippingTruckIcon(),
      '<div class="kangoo-free-shipping-nudge__copy">',
      '<strong>', firstOrderOfferActive ? 'First-order free delivery unlocked' : 'Free delivery unlocked', '</strong>',
      '<span>', firstOrderOfferActive ? 'Your first order qualifies for free UK delivery.' : 'Your order qualifies for free UK delivery.', '</span>',
      '</div>',
      '<div class="kangoo-free-shipping-nudge__track" aria-hidden="true"><span style="width: 100%"></span></div>'
    ].join('');
  }

  function getCheckoutDeliveryThresholdLabel() {
    const threshold = getConfiguredFreeShippingThreshold('standard_free_shipping_threshold', 14.95);
    return formatCheckoutMoney(threshold).replace(/\.00$/, '');
  }

  function getThemeAssetUrl(path) {
    const baseUrl = window.kangooRewards && window.kangooRewards.theme_url
      ? String(window.kangooRewards.theme_url)
      : '/wp-content/themes/kangoo-theme';

    return baseUrl.replace(/\/$/, '') + '/' + String(path || '').replace(/^\//, '');
  }

  function getDeliveryCardConfigs() {
    return {
      tracked48: {
        key: 'tracked48',
        title: 'Royal Mail Tracked 48',
        estimate: 'Delivered in 2 working days aim',
        promo: 'FREE over ' + getCheckoutDeliveryThresholdLabel(),
        badge: 'Most Popular'
      },
      tracked24: {
        key: 'tracked24',
        title: 'Royal Mail Tracked 24',
        estimate: 'Delivered in 1 working day aim'
      },
      special: {
        key: 'special',
        title: 'Next Day Delivery',
        estimate: 'Next working day delivery',
        subtext: 'Order before 2pm Mon-Fri'
      },
      special1pm: {
        key: 'special1pm',
        title: 'Next Day by 1pm',
        estimate: 'Next working day before 1pm',
        subtext: 'Order before 2pm Mon-Fri'
      }
    };
  }

  function getDeliveryOptionConfig(text) {
    const cleanText = String(text || '').toLowerCase();
    const configs = getDeliveryCardConfigs();

    if (/free\s*shipping/.test(cleanText)) {
      return configs.tracked48;
    }

    if (!/(royal mail|tracked|special|next day|guaranteed)/i.test(cleanText)) {
      return null;
    }

    if (/tracked\s*48|\b48\b/.test(cleanText)) {
      return configs.tracked48;
    }

    if (/tracked\s*24|\b24\b/.test(cleanText)) {
      return configs.tracked24;
    }

    if (/1\s*pm|1pm|by\s*1|guaranteed/.test(cleanText)) {
      return configs.special1pm;
    }

    if (/special|next\s*day/.test(cleanText)) {
      return configs.special;
    }

    return null;
  }

  function getDeliveryShippingInputs() {
    const checkout = document.querySelector('.woocommerce-checkout');

    if (!checkout) {
      return [];
    }

    return Array.from(checkout.querySelectorAll([
      '.wp-block-woocommerce-checkout-shipping-method-block input[type="radio"]',
      '.wc-block-checkout__shipping-option input[type="radio"]',
      '.wc-block-components-shipping-rates-control input[type="radio"]',
      '.wc-block-components-shipping-methods input[type="radio"]',
      '.woocommerce-shipping-methods input[type="radio"]',
      '#shipping_method input[type="radio"]',
      'input[type="radio"][name^="shipping_method"]',
      'input[type="radio"][name*="shipping_method"]',
      'input[type="radio"][name*="shipping-method"]',
      'input[type="radio"][name*="wc-shipping-method"]',
      'input[type="radio"][name*="radio-control-wc-shipping"]',
      'input[type="radio"][id*="shipping_method"]',
      'input[type="radio"][id*="shipping-rate"]'
    ].join(', '))).filter(function (input) {
      return !input.closest('.wc-block-checkout__payment-method, .woocommerce-checkout-payment, #payment, .payment_box');
    });
  }

  function getDeliveryOptionRow(input) {
    return input.closest([
      '.wc-block-components-radio-control__option',
      '.wc-block-checkout__shipping-option',
      '.woocommerce-shipping-methods li',
      '#shipping_method li',
      'li',
      'label'
    ].join(', ')) || input.parentElement;
  }

  function getDeliveryOptionsGroup(row) {
    return row.closest([
      '.wc-block-components-shipping-rates-control',
      '.woocommerce-shipping-methods',
      '#shipping_method',
      '.wc-block-components-radio-control'
    ].join(', ')) || row.parentElement;
  }

  function getDeliveryOriginalText(row) {
    if (!row) {
      return '';
    }

    if (row.dataset.kpDeliveryOriginalText) {
      return row.dataset.kpDeliveryOriginalText;
    }

    const clone = row.cloneNode(true);
    clone.querySelectorAll('.kp-delivery-card-body, .kp-delivery-heading, .kp-delivery-assurance').forEach(function (node) {
      node.remove();
    });

    const text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
    row.dataset.kpDeliveryOriginalText = text;

    return text;
  }

  function getDeliveryPriceText(row, config) {
    const clone = row.cloneNode(true);
    clone.querySelectorAll('.kp-delivery-card-body, .kp-delivery-heading, .kp-delivery-assurance').forEach(function (node) {
      node.remove();
    });

    const priceNode = clone.querySelector([
      '.wc-block-components-radio-control__secondary-label',
      '.woocommerce-Price-amount',
      '.amount',
      '[class*="price"]'
    ].join(', '));
    const priceSource = priceNode ? priceNode.textContent : getDeliveryOriginalText(row);
    const matches = String(priceSource || '').match(/free|\u00a3\s?\d+(?:\.\d{1,2})?/ig);
    const rawPrice = matches && matches.length ? matches[matches.length - 1].replace(/\s+/g, '') : '';
    const isFree = /free|\u00a30(?:\.00)?/i.test(rawPrice);

    if (isFree) {
      return {
        amount: 'FREE',
        note: config && config.key === 'tracked48' ? 'on orders over ' + getCheckoutDeliveryThresholdLabel() : ''
      };
    }

    return {
      amount: rawPrice,
      note: ''
    };
  }

  function isFreeDeliveryOption(row, config) {
    if (!row || !config || config.key !== 'tracked48') {
      return false;
    }

    const text = getDeliveryOriginalText(row);
    const price = getDeliveryPriceText(row, config);

    return /free\s*shipping/i.test(text) || price.amount === 'FREE';
  }

  function getDeliveryHeadingHtml() {
    return [
      '<div class="kp-delivery-heading" data-kp-delivery-heading>',
      '<span class="kp-delivery-heading__icon" aria-hidden="true">',
      '<svg viewBox="0 0 24 24" focusable="false">',
      '<path d="M3 7h11v10H3zM14 11h3.5l2.5 3v3h-6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
      '<path d="M6.5 19a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4ZM17.5 19a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4Z" fill="none" stroke="currentColor" stroke-width="1.8"/>',
      '</svg>',
      '</span>',
      '<span class="kp-delivery-heading__copy">',
      '<strong>Delivery Options</strong>',
      '<small>Choose your preferred delivery method</small>',
      '</span>',
      '</div>'
    ].join('');
  }

  function getDeliveryAssuranceHtml() {
    return [
      '<div class="kp-delivery-assurance" data-kp-delivery-assurance>',
      '<span class="kp-delivery-assurance__icon" aria-hidden="true">',
      '<svg viewBox="0 0 24 24" focusable="false">',
      '<path d="M12 3.5 18 6v5.3c0 3.9-2.4 7.2-6 8.2-3.6-1-6-4.3-6-8.2V6l6-2.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
      '<path d="m9.2 11.8 1.8 1.8 3.8-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
      '</svg>',
      '</span>',
      '<span>',
      '<strong>Dispatched fast. Delivered reliably.</strong>',
      '<small>All orders are sent with tracking so you can follow your order every step of the way.</small>',
      '</span>',
      '</div>'
    ].join('');
  }

  function renderDeliveryCard(row, input, config) {
    const price = getDeliveryPriceText(row, config);
    const logoUrl = getThemeAssetUrl('assets/images/icons8-royal-mail.svg');
    const stateKey = [config.key, price.amount, price.note, config.promo || ''].join('|');
    let shell = row.querySelector('.kp-delivery-card-body');

    if (!row.classList.contains('kp-delivery-card')) {
      row.classList.add('kp-delivery-card');
    }

    if (row.dataset.kpDeliveryOption !== config.key) {
      row.dataset.kpDeliveryOption = config.key;
    }
    if (row.getAttribute('role') !== 'radio') {
      row.setAttribute('role', 'radio');
    }

    const checkedValue = input.checked ? 'true' : 'false';
    if (row.getAttribute('aria-checked') !== checkedValue) {
      row.setAttribute('aria-checked', checkedValue);
    }

    const tabIndexValue = input.disabled ? '-1' : '0';
    if (row.getAttribute('tabindex') !== tabIndexValue) {
      row.setAttribute('tabindex', tabIndexValue);
    }

    if (!input.classList.contains('kp-delivery-input')) {
      input.classList.add('kp-delivery-input');
    }

    if (!shell) {
      shell = document.createElement('span');
      shell.className = 'kp-delivery-card-body';
      row.appendChild(shell);
    }

    if (shell.dataset.kpDeliveryCardState === stateKey) {
      return;
    }

    shell.dataset.kpDeliveryCardState = stateKey;
    shell.innerHTML = [
      '<span class="kp-delivery-radio" aria-hidden="true"></span>',
      '<span class="kp-delivery-logo" aria-hidden="true"><img src="', escapeHtml(logoUrl), '" alt=""></span>',
      '<span class="kp-delivery-content">',
      '<strong class="kp-delivery-title">', escapeHtml(config.title), '</strong>',
      '<span class="kp-delivery-estimate">', escapeHtml(config.estimate), '</span>',
      config.subtext ? '<span class="kp-delivery-subtext">' + escapeHtml(config.subtext) + '</span>' : '',
      config.promo ? '<span class="kp-delivery-promo">' + escapeHtml(config.promo) + '</span>' : '',
      config.badge ? '<span class="kp-delivery-badge"><span aria-hidden="true">&#9733;</span>' + escapeHtml(config.badge) + '</span>' : '',
      '</span>',
      '<span class="kp-delivery-price">',
      price.amount ? '<strong>' + escapeHtml(price.amount) + '</strong>' : '',
      price.note ? '<small>' + escapeHtml(price.note) + '</small>' : '',
      '</span>'
    ].join('');
  }

  function syncDeliveryCards() {
    getDeliveryShippingInputs().forEach(function (input) {
      const row = getDeliveryOptionRow(input);

      if (!row || !row.classList.contains('kp-delivery-card')) {
        return;
      }

      const isSelected = Boolean(input.checked);
      const checkedValue = isSelected ? 'true' : 'false';

      row.classList.toggle('is-selected', isSelected);

      if (row.getAttribute('aria-checked') !== checkedValue) {
        row.setAttribute('aria-checked', checkedValue);
      }
    });
  }

  function triggerDeliveryCheckoutUpdate() {
    if (window.jQuery) {
      window.jQuery(document.body).trigger('update_checkout');
    }
  }

  function setupCheckoutDeliveryOptions() {
    const checkout = document.querySelector('.woocommerce-checkout');

    if (!checkout) {
      return;
    }

    const enhancedOptions = [];
    const deliveryOptions = [];
    const groups = new Set();

    getDeliveryShippingInputs().forEach(function (input) {
      const row = getDeliveryOptionRow(input);

      if (!row) {
        return;
      }

      const originalText = getDeliveryOriginalText(row);
      const config = getDeliveryOptionConfig(originalText);

      if (!config) {
        return;
      }

      const group = getDeliveryOptionsGroup(row);
      const section = row.closest('.wp-block-woocommerce-checkout-shipping-method-block, .wc-block-checkout__shipping-option, .woocommerce-shipping-methods, #shipping_method');
      const isFreeTracked48 = isFreeDeliveryOption(row, config);

      deliveryOptions.push({
        input: input,
        row: row,
        config: config,
        group: group,
        section: section,
        isFreeTracked48: isFreeTracked48
      });
    });

    const freeTracked48Option = deliveryOptions.find(function (option) {
      return option.isFreeTracked48;
    });

    deliveryOptions.forEach(function (option) {
      const input = option.input;
      const row = option.row;
      const config = option.config;
      const group = option.group;
      const section = option.section;
      const shouldHidePaidTracked48 = Boolean(
        freeTracked48Option
        && config.key === 'tracked48'
        && !option.isFreeTracked48
      );

      if (section) {
        section.classList.add('kp-delivery-enhanced-section');
      }

      if (group) {
        group.classList.add('kp-delivery-options');
        groups.add(group);
      }

      if (shouldHidePaidTracked48) {
        row.classList.add('kp-delivery-card-hidden');

        if (input.checked && !freeTracked48Option.input.checked && !freeTracked48Option.input.disabled) {
          freeTracked48Option.input.click();
        }

        return;
      }

      row.classList.remove('kp-delivery-card-hidden');
      renderDeliveryCard(row, input, config);
      enhancedOptions.push({ input: input, config: config });
    });

    groups.forEach(function (group) {
      const isListGroup = group.matches && group.matches('ul, ol');
      const deliveryRoot = isListGroup ? group.parentElement : group;

      if (!deliveryRoot) {
        return;
      }

      if (!deliveryRoot.querySelector('[data-kp-delivery-heading]')) {
        if (isListGroup) {
          group.insertAdjacentHTML('beforebegin', getDeliveryHeadingHtml());
        } else {
          group.insertAdjacentHTML('afterbegin', getDeliveryHeadingHtml());
        }
      }

      if (!deliveryRoot.querySelector('[data-kp-delivery-assurance]')) {
        if (isListGroup) {
          group.insertAdjacentHTML('afterend', getDeliveryAssuranceHtml());
        } else {
          group.insertAdjacentHTML('beforeend', getDeliveryAssuranceHtml());
        }
      }
    });

    if (enhancedOptions.length && !enhancedOptions.some(function (option) { return option.input.checked; })) {
      const preferredOption = enhancedOptions.find(function (option) {
        return option.config.key === 'tracked48' && !option.input.disabled;
      }) || enhancedOptions.find(function (option) {
        return !option.input.disabled;
      });

      if (preferredOption) {
        preferredOption.input.click();
      }
    }

    syncDeliveryCards();
  }

  function setupDeliveryOptionsInteractions() {
    if (body.dataset.kpDeliveryInteractionsReady === '1') {
      return;
    }

    body.dataset.kpDeliveryInteractionsReady = '1';

    document.addEventListener('click', function (event) {
      const card = event.target.closest('.kp-delivery-card');

      if (!card || !document.body.contains(card)) {
        return;
      }

      const input = card.querySelector('input[type="radio"]');

      if (!input || input.disabled) {
        return;
      }

      if (!input.checked) {
        event.preventDefault();
        input.click();
      }

      window.setTimeout(syncDeliveryCards, 20);
    });

    document.addEventListener('keydown', function (event) {
      const card = event.target.closest('.kp-delivery-card');

      if (!card || ![' ', 'Enter'].includes(event.key)) {
        return;
      }

      event.preventDefault();
      card.click();
    });

    document.addEventListener('change', function (event) {
      if (!event.target.matches('.kp-delivery-input')) {
        return;
      }

      syncDeliveryCards();
      triggerDeliveryCheckoutUpdate();
    });

    if (window.jQuery) {
      window.jQuery(document.body).on('updated_checkout updated_wc_div wc_fragments_refreshed', function () {
        setupCheckoutDeliveryOptions();
      });
    }
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
  }

  function getStoredCheckoutEmail() {
    const localizedEmail = window.kangooRewards && window.kangooRewards.checkout_email ? window.kangooRewards.checkout_email : '';
    const localEmail = window.localStorage ? window.localStorage.getItem('kangoo_checkout_email') : '';
    return isValidEmail(localEmail) ? localEmail : localizedEmail;
  }

  let existingCustomerLookupEmail = '';
  let existingCustomerLookupPromise = null;

  function cacheCheckoutExistingCustomerStatus(email, isExisting) {
    const cleanEmail = String(email || '').trim().toLowerCase();

    if (!cleanEmail || !window.localStorage) {
      return;
    }

    window.localStorage.setItem('kangoo_checkout_existing_customer_email', cleanEmail);
    window.localStorage.setItem('kangoo_checkout_existing_customer', isExisting ? '1' : '0');
  }

  function getCachedCheckoutExistingCustomerStatus(email) {
    const cleanEmail = String(email || '').trim().toLowerCase();

    if (!cleanEmail || !window.localStorage) {
      return null;
    }

    const cachedEmail = window.localStorage.getItem('kangoo_checkout_existing_customer_email');

    if (cachedEmail !== cleanEmail) {
      return null;
    }

    const cachedValue = window.localStorage.getItem('kangoo_checkout_existing_customer');

    if (cachedValue === '1') {
      return true;
    }

    if (cachedValue === '0') {
      return false;
    }

    return null;
  }

  function isStoredCheckoutEmailExistingCustomer() {
    if (document.body.classList.contains('logged-in')) {
      return true;
    }

    const email = getStoredCheckoutEmail();
    const cached = getCachedCheckoutExistingCustomerStatus(email);

    if (cached !== null) {
      return cached;
    }

    return Boolean(window.kangooRewards && window.kangooRewards.checkout_email_is_existing_customer);
  }

  function lookupExistingCustomerEmail(email) {
    const cleanEmail = String(email || '').trim();

    if (!isValidEmail(cleanEmail)) {
      return Promise.resolve(false);
    }

    const cached = getCachedCheckoutExistingCustomerStatus(cleanEmail);

    if (cached !== null) {
      return Promise.resolve(cached);
    }

    if (!window.kangooRewards || !window.kangooRewards.ajax_url || !window.kangooRewards.ajax_nonce) {
      return Promise.resolve(false);
    }

    if (existingCustomerLookupEmail === cleanEmail && existingCustomerLookupPromise) {
      return existingCustomerLookupPromise;
    }

    const formData = new FormData();
    formData.append('action', 'kangoo_check_existing_customer_email');
    formData.append('nonce', window.kangooRewards.ajax_nonce);
    formData.append('email', cleanEmail);

    existingCustomerLookupEmail = cleanEmail;
    existingCustomerLookupPromise = fetch(window.kangooRewards.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Could not check email.');
      }

      return response.json();
    }).then(function (payload) {
      const isExisting = Boolean(payload && payload.success && payload.data && payload.data.existing_customer);
      cacheCheckoutExistingCustomerStatus(cleanEmail, isExisting);

      if (window.kangooRewards) {
        window.kangooRewards.checkout_email_is_existing_customer = isExisting;
      }

      if (payload && payload.success && payload.data && payload.data.removed_first_order_coupon) {
        window.location.reload();
      }

      return isExisting;
    }).catch(function () {
      return false;
    }).finally(function () {
      existingCustomerLookupPromise = null;
    });

    return existingCustomerLookupPromise;
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
        dob: formattedDob,
        existingCustomer: Boolean(payload.data && payload.data.existing_customer),
        removedFirstOrderCoupon: Boolean(payload.data && payload.data.removed_first_order_coupon)
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
    if (!button) {
      return false;
    }

    const enabled = Boolean(isEnabled);
    button.classList.toggle('kangoo-checkout-disabled', !enabled);
    button.setAttribute('aria-disabled', enabled ? 'false' : 'true');

    if (!enabled) {
      button.setAttribute('data-kangoo-checkout-disabled', '1');
      button.setAttribute('title', 'Add your email and date of birth to continue.');
    } else {
      button.removeAttribute('data-kangoo-checkout-disabled');
      button.removeAttribute('title');
    }

    return enabled;
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
        '<strong>Step 1: Cart details</strong>',
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
        isValidEmail(storedEmail) && isValidCheckoutDob(storedDob) ? 'Details saved. Continue to secure checkout.' : 'Enter your email and date of birth to unlock checkout.',
        '</p>'
      ].join('');

      if (cartBlock && cartBlock.parentElement) {
        cartBlock.insertAdjacentElement('beforebegin', panel);
      } else {
        checkoutAnchor.insertAdjacentElement('beforebegin', panel);
      }
    }

    setupCartSidebarPanels();

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
      setupCartSidebarPanels();
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
      setMessage('Details saved. Continue to secure checkout.', false);
      updateSummaryDisplay(email, activeDob);

      if (window.kangooRewards) {
        window.kangooRewards.checkout_email = email;
        window.kangooRewards.checkout_dob = dob;
        window.kangooRewards.checkout_email_is_existing_customer = Boolean(payload && payload.existingCustomer);
      }

      cacheCheckoutExistingCustomerStatus(email, Boolean(payload && payload.existingCustomer));

      if (payload && payload.removedFirstOrderCoupon) {
        window.location.reload();
        return;
      }

      setupFreeShippingNudge();
      setupCartSidebarPanels();
    }

    function clearCheckoutLoadingState(button) {
      if (!button) {
        return;
      }

      button.classList.remove('is-loading', 'loading', 'wc-block-components-button--loading');
      button.removeAttribute('disabled');

      if (button.classList.contains('kangoo-checkout-disabled')) {
        button.setAttribute('aria-disabled', 'true');
      } else {
        button.removeAttribute('aria-disabled');
      }

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

  function getCartSidebar() {
    return document.querySelector('.woocommerce-cart .cart-collaterals, .woocommerce-cart .wc-block-cart__sidebar, .woocommerce-cart .wc-block-components-sidebar');
  }

  function getCartTotalsPanel(sidebar) {
    if (!sidebar) {
      return null;
    }

    const directTotals = sidebar.querySelector(':scope > .cart_totals, :scope > .wp-block-woocommerce-cart-order-summary-block');

    if (directTotals) {
      return directTotals;
    }

    const totalsTitle = sidebar.querySelector('.wc-block-cart__totals-title, .wp-block-woocommerce-cart-order-summary-heading-block');
    const totalsPanel = totalsTitle ? totalsTitle.closest('.wp-block-woocommerce-cart-order-summary-block, .wc-block-components-totals-wrapper, .wc-block-components-sidebar') : null;
    return totalsPanel && totalsPanel !== sidebar ? totalsPanel : null;
  }

  function setupCartSidebarPanels() {
    const cartPage = document.querySelector('.woocommerce-cart');

    if (!cartPage) {
      return;
    }

    const sidebar = getCartSidebar();

    if (!sidebar) {
      return;
    }

    const totals = getCartTotalsPanel(sidebar);
    const panelsBeforeTotals = [
      cartPage.querySelector('.kangoo-rewards-cart--cart'),
      cartPage.querySelector('[data-kangoo-cart-email-capture]'),
      cartPage.querySelector('[data-kangoo-free-shipping-nudge]:not(.kangoo-free-shipping-nudge--cart-drawer)')
    ].filter(Boolean);

    if (panelsBeforeTotals.length) {
      let panelsArePlaced = panelsBeforeTotals.every(function (panel) {
        return panel.parentElement === sidebar;
      });

      if (panelsArePlaced && totals) {
        let cursor = totals.previousElementSibling;

        for (let index = panelsBeforeTotals.length - 1; index >= 0; index -= 1) {
          if (cursor !== panelsBeforeTotals[index]) {
            panelsArePlaced = false;
            break;
          }

          cursor = cursor.previousElementSibling;
        }
      } else if (panelsArePlaced) {
        const sidebarChildren = Array.from(sidebar.children);
        const startIndex = sidebarChildren.length - panelsBeforeTotals.length;
        panelsArePlaced = startIndex >= 0 && panelsBeforeTotals.every(function (panel, index) {
          return sidebarChildren[startIndex + index] === panel;
        });
      }

      if (!panelsArePlaced) {
        const fragment = document.createDocumentFragment();

        panelsBeforeTotals.forEach(function (panel) {
          fragment.appendChild(panel);
        });

        sidebar.insertBefore(fragment, totals || null);
      }
    }

    const secureCheckout = cartPage.querySelector('.kangoo-cart-secure-checkout');

    if (!secureCheckout) {
      return;
    }

    if (totals && totals.parentElement === sidebar && totals.nextElementSibling !== secureCheckout) {
      totals.insertAdjacentElement('afterend', secureCheckout);
      return;
    }

    if (!totals && secureCheckout.parentElement !== sidebar) {
      sidebar.appendChild(secureCheckout);
    }
  }

  function replaceDirectText(element, pattern, replacement) {
    if (!element || element.dataset.kangooLabelReplaced === replacement) {
      return;
    }

    let replaced = false;

    Array.from(element.childNodes).forEach(function (node) {
      if (node.nodeType === window.Node.TEXT_NODE && pattern.test(node.textContent || '')) {
        node.textContent = node.textContent.replace(pattern, replacement);
        replaced = true;
      }
    });

    if (!replaced && pattern.test(element.textContent || '')) {
      const label = element.querySelector('span, strong');

      if (label && pattern.test(label.textContent || '')) {
        label.textContent = label.textContent.replace(pattern, replacement);
        replaced = true;
      }
    }

    if (replaced) {
      element.dataset.kangooLabelReplaced = replacement;
    }
  }

  function setupCartSummaryLabels() {
    const root = document.querySelector('.woocommerce-cart, .woocommerce-checkout');

    if (!root) {
      return;
    }

    root.querySelectorAll('button, summary, .wc-block-components-panel__button').forEach(function (element) {
      const text = String(element.textContent || '').replace(/\s+/g, ' ').trim();

      if (/^add coupons$/i.test(text)) {
        element.classList.add('kangoo-coupon-code-toggle');
        replaceDirectText(element, /add coupons/i, 'Coupon code');
      }
    });
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

  function getCheckoutStepDefinitions() {
    return [
      { key: 'cart', number: 1, title: 'Cart', subtitle: 'Review your order' },
      { key: 'details', number: 2, title: 'Details', subtitle: 'Email, DOB and delivery' },
      { key: 'verify', number: 3, title: 'Verify', subtitle: 'Photo ID and selfie' },
      { key: 'pay', number: 4, title: 'Pay', subtitle: 'Express checkout or card' }
    ];
  }

  function renderCheckoutProgress(activeKey) {
    const root = document.body.classList.contains('woocommerce-cart')
      ? document.querySelector('.woocommerce-cart')
      : document.querySelector('.woocommerce-checkout');

    if (!root) {
      return;
    }

    let progress = root.querySelector('[data-kangoo-checkout-progress]');
    const steps = getCheckoutStepDefinitions();

    if (!progress) {
      progress = document.createElement('nav');
      progress.className = 'kangoo-checkout-progress';
      progress.setAttribute('data-kangoo-checkout-progress', '');
      progress.setAttribute('aria-label', 'Checkout progress');
      progress.innerHTML = steps.map(function (step) {
        return [
          '<span class="kangoo-checkout-progress__step" data-kangoo-progress-step="', step.key, '">',
          '<span class="kangoo-checkout-progress__number">', step.number, '</span>',
          '<span class="kangoo-checkout-progress__copy">',
          '<strong>', step.title, '</strong>',
          '<small>', step.subtitle, '</small>',
          '</span>',
          '</span>'
        ].join('');
      }).join('');

      const heading = root.querySelector('.section-header, .entry-header, h1, .page-title');
      if (heading && heading.parentElement) {
        heading.insertAdjacentElement('afterend', progress);
      } else {
        root.insertAdjacentElement('afterbegin', progress);
      }
    }

    const activeIndex = steps.findIndex(function (step) {
      return step.key === activeKey;
    });

    progress.querySelectorAll('[data-kangoo-progress-step]').forEach(function (stepElement) {
      const stepIndex = steps.findIndex(function (step) {
        return step.key === stepElement.getAttribute('data-kangoo-progress-step');
      });

      stepElement.classList.toggle('is-active', stepIndex === activeIndex);
      stepElement.classList.toggle('is-complete', stepIndex >= 0 && stepIndex < activeIndex);
      stepElement.setAttribute('aria-current', stepIndex === activeIndex ? 'step' : 'false');
    });
  }

  function setupCartCheckoutProgress() {
    if (!document.body.classList.contains('woocommerce-cart')) {
      return;
    }

    renderCheckoutProgress('cart');
  }

  function setCheckoutStepInputValue(field, value) {
    if (!field || field.value === value) {
      return;
    }

    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(field, value);
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
    field.dispatchEvent(new Event('blur', { bubbles: true }));
  }

  function getCheckoutEmailField(checkout) {
    return checkout ? checkout.querySelector('#email, #billing_email, input[name="billing_email"], input[type="email"]') : null;
  }

  function isCheckoutVerificationComplete() {
    if (window.kangooAgeVerification && window.kangooAgeVerification.enabled) {
      return Boolean(window.kangooAgeVerification.verified) || document.documentElement.classList.contains('kangoo-av-verified');
    }

    return true;
  }

  function setupCheckoutMultistep() {
    if (!document.body.classList.contains('woocommerce-checkout')) {
      return;
    }

    // WooCommerce Blocks re-render their checkout tree frequently. Keep checkout
    // step UI passive here so the native block flow remains stable.
    renderCheckoutProgress('details');
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
        readMoreToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
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

  function formatDispatchCountdown(milliseconds) {
    const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return [hours, minutes, seconds].map(function (part) {
      return String(part).padStart(2, '0');
    }).join(':');
  }

  function setupProductDispatchTimers() {
    document.querySelectorAll('[data-product-dispatch-timer]').forEach(function (timer) {
      if (timer.dataset.dispatchTimerReady === '1') {
        return;
      }

      const countdown = timer.querySelector('[data-dispatch-countdown]');
      const label = timer.querySelector('[data-dispatch-label]');
      const target = parseInt(timer.getAttribute('data-dispatch-target') || '', 10);
      const activeLabel = timer.getAttribute('data-dispatch-label') || '';
      const completeLabel = timer.getAttribute('data-dispatch-complete-label') || '';

      if (!countdown || !target) {
        return;
      }

      timer.dataset.dispatchTimerReady = '1';

      function updateTimer() {
        const remaining = target - Date.now();

        if (remaining <= 0) {
          if (label && completeLabel) {
            label.textContent = completeLabel;
          }

          countdown.textContent = '00:00:00';
          timer.classList.add('is-ended');
          return;
        }

        if (label && activeLabel) {
          label.textContent = activeLabel;
        }

        countdown.textContent = formatDispatchCountdown(remaining);
        timer.classList.remove('is-ended');
        window.setTimeout(updateTimer, 1000);
      }

      updateTimer();
    });
  }

  setupCheckoutAgeVerificationRow();
  linkCheckoutTermsText();
  setupFreeShippingNudge();
  setupProductDispatchTimers();
  setupDeliveryOptionsInteractions();
  setupCheckoutDeliveryOptions();
  setupCartCheckoutProgress();
  setupCartEmailCapture();
  setupCartSidebarPanels();
  setupCartSummaryLabels();
  setupCheckoutEmailPrefill();
  setupCheckoutGuestNotice();
  setupCheckoutAddressToggle();
  setupCheckoutMultistep();
  setupCategoryMobileControls();
  setupThemePreferenceControls();
  setupThemePreferencePrompt();

  if (document.querySelector('.woocommerce-checkout, .woocommerce-cart')) {
    let checkoutMutationScheduled = false;
    const checkoutAgeObserver = new MutationObserver(function () {
      if (checkoutMutationScheduled) {
        return;
      }

      checkoutMutationScheduled = true;
      window.requestAnimationFrame(function () {
        checkoutMutationScheduled = false;
        setupCheckoutAgeVerificationRow();
        linkCheckoutTermsText();
        setupFreeShippingNudge();
        setupCheckoutDeliveryOptions();
        setupCartCheckoutProgress();
        setupCartEmailCapture();
        setupCartSidebarPanels();
        setupCartSummaryLabels();
        setupCheckoutEmailPrefill();
        setupCheckoutGuestNotice();
        setupCheckoutAddressToggle();
        setupCheckoutMultistep();
      });
    });
    checkoutAgeObserver.observe(document.body, { childList: true, subtree: true });
  }
});
