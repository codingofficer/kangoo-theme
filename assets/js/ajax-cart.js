// FILE: assets/js/ajax-cart.js
jQuery(function ($) {
  const currentCount = parseInt($('.cart-badge').first().text() || '0', 10);
  sessionStorage.setItem('kangoo_cart_count', currentCount);
});

window.kangooAddFeedbackDelay = 1200;
window.kangooLimitedStockFeedbackDelay = 4200;
window.kangooLowStockPublicThreshold = 3;

window.kangooSparkButton = function (button) {
  const $ = jQuery;
  const $button = $(button);

  if (!$button.length) {
    return;
  }

  $button.find('.kangoo-button-sparks').remove();
  $button.addClass('kangoo-spark-host');

  const $sparks = $('<span class="kangoo-button-sparks" aria-hidden="true"></span>');

  for (let index = 0; index < 12; index += 1) {
    const angle = (index * 30) - 90;
    const distance = 34 + (index % 4) * 7;
    const delay = (index % 3) * 18;
    $sparks.append($('<i></i>').attr('style', '--spark-angle:' + angle + 'deg;--spark-distance:' + distance + 'px;--spark-delay:' + delay + 'ms;'));
  }

  $button.append($sparks);

  window.setTimeout(function () {
    $sparks.remove();
  }, 900);
};

window.kangooSparkCartIcon = function () {
  const $ = jQuery;
  const $icons = $('.kangoo-mobile-cart-sticky__icon:visible, .site-header__cart:visible .cart-icon');

  if (!$icons.length) {
    return;
  }

  $icons.each(function () {
    const $icon = $(this);
    const $sparks = $('<span class="kangoo-cart-icon-sparks" aria-hidden="true"></span>');
    const points = [
      ['8%', '18%', '0ms', '0.9'],
      ['22%', '82%', '70ms', '0.72'],
      ['72%', '8%', '95ms', '0.8'],
      ['88%', '34%', '35ms', '0.58'],
      ['78%', '84%', '120ms', '0.68'],
      ['-4%', '52%', '150ms', '0.52'],
      ['102%', '66%', '180ms', '0.55']
    ];

    $icon.find('.kangoo-cart-icon-sparks').remove();
    $icon.addClass('is-sparking');

    points.forEach(function (point) {
      $sparks.append(
        $('<i></i>').attr(
          'style',
          '--spark-x:' + point[0] + ';--spark-y:' + point[1] + ';--spark-delay:' + point[2] + ';--spark-scale:' + point[3] + ';'
        )
      );
    });

    $icon.append($sparks);

    window.setTimeout(function () {
      $sparks.remove();
      $icon.removeClass('is-sparking');
    }, 950);
  });
};

window.kangooOpenCartDrawerIfWasEmpty = function (fragments) {
  const $ = jQuery;

  const previousCount = parseInt(sessionStorage.getItem('kangoo_cart_count') || '0', 10);

  let currentCount = parseInt($('.cart-badge').first().text() || '0', 10);

  if (fragments && fragments['.cart-badge']) {
    const fragmentCount = parseInt($('<div>').html(fragments['.cart-badge']).text() || '0', 10);

    if (!Number.isNaN(fragmentCount)) {
      currentCount = fragmentCount;
    }
  }

  sessionStorage.setItem('kangoo_cart_count', currentCount);

  if (previousCount === 0 && currentCount > 0) {
    const $drawer = $('#cart-drawer');

    if ($drawer.length) {
      $drawer.addClass('is-open');
      $('body').addClass('no-scroll');
    }
  }
};

jQuery(function ($) {
  const singleAddLabel = 'ADD TO CART';

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

	function parsePriceNumber(input) {
	  const $html = $('<div>').html(input || '');

	  let text = '';

	  const $salePrice = $html.find('ins .woocommerce-Price-amount, ins bdi').last();

	  if ($salePrice.length) {
		text = $.trim($salePrice.text());
	  } else {
		const $regularPrice = $html.find('.woocommerce-Price-amount, bdi').last();

		if ($regularPrice.length) {
		  text = $.trim($regularPrice.text());
		} else {
		  text = $.trim($html.text());
		}
	  }

	  if (!text) {
		return null;
	  }

	  const match = text.match(/-?\d[\d,.]*/);

	  if (!match) {
		return null;
	  }

	  let value = match[0];

	  if (value.indexOf(',') > -1 && value.indexOf('.') > -1) {
		value = value.replace(/,/g, '');
	  } else {
		value = value.replace(/,/g, '.');
	  }

	  const parsed = parseFloat(value);

	  return Number.isFinite(parsed) ? parsed : null;
	}

  function formatCurrency(amount) {
    if (!Number.isFinite(amount)) {
      return '';
    }

    try {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
      }).format(amount);
    } catch (error) {
      return `£${amount.toFixed(2)}`;
    }
  }

	function buildButtonHtml(label, total) {
	  const text = total ? `${label} \u00b7 ${total}` : label;
	  return `<span class="button-text">${text}</span>`;
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

  function getCurrentSingleUnitPrice($form, variation) {
    if (variation && typeof variation.display_price === 'number') {
      return variation.display_price;
    }

    if ($form.hasClass('variations_form')) {
      const variationId = parseInt($form.find('input[name="variation_id"]').val(), 10) || 0;

      if (!variationId) {
        return null;
      }
    }

    const $price = getPriceEl($form);
    if (!$price.length) {
      return null;
    }

    return parsePriceNumber($price.html());
  }

  function syncSingleVariationStockNote(variation) {
    const $note = $('[data-product-stock-note]').first();

    if (!$note.length) {
      return;
    }

    if ($note.attr('data-suppress-stock-note') === '1') {
      $note.prop('hidden', true).removeClass('product-stock-note--low').text('');
      return;
    }

    const maxQty = variation ? parseInt(variation.max_qty, 10) : NaN;
    const threshold = window.kangooLowStockPublicThreshold || 3;

    if (Number.isFinite(maxQty) && maxQty > 0 && maxQty < threshold) {
      $note
        .prop('hidden', false)
        .addClass('product-stock-note--low')
        .text('Low stock: only ' + maxQty + ' left');
      return;
    }

    $note.prop('hidden', true).removeClass('product-stock-note--low').text('');
  }

  function syncSingleAddButtonTotal($form, variation) {
    const $button = $form.find('.single_add_to_cart_button').first();

    if (!$button.length) {
      return;
    }
	  
   if ($button.hasClass('disabled') || $button.is(':disabled')) {
     $button.html('<span class="button-text">SOLD OUT</span>');
     return;
   }

    const $qtyInput = $form.find('input.qty');
    const maxAttr = parseInt($qtyInput.attr('max'), 10);
    const max = Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : null;
    const qty = max === null
      ? Math.max(1, parseInt($qtyInput.val(), 10) || 1)
      : Math.min(max, Math.max(1, parseInt($qtyInput.val(), 10) || 1));

    if (parseInt($qtyInput.val(), 10) !== qty) {
      $qtyInput.val(qty);
    }
    const unitPrice = getCurrentSingleUnitPrice($form, variation);

    if (!Number.isFinite(unitPrice)) {
      const idleHtml = buildButtonHtml(singleAddLabel, '');
      $button.data('idle-html', idleHtml);

      if (!$button.hasClass('is-loading') && !$button.hasClass('is-added')) {
        $button.html(idleHtml);
      }

      return;
    }

    const total = formatCurrency(unitPrice * qty);
    const idleHtml = buildButtonHtml(singleAddLabel, total);

    $button.data('idle-html', idleHtml);

    if (!$button.hasClass('is-loading') && !$button.hasClass('is-added')) {
      $button.html(idleHtml);
    }
  }

  function enhanceQuantityButtons() {
    $('.product-cart .quantity').each(function () {
      const $qty = $(this);

      if ($qty.find('.qty-btn').length) {
        syncProductQuantityButtons($qty);
        return;
      }

      $qty.prepend('<button type="button" class="qty-btn qty-btn--minus" aria-label="Decrease quantity">-</button>');
      $qty.append('<button type="button" class="qty-btn qty-btn--plus" aria-label="Increase quantity">+</button>');
      syncProductQuantityButtons($qty);
    });
  }

  function syncProductQuantityButtons($qty) {
    const $input = $qty.find('input.qty');

    if (!$input.length) {
      return;
    }

    const value = parseInt($input.val(), 10) || 1;
    const min = parseInt($input.attr('min'), 10) || 1;
    const maxAttr = parseInt($input.attr('max'), 10);
    const max = Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : null;

    $qty.find('.qty-btn--minus').prop('disabled', value <= min);
    $qty.find('.qty-btn--plus').prop('disabled', max !== null && value >= max);
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
    }, duration || 1200);
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
    syncSingleAddButtonTotal($(this), null);
  });

	$(document).on('found_variation', '.variations_form', function (event, variation) {
	  const $form = $(this);
	  const $button = $form.find('.single_add_to_cart_button').first();

	  syncStrengthPills($form);
	  updateDisplayedPrice($form, variation);
      syncSingleVariationStockNote(variation);

	  if (variation && variation.is_in_stock === false) {
		$button
		  .addClass('disabled is-disabled')
		  .prop('disabled', true)
		  .html('<span class="button-text">SOLD OUT</span>');

		return;
	  }

	  $button
		.removeClass('disabled is-disabled')
		.prop('disabled', false);

	  syncSingleAddButtonTotal($form, variation);
	});

  $(document).on('reset_data hide_variation', '.variations_form', function () {
    const $form = $(this);
    syncStrengthPills($form);
    updateDisplayedPrice($form, null);
    syncSingleVariationStockNote(null);
    syncSingleAddButtonTotal($form, null);
  });

  $(document).on('click', '.product-cart .qty-btn', function () {
    const $wrap = $(this).closest('.quantity');
    const $input = $wrap.find('input.qty');

    if (!$input.length) {
      return;
    }

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
    syncProductQuantityButtons($wrap);
  });

  $(document).on('change input', '.product-cart .quantity input.qty', function () {
    syncProductQuantityButtons($(this).closest('.quantity'));
  });

  $(document).on('change input', '.product-cart input.qty', function () {
    const $form = $(this).closest('form.cart');

    if (!$form.length) {
      return;
    }

    syncSingleAddButtonTotal($form, null);
  });

  $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
    window.setTimeout(function () {
      if (window.kangooSparkCartIcon) {
        window.kangooSparkCartIcon();
      }
    }, 80);

    if ($button && $button.length && !$button.hasClass('single_add_to_cart_button') && !$button.is('[data-card-add]')) {
      showTemporaryButtonState($button, 'Added ✓', 1200);
    }
  });

  $('.variations_form').each(function () {
    const $form = $(this);
    const $price = getPriceEl($form);

    ensureDefaultPriceStored($price);
    syncStrengthPills($form);
    updateDisplayedPrice($form, null);
    syncSingleAddButtonTotal($form, null);
  });

  $('.product-page .product-cart form.cart').each(function () {
    syncSingleAddButtonTotal($(this), null);
  });

  enhanceQuantityButtons();
});

