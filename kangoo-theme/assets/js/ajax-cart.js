// FILE: assets/js/ajax-cart.js
jQuery(function ($) {
  function getPriceEl($form) {
    return $form.closest('.product-page').find('#product-price').first();
  }

  function ensureDefaultPriceStored($price) {
    if (!$price.length) {
      return;
    }

    if (!$price.attr('data-default-html')) {
      $price.attr('data-default-html', $price.html());
    }
  }

  function syncStrengthPills($form) {
    $form.closest('.product-page').find('.strength-options').each(function () {
      const $group = $(this);
      const attribute = $group.data('attribute');

      if (!attribute) {
        return;
      }

      const $select = $form.find(`select[name="${attribute}"]`);

      if (!$select.length) {
        return;
      }

      const selectedValue = String($select.val() || '');

      $group.find('.strength-option').each(function () {
        const $button = $(this);
        const isActive = String($button.data('value')) === selectedValue;

        $button.toggleClass('is-active', isActive);
        $button.attr('aria-pressed', isActive ? 'true' : 'false');
      });
    });
  }

  function updateDisplayedPrice($form, variation) {
    const $price = getPriceEl($form);

    if (!$price.length) {
      return;
    }

    ensureDefaultPriceStored($price);

    if (variation && variation.price_html) {
      $price.html(variation.price_html);
      return;
    }

    $price.html($price.attr('data-default-html'));
  }

  function enhanceQuantityButtons() {
    $('.product-cart .quantity').each(function () {
      const $qty = $(this);

      if ($qty.find('.qty-btn').length) {
        return;
      }

      $qty.prepend('<button type="button" class="qty-btn qty-btn--minus" aria-label="Decrease quantity">-</button>');
      $qty.append('<button type="button" class="qty-btn qty-btn--plus" aria-label="Increase quantity">+</button>');
    });
  }

  function showTemporaryButtonState($button, html, duration) {
    if (!$button.length) {
      return;
    }

    if (!$button.data('original-html')) {
      $button.data('original-html', $button.html());
    }

    $button.addClass('is-added').html(html);

    window.setTimeout(function () {
      $button.removeClass('is-added').html($button.data('original-html'));
    }, duration || 1800);
  }

  $(document).on('click', '.strength-option', function () {
    const $button = $(this);
    const $group = $button.closest('.strength-options');
    const attribute = $group.data('attribute');
    const $form = $button.closest('.product-page').find('.variations_form').first();

    if (!attribute || !$form.length) {
      return;
    }

    const $select = $form.find(`select[name="${attribute}"]`);

    if (!$select.length) {
      return;
    }

    $select.val($button.data('value')).trigger('change');
  });

  $(document).on('woocommerce_variation_has_changed', '.variations_form', function () {
    syncStrengthPills($(this));
  });

  $(document).on('found_variation', '.variations_form', function (event, variation) {
    const $form = $(this);
    syncStrengthPills($form);
    updateDisplayedPrice($form, variation);
  });

  $(document).on('reset_data hide_variation', '.variations_form', function () {
    const $form = $(this);
    syncStrengthPills($form);
    updateDisplayedPrice($form, null);
  });

  $(document).on('click', '.qty-btn', function () {
    const $wrap = $(this).closest('.quantity');
    const $input = $wrap.find('input.qty');

    let value = parseInt($input.val(), 10) || 1;
    const min = parseInt($input.attr('min'), 10) || 1;
    const maxAttr = parseInt($input.attr('max'), 10);
    const max = Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : 999;

    if ($(this).hasClass('qty-btn--plus')) {
      value = Math.min(value + 1, max);
    } else {
      value = Math.max(value - 1, min);
    }

    $input.val(value).trigger('change');
  });

  $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
    if ($button && $button.length) {
      showTemporaryButtonState($button, '<span aria-hidden="true">âœ“</span> Added', 1800);
    }
  });

  $('.variations_form').each(function () {
    const $form = $(this);
    const $price = getPriceEl($form);

    ensureDefaultPriceStored($price);
    syncStrengthPills($form);
    updateDisplayedPrice($form, null);
  });

  enhanceQuantityButtons();
});

// Detect add-to-cart after page reload
jQuery(function ($) {

  const params = new URLSearchParams(window.location.search);

  if (params.has('add-to-cart')) {

    const $btn = $('.single_add_to_cart_button');

    if ($btn.length) {
      $btn.addClass('is-added').html('<span>âœ“</span> Added');

      setTimeout(() => {
        $btn.removeClass('is-added').html('Add to cart');
      }, 1800);
    }

    // ðŸ”¥ FUTURE: open cart drawer here
    if (typeof openCartDrawer === 'function') {
      openCartDrawer();
    }

  }

});

// FORCE REMOVE FULL PAGE RELOAD (fix infinite loader)
jQuery(function ($) {

  $(document).on('click', '.woocommerce-mini-cart-item .remove', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const url = this.getAttribute('href');

    if (url) {
      // remember to reopen drawer AFTER reload
      sessionStorage.setItem('reopen_cart_drawer', '1');

      window.location.href = url;
    }
  });

});

jQuery(function () {

  if (sessionStorage.getItem('reopen_cart_drawer') === '1') {

    sessionStorage.removeItem('reopen_cart_drawer');

    const drawer = document.getElementById('cart-drawer');

    if (drawer) {
      setTimeout(() => {
        drawer.classList.add('is-open');
      }, 150); // small delay feels nicer
    }

  }

});


// ===============================
// CART DRAWER QUANTITY CONTROLS
// ===============================

jQuery(function ($) {

  function enhanceMiniCartQty() {

    $('.cart-drawer .quantity').each(function () {
      const $qty = $(this);

      if ($qty.find('.qty-btn').length) return;

      const text = $qty.text(); // "2 × £3.99"

      // extract number
      const match = text.match(/^(\d+)/);
      const value = match ? parseInt(match[1]) : 1;

      // extract remove URL (used for updating)
      const $item = $qty.closest('.woocommerce-mini-cart-item');
      const removeUrl = $item.find('.remove').attr('href');

      $qty.html(`
        <button type="button" class="qty-btn qty-btn--minus">-</button>
        <input type="number" class="mini-qty" value="${value}" min="1">
        <button type="button" class="qty-btn qty-btn--plus">+</button>
      `);

      $qty.attr('data-remove-url', removeUrl);
    });

  }

  // run on load
  enhanceMiniCartQty();

  // run after cart updates (future-proof)
  $(document.body).on('wc_fragments_refreshed', enhanceMiniCartQty);

});

jQuery(function ($) {

  $(document).on('click', '.cart-drawer .qty-btn', function () {

    const $wrap = $(this).closest('.quantity');
    const $input = $wrap.find('.mini-qty');

    let val = parseInt($input.val(), 10) || 1;

    if ($(this).hasClass('qty-btn--plus')) {
      val++;
    } else {
      val = Math.max(1, val - 1);
    }

    $input.val(val);

  });

});

jQuery(function ($) {

  $(document).on('change', '.cart-drawer .mini-qty', function () {

    const $input = $(this);
    const qty = $input.val();

    const $wrap = $input.closest('.quantity');
    const removeUrl = $wrap.data('remove-url');

    if (!removeUrl) return;

    // extract cart item key
    const url = new URL(removeUrl, window.location.origin);
    const key = url.searchParams.get('remove_item');

    if (!key) return;

    // store reopen flag
    sessionStorage.setItem('reopen_cart_drawer', '1');

    // redirect to update quantity
    window.location.href = `/?update_cart=${key}&quantity=${qty}`;

  });

});