jQuery(function ($) {
  let cartFragmentRefreshTimer = null;
  let cartFragmentRefreshInFlight = false;

  function applyCartFragments(fragments) {
    $.each(fragments || {}, function (selector, html) {
      $(selector).replaceWith(html);
    });

    $(document.body).trigger('wc_fragments_refreshed');
  }

  window.kangooApplyCartFragments = applyCartFragments;

  function refreshCartDrawerFragments() {
    if (!window.kangooAjaxCart || !kangooAjaxCart.ajax_url || cartFragmentRefreshInFlight) {
      return;
    }

    cartFragmentRefreshInFlight = true;

    $.ajax({
      url: kangooAjaxCart.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'kangoo_get_cart_fragments'
      }
    }).done(function (response) {
      if (response && response.success && response.data && response.data.fragments) {
        applyCartFragments(response.data.fragments);
      }
    }).always(function () {
      cartFragmentRefreshInFlight = false;
    });
  }

  function scheduleCartDrawerFragmentRefresh() {
    window.clearTimeout(cartFragmentRefreshTimer);
    cartFragmentRefreshTimer = window.setTimeout(refreshCartDrawerFragments, 120);
  }

  $(document.body).on('updated_wc_div updated_cart_totals', function () {
    if ($('body').hasClass('woocommerce-cart')) {
      scheduleCartDrawerFragmentRefresh();
    }
  });

  $(document).on('click', '[data-cart-sticky-open]', function () {
    const $drawer = $('#cart-drawer');

    if (!$drawer.length) {
      return;
    }

    $drawer.addClass('is-open');
    $('body').addClass('no-scroll');
  });

  function positionClearCartButton() {
    if ($('.cart-drawer-summary [data-cart-clear]').length) {
      return;
    }

    let $button = $('[data-cart-clear]').first();
    const $total = $('.cart-drawer .woocommerce-mini-cart__total').first();

    if (!$total.length || $total.find('[data-cart-clear]').length) {
      return;
    }

    if (!$button.length) {
      $button = $('<button type="button" class="cart-drawer__clear" data-cart-clear>Clear</button>');
    }

    $total.append($button);
  }

  function syncClearCartButton() {
    positionClearCartButton();

    const count = parseInt($('.cart-badge').first().text() || '0', 10) || 0;

    const isLockedCartContext = $('body').hasClass('woocommerce-checkout') || $('body').hasClass('woocommerce-cart');

    $('[data-cart-clear]').toggle(count > 0 && !isLockedCartContext);
  }

  syncClearCartButton();

  $(document.body).on('added_to_cart wc_fragments_refreshed', syncClearCartButton);

  $(document).on('click', '[data-cart-clear]', function () {
    const $modal = $('#cart-clear-confirm');

    if ($modal.length) {
      $modal.addClass('is-open').attr('aria-hidden', 'false');
      $('body').addClass('no-scroll');
      return;
    }

    clearCart();
  });

  $(document).on('click', '[data-cart-clear-cancel]', function () {
    $('#cart-clear-confirm').removeClass('is-open').attr('aria-hidden', 'true');
    $('body').removeClass('no-scroll');
  });

  $(document).on('click', '[data-cart-clear-confirm]', function () {
    clearCart();
  });

  $(document).on('keydown', function (event) {
    if (event.key === 'Escape') {
      $('#cart-clear-confirm').removeClass('is-open').attr('aria-hidden', 'true');
      $('body').removeClass('no-scroll');
    }
  });

  function clearCart() {
    if (!window.kangooAjaxCart || !kangooAjaxCart.ajax_url || !kangooAjaxCart.clear_nonce) {
      return;
    }

    const $button = $('[data-cart-clear-confirm]');
    const $drawer = $('#cart-drawer');
    const $content = $drawer.find('.cart-drawer__content');
    const $cartBadge = $('.cart-badge');

    $button.prop('disabled', true).css('opacity', 0.45);

    $.ajax({
      url: kangooAjaxCart.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'kangoo_clear_cart',
        nonce: kangooAjaxCart.clear_nonce
      }
    }).done(function (response) {
      if (!response || !response.success || !response.data) {
        return;
      }

      if (response.data.fragments && window.kangooApplyCartFragments) {
        window.kangooApplyCartFragments(response.data.fragments);
      } else if (response.data.mini_cart_html) {
        $content.html(response.data.mini_cart_html);
      }

      if (!response.data.fragments && response.data.cart_badge_html) {
        $cartBadge.replaceWith(response.data.cart_badge_html);
      }

      if (!response.data.fragments) {
        $(document.body).trigger('wc_fragments_refreshed');
      }
      syncClearCartButton();
      $('#cart-clear-confirm').removeClass('is-open').attr('aria-hidden', 'true');
      $('body').removeClass('no-scroll');
    }).always(function () {
      $button.prop('disabled', false).css('opacity', '');
    });
  }
});

jQuery(function ($) {
  function isCheckoutPage() {
    return $('body').hasClass('woocommerce-checkout') || $('body').hasClass('woocommerce-order-pay') || $('body').hasClass('woocommerce-cart');
  }

  $(document).on('click', '.cart-drawer .woocommerce-mini-cart-item .remove, .cart-drawer .mini-cart-remove', function (e) {
    if (isCheckoutPage()) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    e.preventDefault();
    e.stopPropagation();

    if (!window.kangooAjaxCart || !kangooAjaxCart.ajax_url || !kangooAjaxCart.remove_nonce) {
      return;
    }

    const url = this.getAttribute('href');
    if (!url) {
      return;
    }

    const parsedUrl = new URL(url, window.location.origin);
    const key = parsedUrl.searchParams.get('remove_item');

    if (!key) {
      return;
    }

    const $drawer = $('#cart-drawer');
    const $content = $drawer.find('.cart-drawer__content');
    const $cartBadge = $('.cart-badge');
    const $remove = $(this);

    $remove.css('pointer-events', 'none').css('opacity', 0.45);

    $.ajax({
      url: kangooAjaxCart.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'kangoo_remove_mini_cart_item',
        nonce: kangooAjaxCart.remove_nonce,
        cart_item_key: key
      }
    }).done(function (response) {
      if (!response || !response.success || !response.data) {
        return;
      }

      if (response.data.fragments && window.kangooApplyCartFragments) {
        window.kangooApplyCartFragments(response.data.fragments);
      } else if (response.data.mini_cart_html) {
        $content.html(response.data.mini_cart_html);
      }

      if (!response.data.fragments && response.data.cart_badge_html) {
        $cartBadge.replaceWith(response.data.cart_badge_html);
      }

      if (!response.data.fragments) {
        $(document.body).trigger('wc_fragments_refreshed');
      }
    }).always(function () {
      $remove.css('pointer-events', '').css('opacity', '');
    });
  });
});

jQuery(function ($) {
  function isCheckoutPage() {
    return $('body').hasClass('woocommerce-checkout') || $('body').hasClass('woocommerce-order-pay') || $('body').hasClass('woocommerce-cart');
  }

  function getMiniCartMax($wrap) {
    const maxAttr = parseInt($wrap.attr('data-stock-limit'), 10);

    return Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : null;
  }

  function parseMiniCartMoney(text) {
    const matches = String(text || '').match(/-?\d[\d,.]*/g);

    if (!matches || !matches.length) {
      return null;
    }

    let value = matches[matches.length - 1];

    if (value.indexOf(',') > -1 && value.indexOf('.') > -1) {
      value = value.replace(/,/g, '');
    } else {
      value = value.replace(/,/g, '.');
    }

    const parsed = parseFloat(value);

    return Number.isFinite(parsed) ? parsed : null;
  }

  function formatMiniCartMoney(amount) {
    try {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
      }).format(amount);
    } catch (error) {
      return 'GBP ' + amount.toFixed(2);
    }
  }

  function enhanceMiniCartQty() {
    if (isCheckoutPage()) {
      return;
    }

    $('.cart-drawer .woocommerce-mini-cart-item').each(function () {
      const $item = $(this);
      const $qty = $item.find('.quantity').first();

      if (!$qty.length || $item.find('.mini-cart-remove').length) {
        return;
      }

      const text = $.trim($qty.text());
      const match = text.match(/^(\d+)/);
      const value = match ? parseInt(match[1], 10) : 1;
      const max = getMiniCartMax($qty);
      const clampedValue = max === null ? value : Math.min(value, max);
      const unitPrice = parseMiniCartMoney(text);
      const exactLineTotalText = $.trim($qty.attr('data-line-total-text') || '');
      const exactLineTotal = parseFloat($qty.attr('data-line-total') || '');
      const $title = $item.find('a:not(.remove):not(.mini-cart-remove)').first();

      const $remove = $item.find('.remove').first();
      const removeUrl = $remove.attr('href') || '';

      if ($title.length && (exactLineTotalText || Number.isFinite(exactLineTotal) || unitPrice !== null)) {
        let $lineTotal = $item.find('.mini-cart-line-total').first();

        if (!$lineTotal.length) {
          $lineTotal = $('<span class="mini-cart-line-total"></span>');
          $title.after($lineTotal);
        }

        if (exactLineTotalText) {
          $lineTotal.text(exactLineTotalText);
        } else if (Number.isFinite(exactLineTotal)) {
          $lineTotal.text(formatMiniCartMoney(exactLineTotal));
        } else {
          $lineTotal.text(formatMiniCartMoney(unitPrice * clampedValue));
        }
      }

      $qty.html(`
        <button type="button" class="qty-btn qty-btn--minus" aria-label="Decrease quantity">-</button>
        <input type="number" class="mini-qty" value="${clampedValue}" min="1" ${max === null ? '' : 'max="' + max + '"'} aria-label="Quantity">
        <button type="button" class="qty-btn qty-btn--plus" aria-label="Increase quantity" ${max !== null && clampedValue >= max ? 'disabled' : ''}>+</button>
      `);

      $qty.attr('data-remove-url', removeUrl);

      if (removeUrl) {
        $qty.after(`
          <a href="${removeUrl}" class="mini-cart-remove" aria-label="Remove item from cart">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path d="M9 3h6l1 2h4v2H4V5h4l1-2zm1 6h2v8h-2V9zm4 0h2v8h-2V9zM7 9h2v8H7V9zm1 11h8a2 2 0 0 0 2-2V8H6v10a2 2 0 0 0 2 2z" fill="currentColor"></path>
            </svg>
          </a>
        `);
      }

      $remove.remove();
    });
  }

  function scheduleEnhanceMiniCartQty() {
    enhanceMiniCartQty();
    window.setTimeout(enhanceMiniCartQty, 50);
    window.setTimeout(enhanceMiniCartQty, 150);
    window.setTimeout(enhanceMiniCartQty, 300);
  }

  scheduleEnhanceMiniCartQty();

  $(window).on('load', function () {
    scheduleEnhanceMiniCartQty();
  });

  $(document.body).on('wc_fragments_refreshed added_to_cart', function () {
    scheduleEnhanceMiniCartQty();
  });
});

jQuery(function ($) {
  function showCardStockMessage($button, message) {
    const $card = $button.closest('.product-card');
    let $message = $card.find('[data-card-stock-message]');

    if (!$message.length) {
      $message = $('<div class="product-card__stock product-card__stock--low" data-card-stock-message></div>');
      $button.before($message);
    }

    $message.text(message || 'Limited availability for this product.').show();

    window.setTimeout(function () {
      $message.fadeOut(180);
    }, window.kangooLimitedStockFeedbackDelay || 4200);
  }

  function restoreCardAddButton($button, fallbackHtml) {
    const $card = $button.closest('.product-card');

    $button
      .removeClass('is-loading is-added')
      .removeData('request-lock');

    if (typeof window.kangooUpdateProductCardButton === 'function' && $card.length) {
      window.kangooUpdateProductCardButton($card);
      return;
    }

    $button.html(fallbackHtml);
  }

  function scheduleCardAddButtonRestore($button, fallbackHtml, delay) {
    const existingTimer = $button.data('kangoo-reset-timer');

    if (existingTimer) {
      window.clearTimeout(existingTimer);
    }

    const timer = window.setTimeout(function () {
      $button.removeData('kangoo-reset-timer');
      restoreCardAddButton($button, fallbackHtml);
    }, delay);

    $button.data('kangoo-reset-timer', timer);
  }

  $(document.body)
    .off('click.kangooCardAdd', '[data-card-add]')
    .on('click.kangooCardAdd', '[data-card-add]', function (event) {
      const $button = $(this);

      event.preventDefault();
      event.stopImmediatePropagation();

      if (!window.kangooAjaxCart || !kangooAjaxCart.ajax_url || !kangooAjaxCart.add_to_cart_nonce) {
        showCardStockMessage($button, 'Cart is unavailable. Please refresh and try again.');
        return false;
      }

      if ($button.data('request-lock') || $button.hasClass('is-disabled') || $button.is(':disabled')) {
        return false;
      }

      const productId = $button.data('product_id') || $button.attr('data-product_id');
      const quantity = Math.max(1, parseInt($button.attr('data-quantity'), 10) || 1);

      if (!productId) {
        return false;
      }

      const originalHtml = $button.html();
      const existingTimer = $button.data('kangoo-reset-timer');

      if (existingTimer) {
        window.clearTimeout(existingTimer);
        $button.removeData('kangoo-reset-timer');
      }

      $button
        .data('request-lock', true)
        .removeClass('is-added')
        .addClass('is-loading');

      $.ajax({
        type: 'POST',
        url: kangooAjaxCart.ajax_url,
        dataType: 'json',
        data: {
          action: 'kangoo_ajax_add_to_cart',
          nonce: kangooAjaxCart.add_to_cart_nonce,
          product_id: productId,
          quantity: quantity
        }
      }).done(function (response) {
        const payloadData = response && response.data ? response.data : {};
        const fragments = payloadData.fragments || {};

        if (!response || response.success === false || !Object.keys(fragments).length) {
          showCardStockMessage($button, payloadData.message);
          $button.removeClass('is-loading').html('Limited stock');

          scheduleCardAddButtonRestore($button, originalHtml, window.kangooLimitedStockFeedbackDelay || 4200);
          return;
        }

        $.each(fragments, function (selector, html) {
          $(selector).replaceWith(html);
        });

        $(document.body).trigger('added_to_cart', [fragments, payloadData.cart_hash, $button]);
        $(document.body).trigger('wc_fragments_refreshed');

        $button.removeClass('is-loading').addClass('is-added').html('ADDED \u2713');
        window.kangooSparkButton($button);

        scheduleCardAddButtonRestore($button, originalHtml, window.kangooAddFeedbackDelay || 1800);
      }).fail(function (xhr) {
        const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
        const message = response && response.data && response.data.message ? response.data.message : 'Limited availability for this product.';

        showCardStockMessage($button, message);
        $button.removeClass('is-loading').html('Limited stock');

        scheduleCardAddButtonRestore($button, originalHtml, window.kangooLimitedStockFeedbackDelay || 4200);
      }).always(function () {
        $button.removeClass('is-loading');
      });

      return false;
    });
});

jQuery(function ($) {
  function isCheckoutPage() {
    return $('body').hasClass('woocommerce-checkout') || $('body').hasClass('woocommerce-order-pay') || $('body').hasClass('woocommerce-cart');
  }

  $(document).on('click', '.cart-drawer .qty-btn', function () {
    if (isCheckoutPage()) {
      return;
    }

    const $wrap = $(this).closest('.quantity');
    const $input = $wrap.find('.mini-qty');

    if (!$input.length) {
      return;
    }

    let val = parseInt($input.val(), 10) || 1;
    const maxAttr = parseInt($input.attr('max') || $wrap.attr('data-stock-limit'), 10);
    const max = Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : null;

    if ($(this).hasClass('qty-btn--plus')) {
      val += 1;
    } else {
      val = Math.max(1, val - 1);
    }

    if (max !== null) {
      val = Math.min(val, max);
    }

    $input.val(val).trigger('change');
  });
});

jQuery(function ($) {
  function isCheckoutPage() {
    return $('body').hasClass('woocommerce-checkout') || $('body').hasClass('woocommerce-order-pay') || $('body').hasClass('woocommerce-cart');
  }

  $(document).on('change', '.cart-drawer .mini-qty', function () {
    if (isCheckoutPage()) {
      return;
    }

    const $input = $(this);
    const $wrap = $input.closest('.quantity');
    const maxAttr = parseInt($input.attr('max') || $wrap.attr('data-stock-limit'), 10);
    const max = Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : null;
    const qty = max === null
      ? Math.max(1, parseInt($input.val(), 10) || 1)
      : Math.min(max, Math.max(1, parseInt($input.val(), 10) || 1));

    $input.val(qty);

    const removeUrl = $wrap.data('remove-url');

    if (!removeUrl || !window.kangooAjaxCart) {
      return;
    }

    const url = new URL(removeUrl, window.location.origin);
    const key = url.searchParams.get('remove_item');

    if (!key) {
      return;
    }

    const $drawer = $('#cart-drawer');
    const $content = $drawer.find('.cart-drawer__content');
    const $cartBadge = $('.cart-badge');

    $input.prop('disabled', true);
    $wrap.addClass('is-updating');

    $.ajax({
      url: kangooAjaxCart.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'kangoo_update_mini_cart_quantity',
        nonce: kangooAjaxCart.nonce,
        cart_item_key: key,
        quantity: qty
      }
    }).done(function (response) {
      if (!response || !response.success || !response.data) {
        return;
      }

      if (response.data.fragments && window.kangooApplyCartFragments) {
        window.kangooApplyCartFragments(response.data.fragments);
      } else if (response.data.mini_cart_html) {
        $content.html(response.data.mini_cart_html);
      }

      if (!response.data.fragments && response.data.cart_badge_html) {
        $cartBadge.replaceWith(response.data.cart_badge_html);
      }

      if (!response.data.fragments) {
        $(document.body).trigger('wc_fragments_refreshed');
      }
    }).fail(function () {
      window.location.reload();
    }).always(function () {
      $input.prop('disabled', false);
      $wrap.removeClass('is-updating');
    });
  });
});

jQuery(function ($) {
  $(document)
    .off('submit.kangooSingleAddBlock', 'body.single-product form.cart')
    .on('submit.kangooSingleAddBlock', 'body.single-product form.cart', function (event) {
      const $form = $(this);

      if ($form.data('is-adding')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
      }
    });
});

jQuery(function ($) {
  const singleAddLabel = 'ADD TO CART';
  const singleAddedLabel = 'ADDED ✓';

	function openCartDrawer(fragments) {
	  window.kangooOpenCartDrawerIfWasEmpty(fragments);
	}

  function resyncSingleProductButton($form) {
    if (!$form || !$form.length) {
      return;
    }

    const $qty = $form.find('input.qty').first();

    if ($qty.length) {
      $qty.trigger('change');
    }

    $(document.body).trigger('kangoo_sync_pack_pricing', [$form]);
  }

  function showSingleStockMessage(message) {
    const $note = $('[data-product-stock-note]').first();
    const text = message || 'Limited availability for this product.';

    if (!$note.length) {
      window.alert(text);
      return;
    }

    $note
      .prop('hidden', false)
      .addClass('product-stock-note--low')
      .text(text);

    window.setTimeout(function () {
      $note.prop('hidden', true).text('');
    }, window.kangooLimitedStockFeedbackDelay || 4200);
  }

  // Prevent native form submission from firing in parallel with AJAX add-to-cart.
  $('body.single-product form.cart .single_add_to_cart_button').attr('type', 'button');

  $(document)
    .off('click.kangooSingleAdd', 'body.single-product form.cart .single_add_to_cart_button')
    .on('click.kangooSingleAdd', 'body.single-product form.cart .single_add_to_cart_button', function (event) {
      const $button = $(this);
      const $form = $button.closest('form.cart');

      if (!$button.length || $button.is(':disabled') || $button.hasClass('disabled')) {
        return;
      }

      if (!window.kangooAjaxCart || !kangooAjaxCart.ajax_url || !kangooAjaxCart.add_to_cart_nonce) {
        return;
      }

      if ($form.data('is-adding') || $button.data('request-lock')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
      }

      const payload = $form.serializeArray().filter(function (item) {
        return item.name !== 'add-to-cart';
      });
      const hasProductId = payload.some(function (item) {
        return item.name === 'product_id';
      });

      if (!hasProductId) {
        payload.push({
          name: 'product_id',
          value: $form.find('input[name="add-to-cart"]').val() || $button.val()
        });
      }

	  $button.data('original-html', $button.data('idle-html') || $button.html());

      event.preventDefault();
      event.stopImmediatePropagation();

      $button.data('request-lock', true);
      $form.data('is-adding', true);
      $button
        .removeClass('is-added')
        .addClass('is-loading');

      payload.push(
        {
          name: 'action',
          value: 'kangoo_ajax_add_to_cart'
        },
        {
          name: 'nonce',
          value: kangooAjaxCart.add_to_cart_nonce
        }
      );

      console.log('[kangooSingleAdd] request', {
        productId: $form.find('input[name="product_id"]').val() || $form.find('input[name="add-to-cart"]').val() || $button.val(),
        variationId: $form.find('input[name="variation_id"]').val() || '',
        quantity: $form.find('input[name="quantity"]').val() || '1',
        payload: payload
      });

      $.ajax({
        type: 'POST',
        url: kangooAjaxCart.ajax_url,
        data: $.param(payload),
        dataType: 'json'
      }).done(function (response) {
        console.log('[kangooSingleAdd] response', response);
        const payloadData = response && response.data ? response.data : {};
        const fragments = payloadData.fragments || {};
        const hasFragments = Object.keys(fragments).length > 0;

        if (!response || response.success === false || !hasFragments) {
          showSingleStockMessage(payloadData.message);
          $(document.body).trigger('kangoo_add_to_cart_failed', [$button, payloadData.message]);

          $button
            .removeClass('is-loading is-added')
            .prop('disabled', false)
            .html('LIMITED STOCK');

          window.setTimeout(function () {
            $button.html($button.data('original-html'));
            resyncSingleProductButton($form);
            $form.data('is-adding', false);
            $button.removeData('request-lock');
          }, window.kangooLimitedStockFeedbackDelay || 4200);

          return;
        }

        $.each(fragments, function (selector, html) {
          $(selector).replaceWith(html);
        });

        $(document.body).trigger('added_to_cart', [fragments, payloadData.cart_hash, $button]);
        $(document.body).trigger('wc_fragments_refreshed');

        openCartDrawer(fragments);

        $button
          .removeClass('is-loading')
          .addClass('is-added')
          .prop('disabled', false)
          .html(singleAddedLabel);
        window.kangooSparkButton($button);

        window.setTimeout(function () {
          $button
            .removeClass('is-added')
            .html($button.data('original-html'));

          resyncSingleProductButton($form);
          $form.data('is-adding', false);
          $button.removeData('request-lock');
        }, window.kangooAddFeedbackDelay || 1800);
      }).fail(function (xhr) {
        const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
        const message = response && response.data && response.data.message ? response.data.message : 'LIMITED STOCK';

        showSingleStockMessage(message);
        $(document.body).trigger('kangoo_add_to_cart_failed', [$button, message]);

        $button
          .removeClass('is-loading is-added')
          .prop('disabled', false)
          .html('LIMITED STOCK');

        window.setTimeout(function () {
          $button.html($button.data('original-html'));
          resyncSingleProductButton($form);
          $form.data('is-adding', false);
          $button.removeData('request-lock');
        }, window.kangooLimitedStockFeedbackDelay || 4200);
      }).always(function () {
        $button.removeClass('is-loading');
      });

      return false;
    });
});

jQuery(function ($) {
  function formatCurrency(amount) {
    if (!Number.isFinite(amount)) {
      return '';
    }

    try {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
      }).format(amount);
    } catch (error) {
      return `£${amount.toFixed(2)}`;
    }
  }

	function buildQuickAddButtonHtml(label, total) {
	  const text = total ? `${label} \u00b7 ${total}` : label;
	  return `<span class="button-text">${text}</span>`;
	}

  function getQuickAddVariations($modal) {
    return JSON.parse($modal.find('.quick-add-variations').text() || '[]');
  }

  function getSelectedAttributes($modal) {
    const selected = {};

    $modal.find('.quick-add-pill.is-active').each(function () {
      const $pill = $(this);
      selected[String($pill.data('name'))] = String($pill.data('value'));
    });

    return selected;
  }

  function getQuickAddQuantity($modal) {
    return Math.max(1, parseInt($modal.find('input[name="quantity"]').val(), 10) || 1);
  }

  function getVariationStockLimit(variation) {
    const maxQty = variation && parseInt(variation.max_qty, 10);

    return Number.isFinite(maxQty) && maxQty > 0 ? maxQty : null;
  }

  function getLowStockText(stockLimit) {
    const threshold = window.kangooLowStockPublicThreshold || 3;
    return stockLimit !== null && stockLimit > 0 && stockLimit < threshold ? 'Low stock: only ' + stockLimit + ' left' : '';
  }

  function isQuickAdd99p($modal) {
    return $modal.attr('data-is-99p') === '1';
  }

  function clampQuickAddQuantity($modal, stockLimit) {
    const $visible = $modal.find('.quick-add-qty__input');
    const $hidden = $modal.find('input[name="quantity"]');
    const effectiveStockLimit = isQuickAdd99p($modal) ? 1 : stockLimit;
    let quantity = Math.max(1, parseInt($visible.val() || $hidden.val(), 10) || 1);

    if (effectiveStockLimit !== null) {
      quantity = Math.min(quantity, effectiveStockLimit);
      $visible.attr('max', effectiveStockLimit);
    } else {
      $visible.removeAttr('max');
    }

    $visible.val(quantity);
    $hidden.val(quantity);
    $modal.find('[data-quick-add-minus]').prop('disabled', quantity <= 1);
    $modal.find('[data-quick-add-plus]').prop('disabled', effectiveStockLimit !== null && quantity >= effectiveStockLimit);

    return quantity;
  }

  function getQuickAddPackTiers($modal) {
    const raw = $modal.attr('data-pack-tiers') || '[]';

    try {
      return JSON.parse(raw).map(function (tier) {
        return {
          qty: Math.max(1, parseInt(tier.quantity, 10) || 1),
          unitPrice: parseFloat(tier.unit_price) || 0
        };
      }).filter(function (tier) {
        return tier.qty > 0 && tier.unitPrice > 0;
      }).sort(function (a, b) {
        return a.qty - b.qty;
      });
    } catch (e) {
      return [];
    }
  }

  function getQuickAddPackTierForQty($modal, qty) {
    const tiers = getQuickAddPackTiers($modal);
    let selected = null;

    tiers.forEach(function (tier) {
      if (qty >= tier.qty) {
        selected = tier;
      }
    });

    return selected;
  }

  function matchesAttributes(variationAttributes, selectedAttributes, ignoredKey) {
    return Object.keys(selectedAttributes).every(function (key) {
      if (ignoredKey && key === ignoredKey) {
        return true;
      }

      return String(variationAttributes[key] || '') === String(selectedAttributes[key] || '');
    });
  }

  function isVariationAvailable(variation) {
    const inStock = !!variation.is_in_stock;
    const purchasable = variation.is_purchasable === undefined ? true : !!variation.is_purchasable;
    const active = variation.variation_is_active === undefined ? true : !!variation.variation_is_active;

    return inStock && purchasable && active;
  }

  function findExactVariation($modal) {
    const variations = getQuickAddVariations($modal);
    const selected = getSelectedAttributes($modal);

    return variations.find(function (variation) {
      if (!isVariationAvailable(variation)) {
        return false;
      }

      return Object.keys(variation.attributes || {}).every(function (key) {
        return String(variation.attributes[key] || '') === String(selected[key] || '');
      });
    }) || null;
  }

  function updateQuickAddPillAvailability($modal) {
    const variations = getQuickAddVariations($modal);
    const selected = getSelectedAttributes($modal);

    $modal.find('.quick-add-pill').each(function () {
      const $pill = $(this);
      const pillKey = String($pill.data('name'));
      const pillValue = String($pill.data('value'));

      const isAvailable = variations.some(function (variation) {
        if (!isVariationAvailable(variation)) {
          return false;
        }

        if (String((variation.attributes || {})[pillKey] || '') !== pillValue) {
          return false;
        }

        return matchesAttributes(variation.attributes || {}, selected, pillKey);
      });

      $pill.toggleClass('is-disabled', !isAvailable);

      if (!isAvailable) {
        $pill.removeClass('is-active');
      }
    });
  }

  function syncQuickAddState($modal) {
    const $price = $modal.find('[data-quick-add-price]');
    const $variationId = $modal.find('input[name="variation_id"]');
    const $submit = $modal.find('.quick-add-submit');
    const variations = getQuickAddVariations($modal);
    const initialPriceHtml = $modal.attr('data-initial-price-html') || $price.html();

    updateQuickAddPillAvailability($modal);

    const selected = getSelectedAttributes($modal);
    const attributeCount = $modal.find('.quick-add-pills').length;
    const selectedCount = Object.keys(selected).length;

    if (selectedCount < attributeCount) {
      $variationId.val('');
      $submit
        .prop('disabled', true)
        .addClass('is-disabled')
        .attr('aria-disabled', 'true')
        .html(buildQuickAddButtonHtml('Add to cart', ''));

      $price.html(initialPriceHtml);
      return;
    }

    const variation = findExactVariation($modal);

    if (!variation) {
      $variationId.val('');
      $submit
        .prop('disabled', true)
        .addClass('is-disabled')
        .attr('aria-disabled', 'true')
        .html(buildQuickAddButtonHtml('Add to cart', ''));

      $price.html(initialPriceHtml);
      return;
    }

    const quantity = getQuickAddQuantity($modal);
    const displayPrice = typeof variation.display_price === 'number' ? variation.display_price : null;
    const stockLimit = getVariationStockLimit(variation);
    const clampedQuantity = clampQuickAddQuantity($modal, stockLimit);
    const lowStockText = $modal.attr('data-suppress-stock-note') === '1' ? '' : getLowStockText(stockLimit);
    const $stockNote = $modal.find('[data-quick-add-stock-note]');
    const packTier = getQuickAddPackTierForQty($modal, clampedQuantity);
    const unitPrice = packTier ? packTier.unitPrice : displayPrice;
    const total = Number.isFinite(unitPrice) ? formatCurrency(unitPrice * clampedQuantity) : '';

    if ($stockNote.length) {
      $stockNote.prop('hidden', !lowStockText).toggleClass('is-visible', !!lowStockText).text(lowStockText);
    }

    $variationId.val(variation.variation_id || '');
    $submit
      .prop('disabled', false)
      .removeClass('is-disabled')
      .attr('aria-disabled', 'false')
      .html(buildQuickAddButtonHtml('Add to cart', total));

    if (variation.price_html) {
      $price.html(variation.price_html);
      return;
    }

    const firstPrice = variations.find(function (item) {
      return item.price_html;
    });

    if (firstPrice) {
      $price.html(firstPrice.price_html);
    } else {
      $price.html(initialPriceHtml);
    }
  }

  $(document).on('click', '.quick-add-open', function () {
    const target = $(this).data('quick-add-target');
    const $modal = $('#' + target);

    if (!$modal.length) {
      return;
    }

    const $price = $modal.find('[data-quick-add-price]');

    if (!$modal.attr('data-initial-price-html')) {
      $modal.attr('data-initial-price-html', $price.html());
    }

    $modal.addClass('is-open').attr('aria-hidden', 'false');
    $('body').addClass('no-scroll');

    $modal.find('.quick-add-pill').removeClass('is-active');
    $modal.find('input[name="variation_id"]').val('');
    $modal.find('input[name="quantity"]').val('1');
    $modal.find('.quick-add-qty__input').val('1');
    $modal.find('.quick-add-submit')
      .prop('disabled', true)
      .addClass('is-disabled')
      .attr('aria-disabled', 'true')
      .html(buildQuickAddButtonHtml('Add to cart', ''));

    $price.html($modal.attr('data-initial-price-html'));

    syncQuickAddState($modal);
  });

  $(document).on('click', '[data-quick-add-close]', function () {
    $('.quick-add-modal.is-open').removeClass('is-open').attr('aria-hidden', 'true');
    $('body').removeClass('no-scroll');
  });

  $(document).on('click', '.quick-add-pill', function () {
    const $pill = $(this);

    if ($pill.hasClass('is-disabled')) {
      return;
    }

    const $group = $pill.closest('.quick-add-pills');
    const $modal = $pill.closest('.quick-add-modal');

    $group.find('.quick-add-pill').removeClass('is-active');
    $pill.addClass('is-active');

    syncQuickAddState($modal);
  });

  $(document).on('click', '[data-quick-add-minus], [data-quick-add-plus]', function () {
    const $modal = $(this).closest('.quick-add-modal');
    const $input = $modal.find('.quick-add-qty__input');
    let value = parseInt($input.val(), 10) || 1;
    const variation = findExactVariation($modal);
    const stockLimit = isQuickAdd99p($modal) ? 1 : getVariationStockLimit(variation);

    if ($(this).is('[data-quick-add-plus]')) {
      value += 1;
    } else {
      value = Math.max(1, value - 1);
    }

    if (stockLimit !== null) {
      value = Math.min(value, stockLimit);
    }

    $input.val(value);
    $modal.find('input[name="quantity"]').val(value);
    syncQuickAddState($modal);
  });

  $(document).on('change input', '.quick-add-qty__input', function () {
    const $input = $(this);
    const $modal = $input.closest('.quick-add-modal');
    const variation = findExactVariation($modal);
    const stockLimit = isQuickAdd99p($modal) ? 1 : getVariationStockLimit(variation);
    let value = Math.max(1, parseInt($input.val(), 10) || 1);

    if (stockLimit !== null) {
      value = Math.min(value, stockLimit);
    }

    $input.val(value);
    $modal.find('input[name="quantity"]').val(value);
    syncQuickAddState($modal);
  });

  $(document).on('submit', '.quick-add-form', function (event) {
    event.preventDefault();

    const $form = $(this);
    const $modal = $form.closest('.quick-add-modal');
    const $submit = $form.find('.quick-add-submit');
    const variation = findExactVariation($modal);

    if (!window.kangooAjaxCart || !kangooAjaxCart.ajax_url || !kangooAjaxCart.add_to_cart_nonce) {
      return;
    }

    if (!variation || $submit.prop('disabled')) {
      return;
    }

    const payload = [
      { name: 'action', value: 'kangoo_ajax_add_to_cart' },
      { name: 'nonce', value: kangooAjaxCart.add_to_cart_nonce },
      { name: 'product_id', value: $form.find('input[name="product_id"]').val() },
      { name: 'variation_id', value: variation.variation_id },
      { name: 'quantity', value: $form.find('input[name="quantity"]').val() }
    ];

    Object.keys(variation.attributes || {}).forEach(function (key) {
      payload.push({
        name: key,
        value: variation.attributes[key]
      });
    });

    $submit
      .prop('disabled', true)
      .addClass('is-disabled');

    $.ajax({
      type: 'POST',
      url: kangooAjaxCart.ajax_url,
      data: $.param(payload),
      dataType: 'json'
    }).done(function (response) {
      const payloadData = response && response.data ? response.data : {};
      const fragments = payloadData.fragments || {};

      if (!response || response.success === false) {
        $submit
          .prop('disabled', false)
          .removeClass('is-disabled')
          .html(buildQuickAddButtonHtml(payloadData.message || 'Limited stock', ''));

        window.setTimeout(function () {
          syncQuickAddState($modal);
        }, window.kangooLimitedStockFeedbackDelay || 4200);

        return;
      }

      $.each(fragments, function (selector, html) {
        $(selector).replaceWith(html);
      });

      $(document.body).trigger('added_to_cart', [fragments, payloadData.cart_hash, $submit]);
      $(document.body).trigger('wc_fragments_refreshed');

      $('.quick-add-modal.is-open').removeClass('is-open').attr('aria-hidden', 'true');
      $('body').removeClass('no-scroll');

	  window.kangooOpenCartDrawerIfWasEmpty(fragments);

    }).fail(function (xhr) {
      const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
      const message = response && response.data && response.data.message ? response.data.message : 'Limited stock';

      $submit
        .prop('disabled', false)
        .removeClass('is-disabled')
        .html(buildQuickAddButtonHtml(message, ''));

      window.setTimeout(function () {
        syncQuickAddState($modal);
      }, window.kangooLimitedStockFeedbackDelay || 4200);
    }).always(function () {
      if (!$submit.hasClass('is-disabled') && $submit.text().indexOf('Limited') === -1) {
        window.setTimeout(function () {
          syncQuickAddState($modal);
        }, window.kangooAddFeedbackDelay || 1800);
      }
    });
  });

  $(document).on('keydown', function (event) {
    if (event.key === 'Escape') {
      $('.quick-add-modal.is-open').removeClass('is-open').attr('aria-hidden', 'true');
      $('body').removeClass('no-scroll');
    }
  });
});

jQuery(function ($) {
  $(document).on('click', '.cart-drawer a.checkout', function (event) {
    if (!window.kangooAjaxCart || !kangooAjaxCart.cart_url) {
      return;
    }

    event.preventDefault();
    window.location.href = kangooAjaxCart.cart_url;
  });
});

jQuery(function ($) {
  $(document).on('click', '.added_to_cart.wc-forward', function (event) {
    event.preventDefault();

    const $drawer = $('#cart-drawer');

    if ($drawer.length) {
      $drawer.addClass('is-open');
      $('body').addClass('no-scroll');
    }
  });

	$(document.body).on('added_to_cart', function (event, fragments) {
	  $('.added_to_cart.wc-forward').remove();
	  window.kangooOpenCartDrawerIfWasEmpty(fragments);
	});
});

jQuery(function ($) {
  function formatCardPrice(amount) {
    try {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
      }).format(amount);
    } catch (e) {
      return '£' + amount.toFixed(2);
    }
  }

  function updateCardAddButton($card) {
    const $qtyWrap = $card.find('[data-card-qty]');
    const $input = $card.find('[data-card-qty-input]');
    const $button = $card.find('[data-card-add]');

    if (!$qtyWrap.length || !$input.length || !$button.length) {
      return;
    }

    const stockLimitRaw = parseInt($qtyWrap.attr('data-stock-limit') || $input.attr('max'), 10);
    const rawStockLimit = Number.isFinite(stockLimitRaw) && stockLimitRaw > 0 ? stockLimitRaw : null;
    const is99p = $qtyWrap.attr('data-is-99p') === '1' || $button.attr('data-is-99p') === '1';
    const stockLimit = is99p ? 1 : rawStockLimit;
    const qty = stockLimit === null
      ? Math.max(1, parseInt($input.val(), 10) || 1)
      : Math.min(stockLimit, Math.max(1, parseInt($input.val(), 10) || 1));
    const price = parseFloat($qtyWrap.data('price')) || 0;
    const tiers = parseCardPackTiers($qtyWrap);
    const tier = getCardPackTierForQty(tiers, qty);
    const unitPrice = tier ? tier.unitPrice : price;
    const total = unitPrice * qty;
	  
	const regularPrice = parseFloat($qtyWrap.data('regular-price')) || price;
    const saving = Math.max(0, (regularPrice - unitPrice) * qty);
	const $saving = $card.find('[data-card-saving]');
    const $packLabel = $card.find('[data-card-pack-label]');
    const $packUnit = $card.find('[data-card-pack-unit]');

	if ($saving.length && saving > 0) {
	  $saving.text('You save ' + formatCardPrice(saving));
	}

    if ($packLabel.length) {
      $packLabel.text(qty + '-pack');
    }

    if ($packUnit.length) {
      $packUnit.text(formatCardPrice(unitPrice) + '/unit');
    }

    $input.val(qty);

    if (stockLimit === null) {
      $input.removeAttr('max');
    } else {
      $input.attr('max', stockLimit);
    }

    $card.find('[data-card-minus]').prop('disabled', qty <= 1);
    $card.find('[data-card-plus]').prop('disabled', stockLimit !== null && qty >= stockLimit);
    $button.attr('data-quantity', qty);

    if (!$button.hasClass('is-loading') && !$button.hasClass('is-added')) {
      $button.html('Add to cart \u00b7 ' + formatCardPrice(total));
    }
  }

  window.kangooUpdateProductCardButton = function (card) {
    updateCardAddButton($(card));
  };

  function parseCardPackTiers($qtyWrap) {
    const raw = $qtyWrap.attr('data-pack-tiers') || '[]';

    try {
      return JSON.parse(raw).map(function (tier) {
        return {
          qty: Math.max(1, parseInt(tier.quantity, 10) || 1),
          unitPrice: parseFloat(tier.unit_price) || 0
        };
      }).filter(function (tier) {
        return tier.qty > 0 && tier.unitPrice > 0;
      }).sort(function (a, b) {
        return a.qty - b.qty;
      });
    } catch (e) {
      return [];
    }
  }

  function getCardPackTierForQty(tiers, qty) {
    let selected = null;

    tiers.forEach(function (tier) {
      if (qty >= tier.qty) {
        selected = tier;
      }
    });

    return selected;
  }

  function syncPackModalSelection($modal, $card) {
    if (!$modal.length || !$card.length) {
      return;
    }

    const qty = Math.max(1, parseInt($card.find('[data-card-qty-input]').val(), 10) || 1);
    const $options = $modal.find('[data-card-pack-option]');
    let $match = $();

    $options.each(function () {
      const $option = $(this);
      const optionQty = Math.max(1, parseInt($option.data('pack-qty'), 10) || 1);

      if (optionQty === qty && !$option.prop('disabled') && !$option.hasClass('is-disabled')) {
        $match = $option;
        return false;
      }
    });

    if (!$match.length) {
      $match = $options.filter(':not(:disabled):not(.is-disabled)').first();
    }

    $options.removeClass('is-active').attr('aria-pressed', 'false');

    if ($match.length) {
      $match.addClass('is-active').attr('aria-pressed', 'true');
    }
  }

  function hydratePackModalSummary($modal, $card) {
    if (!$modal.length || !$card.length) {
      return;
    }

    const $title = $modal.find('.pack-add-modal__top h3').first();
    const $imageWrap = $modal.find('.pack-add-modal__image').first();
    const title = $.trim($modal.attr('data-product-title') || $card.find('.product-card__title a').first().text());
    const $cardImage = $card.find('.product-card__media img').first();
    const imageSrc = $modal.attr('data-product-image') || $cardImage.attr('src') || $cardImage.attr('data-src') || '';
    const imageSrcset = $cardImage.attr('srcset') || '';
    const imageSizes = cleanModalImageSizes($cardImage.attr('sizes') || '');

    if ($title.length && title) {
      $title.text(title);
    }

    if ($imageWrap.length && imageSrc) {
      let $img = $imageWrap.find('img').first();

      if (!$img.length) {
        $img = $('<img>', {
          alt: title || ''
        });
        $imageWrap.append($img);
      }

      $img.attr({
        src: imageSrc,
        alt: title || '',
        loading: 'eager',
        decoding: 'async'
      });

      if (imageSrcset) {
        $img.attr('srcset', imageSrcset);
      }

      if (imageSizes) {
        $img.attr('sizes', imageSizes);
      }

    }
  }

  function cleanModalImageSizes(value) {
    return String(value || '').replace(/^auto\s*,\s*/i, '').trim();
  }

  function relocateCartRecommendations() {
    const $section = $('.kangoo-cart-recommendations').first();
    const $main = $('.wc-block-cart__main, .wc-block-components-main.wc-block-cart__main').first();

    if (!$section.length || !$main.length) {
      return;
    }

    const $table = $main.find('table.wc-block-cart-items').last();

    if ($table.length) {
      if (!$section.prev().is($table)) {
        $section.insertAfter($table);
      }
    } else {
      if (!$section.parent().is($main)) {
        $section.appendTo($main);
      }
    }
  }

  $(document).on('click', '[data-card-minus], [data-card-plus]', function () {
    const $card = $(this).closest('.product-card');
    const $input = $card.find('[data-card-qty-input]');

    let qty = Math.max(1, parseInt($input.val(), 10) || 1);

    if ($(this).is('[data-card-plus]')) {
      qty += 1;
    } else {
      qty = Math.max(1, qty - 1);
    }

    const maxAttr = parseInt($input.attr('max'), 10);

    if (Number.isFinite(maxAttr) && maxAttr > 0) {
      qty = Math.min(qty, maxAttr);
    }

    $input.val(qty);
    updateCardAddButton($card);
  });

  $(document).on('input change', '[data-card-qty-input]', function () {
    updateCardAddButton($(this).closest('.product-card'));
  });

  $(document).on('click', '[data-pack-add-open]', function () {
    const target = $(this).data('pack-add-target');
    const $modal = $('#' + target);

    if (!$modal.length) {
      return;
    }

    const $card = $(this).closest('.product-card');

    hydratePackModalSummary($modal, $card);
    syncPackModalSelection($modal, $card);

    $modal.addClass('is-open').attr('aria-hidden', 'false');
    $('body').addClass('no-scroll');
  });

  $(document).on('click', '[data-pack-add-close]', function () {
    $('.pack-add-modal.is-open').removeClass('is-open').attr('aria-hidden', 'true');
    $('body').removeClass('no-scroll');
  });

  $(document).on('click', '[data-card-pack-option]', function () {
    const $option = $(this);
    const $modal = $option.closest('.pack-add-modal');
    const cardId = $modal.data('product-card-id');
    const $card = $('[data-product-card-id="' + cardId + '"]').first();
    const $input = $card.find('[data-card-qty-input]');
    const qty = Math.max(1, parseInt($option.data('pack-qty'), 10) || 1);

    if (!$card.length || !$input.length || $option.prop('disabled') || $option.hasClass('is-disabled')) {
      return;
    }

    $modal.find('[data-card-pack-option]').removeClass('is-active').attr('aria-pressed', 'false');
    $option.addClass('is-active').attr('aria-pressed', 'true');

    $input.val(qty).trigger('change');
  });

  $(document).on('click', '[data-pack-add-submit]', function () {
    const $modal = $(this).closest('.pack-add-modal');
    const cardId = $modal.data('product-card-id');
    const $card = $('[data-product-card-id="' + cardId + '"]').first();

    if ($card.length) {
      $card.find('[data-card-add]').trigger('click');
    }

    $modal.removeClass('is-open').attr('aria-hidden', 'true');
    $('body').removeClass('no-scroll');
  });

  $(document).on('keydown', function (event) {
    if (event.key === 'Escape') {
      $('.pack-add-modal.is-open').removeClass('is-open').attr('aria-hidden', 'true');
      $('body').removeClass('no-scroll');
    }
  });

  $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
	  if ($button && $button.length && $button.is('[data-card-add]')) {
		const $card = $button.closest('.product-card');

		window.setTimeout(function () {
		  updateCardAddButton($card);
		}, 1300);
	  }
	});

  $('.product-card').each(function () {
    updateCardAddButton($(this));
  });

  relocateCartRecommendations();
  $(window).on('load', relocateCartRecommendations);
  $(document.body).on('updated_wc_div wc_fragments_refreshed added_to_cart removed_from_cart', function () {
    window.setTimeout(relocateCartRecommendations, 80);
  });
});

jQuery(function ($) {
  function formatSingleSaving(amount) {
    try {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
      }).format(amount);
    } catch (e) {
      return '£' + amount.toFixed(2);
    }
  }

  function updateSingleProductSaving() {
    const $saving = $('[data-single-saving]');
    const $qty = $('.product-cart form.cart input.qty').first();

    if (!$saving.length || !$qty.length) {
      return;
    }

    const savingPerItem = parseFloat($saving.data('saving-per-item')) || 0;
    const qty = Math.max(1, parseInt($qty.val(), 10) || 1);

    if (savingPerItem > 0) {
      $saving.text('You save ' + formatSingleSaving(savingPerItem * qty));
    }
  }

  $(document).on('input change', '.product-cart form.cart input.qty', updateSingleProductSaving);

  $(document).on('click', '.product-cart .qty-btn', function () {
    setTimeout(updateSingleProductSaving, 50);
  });

  updateSingleProductSaving();
});

jQuery(function ($) {
  function formatPackCurrency(amount) {
    try {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
      }).format(amount);
    } catch (e) {
      return '\u00a3' + amount.toFixed(2);
    }
  }

  function getPackOptions($form) {
    return $form.find('[data-pack-pricing] .pack-pricing__option').map(function () {
      const $option = $(this);

      return {
        $el: $option,
        qty: Math.max(1, parseInt($option.data('pack-qty'), 10) || 1),
        packPrice: parseFloat($option.data('pack-price')) || 0,
        unitPrice: parseFloat($option.data('unit-price')) || 0,
        disabled: $option.prop('disabled') || $option.hasClass('is-disabled')
      };
    }).get().filter(function (tier) {
      return tier.qty > 0 && tier.packPrice > 0 && tier.unitPrice > 0;
    }).sort(function (a, b) {
      return a.qty - b.qty;
    });
  }

  function getPackTierForQty($form, qty) {
    const tiers = getPackOptions($form);
    let selected = null;

    tiers.forEach(function (tier) {
      if (!tier.disabled && qty >= tier.qty) {
        selected = tier;
      }
    });

    return selected;
  }

  function getSingleStockLimit($form) {
    const $qty = $form.find('input.qty').first();
    const maxAttr = parseInt($qty.attr('max'), 10);

    return Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : null;
  }

  function syncSingleQuantityVisibility($form) {
    const stockLimit = getSingleStockLimit($form);
    const shouldHideQuantity = stockLimit !== null && stockLimit <= 1;
    const $cart = $form.closest('.product-cart');
    const $qty = $form.find('input.qty').first();

    $cart.toggleClass('product-cart--single-quantity', shouldHideQuantity);

    if (shouldHideQuantity && $qty.length && parseInt($qty.val(), 10) !== 1) {
      $qty.val(1);
    }
  }

  function syncSingleLowStockNote($form) {
    const stockLimit = getSingleStockLimit($form);
    const $note = $('[data-product-stock-note]').first();

    if (!$note.length) {
      return;
    }

    if ($note.attr('data-suppress-stock-note') === '1') {
      $note.prop('hidden', true).removeClass('product-stock-note--low').text('');
      return;
    }

    const threshold = window.kangooLowStockPublicThreshold || 3;

    if (stockLimit !== null && stockLimit > 0 && stockLimit < threshold) {
      $note
        .prop('hidden', false)
        .addClass('product-stock-note--low')
        .text('Low stock: only ' + stockLimit + ' left');
      return;
    }

    $note.prop('hidden', true).removeClass('product-stock-note--low').text('');
  }

  function syncPackOptionAvailability($form) {
    const stockLimit = getSingleStockLimit($form);

    $form.find('[data-pack-pricing] .pack-pricing__option').each(function () {
      const $option = $(this);
      const packQty = Math.max(1, parseInt($option.data('pack-qty'), 10) || 1);
      const isUnavailable = stockLimit !== null && packQty > stockLimit;

      $option
        .prop('disabled', isUnavailable)
        .prop('hidden', false)
        .removeAttr('hidden')
        .toggleClass('is-disabled', isUnavailable);

      const $badge = $option.find('.pack-pricing__badge');

      if ($badge.length && !$badge.attr('data-original-badge')) {
        $badge.attr('data-original-badge', $badge.text());
      }

      if (isUnavailable) {
        return;
      }

      if ($badge.length) {
        const originalBadge = $badge.attr('data-original-badge') || '';

        $badge.removeClass('pack-pricing__badge--muted');

        if (originalBadge) {
          $badge.text(originalBadge);
        } else {
          $badge.remove();
        }
      }
    });
  }

  function syncPackPricing($form) {
    const $qty = $form.find('input.qty').first();

    if (!$qty.length) {
      return;
    }

    const $selector = $form.find('[data-pack-pricing]');
    const stockLimit = getSingleStockLimit($form);
    const quantity = stockLimit === null
      ? Math.max(1, parseInt($qty.val(), 10) || 1)
      : Math.min(stockLimit, Math.max(1, parseInt($qty.val(), 10) || 1));

    if (parseInt($qty.val(), 10) !== quantity) {
      $qty.val(quantity);
    }

    syncSingleQuantityVisibility($form);
    syncSingleLowStockNote($form);

    if (!$selector.length) {
      return;
    }

    syncPackOptionAvailability($form);

    const tier = getPackTierForQty($form, quantity);

    if (!tier) {
      return;
    }

    $selector.find('.pack-pricing__option').removeClass('is-active').attr('aria-pressed', 'false');
    tier.$el.addClass('is-active').attr('aria-pressed', 'true');

    const total = tier.unitPrice * quantity;
    const $price = $form.closest('.product-page').find('#product-price').first();

    if ($price.length) {
      const regularUnitPrice = parseFloat($price.data('product-regular-price')) || 0;
      const regularTotal = regularUnitPrice * quantity;
      const totalPriceHtml = regularTotal > total
        ? '<del aria-hidden="true">' + formatPackCurrency(regularTotal) + '</del> <ins><span class="pack-pricing-price">' + formatPackCurrency(total) + '</span></ins>'
        : '<span class="pack-pricing-price">' + formatPackCurrency(total) + '</span>';

      $price.html(
        totalPriceHtml +
        '<span class="pack-pricing-price__unit">' + formatPackCurrency(tier.unitPrice) + '/unit</span>'
      );
    }

    const $button = $form.find('.single_add_to_cart_button').first();

    if ($button.length && !$button.hasClass('is-loading') && !$button.hasClass('is-added') && !$button.is(':disabled')) {
      const html = '<span class="button-text">ADD TO CART \u00b7 ' + formatPackCurrency(total) + '</span>';
      $button.data('idle-html', html).html(html);
    }

    const $saving = $('[data-single-saving]');
    const basePrice = parseFloat($price.data('product-price')) || 0;

    if ($saving.length && basePrice > tier.unitPrice) {
      $saving.text('You save ' + formatPackCurrency((basePrice - tier.unitPrice) * quantity));
    }
  }

  $(document).on('click', '.pack-pricing__option', function () {
    const $option = $(this);
    const $form = $option.closest('form.cart');
    const $qty = $form.find('input.qty').first();
    const packQty = Math.max(1, parseInt($option.data('pack-qty'), 10) || 1);

    if (!$form.length || !$qty.length || $option.prop('disabled') || $option.hasClass('is-disabled')) {
      return;
    }

    $qty.val(packQty).trigger('change');
    syncPackPricing($form);
  });

  $(document).on('input change', 'form.cart input.qty', function () {
    syncPackPricing($(this).closest('form.cart'));
  });

  $(document).on('found_variation reset_data hide_variation woocommerce_variation_has_changed', '.variations_form', function () {
    const $form = $(this);

    window.setTimeout(function () {
      syncPackPricing($form);
    }, 0);
  });

  $(document.body).on('kangoo_sync_pack_pricing', function (event, form) {
    const $form = form && form.jquery ? form : $(form);

    if ($form.length) {
      syncPackPricing($form);
    }
  });

  $('.product-page .product-cart form.cart').each(function () {
    const $form = $(this);
    const $default = $form.find('[data-pack-pricing] .pack-pricing__option.is-active').first();

    if ($default.length) {
      const $qty = $form.find('input.qty').first();
      const packQty = Math.max(1, parseInt($default.data('pack-qty'), 10) || 1);

      if ($qty.length) {
        $qty.val(packQty);
      }
    }

    syncPackPricing($form);
  });
});

jQuery(function ($) {
  function formatStickyCurrency(amount) {
    if (!Number.isFinite(amount)) {
      return '';
    }

    try {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
      }).format(amount);
    } catch (e) {
      return '\u00a3' + amount.toFixed(2);
    }
  }

  function parseStickyPrice(input) {
    const $html = $('<div>').html(input || '');
    const saleText = $html.find('ins').last().text();
    const text = saleText || $html.text();
    const match = text.match(/-?\d[\d,.]*/);

    if (!match) {
      return null;
    }

    let value = match[0];

    if (value.indexOf(',') > -1 && value.indexOf('.') > -1) {
      value = value.replace(/,/g, '');
    } else {
      value = value.replace(/,/g, '.');
    }

    const parsed = parseFloat(value);

    return Number.isFinite(parsed) ? parsed : null;
  }

  function getStickyPackTiers($form) {
    return $form.find('[data-pack-pricing] .pack-pricing__option').map(function () {
      const $option = $(this);

      return {
        $el: $option,
        qty: Math.max(1, parseInt($option.data('pack-qty'), 10) || 1),
        packPrice: parseFloat($option.data('pack-price')) || 0,
        unitPrice: parseFloat($option.data('unit-price')) || 0,
        disabled: $option.prop('disabled') || $option.hasClass('is-disabled')
      };
    }).get().filter(function (tier) {
      return tier.qty > 0 && tier.unitPrice > 0;
    }).sort(function (a, b) {
      return a.qty - b.qty;
    });
  }

  function getStickyTierForQty($form, quantity) {
    const tiers = getStickyPackTiers($form);
    let selected = null;

    tiers.forEach(function (tier) {
      if (quantity >= tier.qty) {
        selected = tier;
      }
    });

    return selected;
  }

  function stickyPackLabel(quantity, hasPackPricing) {
    if (hasPackPricing) {
      return quantity + '-pack';
    }

    return quantity === 1 ? '1-pack' : quantity + '-pack';
  }

  function getStickyUnitPrice($form, quantity) {
    const tier = getStickyTierForQty($form, quantity);

    if (tier) {
      return tier.unitPrice;
    }

    const $price = $('#product-price').first();
    const basePrice = parseFloat($price.data('product-price')) || 0;
    const visiblePrice = parseStickyPrice($price.html());

    if (Number.isFinite(visiblePrice) && visiblePrice > 0) {
      return visiblePrice;
    }

    return basePrice;
  }

  function getStickyBaseUnitPrice() {
    const tiers = getStickyPackTiers($('.product-page .product-cart form.cart').first()).filter(function (tier) {
      return !tier.disabled;
    });
    const singleTier = tiers.find(function (tier) {
      return tier.qty === 1 && tier.unitPrice > 0;
    });

    if (singleTier) {
      return singleTier.unitPrice;
    }

    const $price = $('#product-price').first();
    const basePrice = parseFloat($price.data('product-price')) || 0;

    return basePrice > 0 ? basePrice : 0;
  }

  function syncStickyPackSelect($sticky, $form, quantity) {
    const $select = $sticky.find('[data-sticky-pack-select]');

    if (!$select.length) {
      return;
    }

    const tiers = getStickyPackTiers($form).filter(function (tier) {
      return !tier.disabled;
    });
    const values = tiers.length ? tiers : [{
      qty: 1,
      unitPrice: getStickyUnitPrice($form, 1),
      packPrice: getStickyUnitPrice($form, 1)
    }];
    const signature = values.map(function (tier) {
      return tier.qty + ':' + tier.unitPrice;
    }).join('|');

    $sticky.toggleClass('sticky-add--single-pack', values.length <= 1);

    if ($select.attr('data-options-signature') !== signature) {
      $select.empty();

      values.forEach(function (tier) {
        const label = tier.qty === 1 ? '1-pack' : tier.qty + '-pack';
        $select.append($('<option>', {
          value: String(tier.qty),
          text: label
        }));
      });

      $select.attr('data-options-signature', signature);
    }

    $select.val(String(quantity));

    if ($select.val() !== String(quantity)) {
      $select.val(String(values[0].qty));
    }
  }

  function setStickyButtonText($button, text) {
    const $text = $button.find('[data-sticky-button-text]');

    if ($text.length) {
      $text.text(text);
      return;
    }

    $button.text(text);
  }

  function syncStickyAddBar() {
    const $sticky = $('[data-sticky-add]');
    const $form = $('.product-page .product-cart form.cart').first();

    if (!$sticky.length || !$form.length) {
      return;
    }

    const $qty = $form.find('input.qty').first();
    const quantity = Math.max(1, parseInt($qty.val(), 10) || 1);
    const hasPackPricing = $form.find('[data-pack-pricing]').length > 0;
    const unitPrice = getStickyUnitPrice($form, quantity);
    const total = unitPrice * quantity;
    const totalLabel = formatStickyCurrency(total);
    const metaLabel = stickyPackLabel(quantity, hasPackPricing);
    const baseUnitPrice = hasPackPricing ? getStickyBaseUnitPrice() : 0;
    const saving = baseUnitPrice > unitPrice ? (baseUnitPrice - unitPrice) * quantity : 0;
    const $mainButton = $form.find('.single_add_to_cart_button').first();
    const $stickyButton = $sticky.find('[data-sticky-add-button]');

    if ($stickyButton.hasClass('is-loading') || $stickyButton.hasClass('is-added')) {
      return;
    }

    syncStickyPackSelect($sticky, $form, quantity);

    $sticky.find('[data-sticky-price]').text(totalLabel);
    $sticky.find('[data-sticky-unit-price]')
      .empty()
      .append($('<strong>').text(formatStickyCurrency(unitPrice)))
      .append($('<span>').text('per pack'));
    $sticky.find('[data-sticky-meta]').text(metaLabel);
    $sticky.find('[data-sticky-saving]')
      .toggle(saving > 0)
      .text(saving > 0 ? 'Save ' + formatStickyCurrency(saving) : '');

    const isTemporary = $mainButton.hasClass('is-loading') || $mainButton.hasClass('is-added');
    const isStockDisabled = !isTemporary && (
      $mainButton.hasClass('disabled') ||
      $mainButton.hasClass('is-disabled') ||
      ($mainButton.is(':disabled') && !$mainButton.data('request-lock'))
    );

    if (!$stickyButton.hasClass('is-loading') && !$stickyButton.hasClass('is-added')) {
      setStickyButtonText($stickyButton, 'Add to cart');
    }

    $stickyButton
      .prop('disabled', isStockDisabled)
      .toggleClass('is-disabled', isStockDisabled);

    if (isStockDisabled) {
      setStickyButtonText($stickyButton, 'Sold Out');
    }
  }

  $(document).on('click', '[data-sticky-summary]', function () {
    const target = document.querySelector('[data-pack-pricing]') || document.querySelector('.product-cart .quantity');

    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });
    }
  });

  $(document).on('input change', '.product-cart form.cart input.qty', syncStickyAddBar);
  $(document).on('click', '.pack-pricing__option, .product-cart .qty-btn', function () {
    window.setTimeout(syncStickyAddBar, 0);
  });

  $(document).on('change', '[data-sticky-pack-select]', function () {
    const quantity = Math.max(1, parseInt($(this).val(), 10) || 1);
    const $form = $('.product-page .product-cart form.cart').first();
    const $qty = $form.find('input.qty').first();

    if (!$form.length || !$qty.length) {
      return;
    }

    const $option = $form.find('[data-pack-pricing] .pack-pricing__option').filter(function () {
      return Math.max(1, parseInt($(this).data('pack-qty'), 10) || 1) === quantity;
    }).first();

    $qty.val(quantity).trigger('change');

    if ($option.length && !$option.prop('disabled') && !$option.hasClass('is-disabled')) {
      $option.trigger('click');
      return;
    }

    syncStickyAddBar();
  });

  $(document).on('found_variation reset_data hide_variation woocommerce_variation_has_changed', '.variations_form', function () {
    window.setTimeout(syncStickyAddBar, 0);
  });

  $(document.body).on('added_to_cart wc_fragments_refreshed', syncStickyAddBar);

  $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
    const $stickyButton = $('[data-sticky-add-button]');

    if (!$stickyButton.length || !$button || !$button.hasClass('single_add_to_cart_button')) {
      return;
    }

    $stickyButton
      .removeClass('is-loading')
      .addClass('is-added')
      .prop('disabled', false)
      .find('[data-sticky-button-text]').text('Added \u2713');

    if (!$stickyButton.find('[data-sticky-button-text]').length) {
      $stickyButton.text('Added \u2713');
    }

    window.kangooSparkButton($stickyButton);

    window.setTimeout(function () {
      $stickyButton.removeClass('is-added');
      syncStickyAddBar();
    }, window.kangooAddFeedbackDelay || 1800);
  });

  $(document.body).on('kangoo_add_to_cart_failed', function (event, $button, message) {
    const $stickyButton = $('[data-sticky-add-button]');

    if (!$stickyButton.length || !$button || !$button.hasClass('single_add_to_cart_button')) {
      return;
    }

    const $sticky = $('[data-sticky-add]');
    const $message = $sticky.find('[data-sticky-message]');

    $stickyButton
      .removeClass('is-loading is-added')
      .addClass('is-disabled')
      .prop('disabled', true);

    setStickyButtonText($stickyButton, 'Limited stock');

    if ($message.length) {
      $sticky.addClass('has-message');
      $message
        .prop('hidden', false)
        .text(message || 'Limited availability for this product.');
    }

    window.setTimeout(function () {
      $stickyButton.removeClass('is-disabled').prop('disabled', false);
      if ($message.length) {
        $sticky.removeClass('has-message');
        $message.prop('hidden', true).text('');
      }
      syncStickyAddBar();
    }, window.kangooLimitedStockFeedbackDelay || 4200);
  });

  $(window).on('load', syncStickyAddBar);

  syncStickyAddBar();
});
