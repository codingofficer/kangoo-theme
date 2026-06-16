(function () {
  'use strict';

  const config = window.kangooCustomCheckout || {};
  const root = document.querySelector('[data-kangoo-custom-checkout]');
  const cartRoot = document.querySelector('.woocommerce-cart');

  function api(path, body) {
    return fetch(config.restUrl + path, {
      method: body ? 'POST' : 'GET',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce
      },
      body: body ? JSON.stringify(body) : undefined
    }).then(async function (response) {
      const payload = await response.json().catch(function () { return {}; });
      if (!response.ok) {
        throw new Error(payload.message || 'Checkout could not be updated.');
      }
      return payload;
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

  function setButtonLoading(button, isLoading) {
    if (!button) {
      return;
    }

    button.disabled = Boolean(isLoading);
    button.classList.toggle('is-loading', Boolean(isLoading));
  }

  function setMessage(key, message, isError) {
    const element = root ? root.querySelector('[data-kangoo-message="' + key + '"]') : null;
    if (!element) {
      return;
    }

    element.textContent = message || '';
    element.classList.toggle('is-error', Boolean(isError));
  }

  function currentUrlWithStep(step) {
    const url = new URL(window.location.href);
    url.searchParams.set('kangoo_checkout', '1');
    url.searchParams.set('step', step);
    return url.toString();
  }

  function setStep(step, updateHistory) {
    if (!root) {
      return;
    }

    const steps = ['delivery', 'verify', 'payment'];
    const activeStep = steps.includes(step) ? step : 'delivery';
    root.dataset.step = activeStep;
    window.sessionStorage.setItem('kangooCustomCheckoutStep', activeStep);

    root.querySelectorAll('[data-kangoo-panel]').forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-kangoo-panel') !== activeStep;
    });

    root.querySelectorAll('[data-kangoo-step-target]').forEach(function (control) {
      const target = control.getAttribute('data-kangoo-step-target');
      const activeIndex = steps.indexOf(activeStep);
      const targetIndex = steps.indexOf(target);
      control.classList.toggle('is-active', target === activeStep);
      control.classList.toggle('is-complete', targetIndex > -1 && targetIndex < activeIndex);
    });

    if (updateHistory) {
      window.history.replaceState({}, '', currentUrlWithStep(activeStep));
    }

    const card = root.querySelector('.kangoo-custom-checkout__card');
    if (card) {
      card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function deliveryConfig(label) {
    const text = String(label || '').toLowerCase();
    if (/48|free/.test(text)) {
      return {
        title: 'Royal Mail Tracked 48',
        estimate: 'Delivered in 2 working days aim',
        promo: 'FREE over £14.95'
      };
    }
    if (/24/.test(text)) {
      return {
        title: 'Royal Mail Tracked 24',
        estimate: 'Delivered in 1 working day aim',
        promo: ''
      };
    }
    if (/1\s*pm|1pm|guaranteed/.test(text)) {
      return {
        title: 'Next Day by 1pm',
        estimate: 'Order before 2pm Mon-Fri',
        promo: ''
      };
    }
    return {
      title: 'Next Day Delivery',
      estimate: 'Order before 2pm Mon-Fri',
      promo: ''
    };
  }

  function renderDeliveryOptions(state) {
    const holder = root.querySelector('[data-kangoo-delivery-options]');
    if (!holder) {
      return;
    }

    const rates = state && Array.isArray(state.shippingRates) ? state.shippingRates : [];
    holder.innerHTML = rates.map(function (rate) {
      const detail = deliveryConfig(rate.label);
      return [
        '<label class="kangoo-custom-checkout__delivery-card', rate.selected ? ' is-selected' : '', '">',
        '<input type="radio" name="shipping_rate" value="', escapeHtml(rate.id), '"', rate.selected ? ' checked' : '', ' required>',
        '<span class="kangoo-custom-checkout__delivery-radio" aria-hidden="true"></span>',
        '<span class="kangoo-custom-checkout__delivery-logo" aria-hidden="true"><img src="', escapeHtml((window.kangooRewards && window.kangooRewards.theme_url ? window.kangooRewards.theme_url : '/wp-content/themes/kangoo-theme') + '/assets/images/icons8-royal-mail.svg'), '" alt=""></span>',
        '<span class="kangoo-custom-checkout__delivery-copy">',
        '<strong>', escapeHtml(detail.title), '</strong>',
        '<small>', escapeHtml(detail.estimate), '</small>',
        detail.promo ? '<em>' + escapeHtml(detail.promo) + '</em>' : '',
        '</span>',
        '<span class="kangoo-custom-checkout__delivery-price">', escapeHtml(rate.price), '</span>',
        '</label>'
      ].join('');
    }).join('');
  }

  function renderSummary(state) {
    const holder = root.querySelector('[data-kangoo-order-summary]');
    if (!holder || !state) {
      return;
    }

    const items = Array.isArray(state.items) ? state.items : [];
    holder.innerHTML = [
      '<h2>Order summary</h2>',
      '<div class="kangoo-custom-checkout__summary-items">',
      items.map(function (item) {
        return [
          '<article>',
          item.image ? '<img src="' + escapeHtml(item.image) + '" alt="">' : '<span></span>',
          '<div><strong>', escapeHtml(item.name), '</strong><small>Qty: ', escapeHtml(item.quantity), '</small></div>',
          '<b>', escapeHtml(item.lineTotal), '</b>',
          '</article>'
        ].join('');
      }).join(''),
      '</div>',
      '<dl>',
      '<dt>Subtotal</dt><dd>', escapeHtml(state.totals && state.totals.subtotal), '</dd>',
      '<dt>Shipping</dt><dd>', escapeHtml(state.totals && state.totals.shipping), '</dd>',
      '<dt>Total</dt><dd>', escapeHtml(state.totals && state.totals.total), ' GBP</dd>',
      '</dl>'
    ].join('');
  }

  function hydrateForms(state) {
    const deliveryForm = root.querySelector('[data-kangoo-delivery-form]');
    if (deliveryForm && state && state.customer) {
      const shipping = state.customer.shipping || {};
      const fullName = [shipping.first_name, shipping.last_name].filter(Boolean).join(' ');
      const values = {
        email: state.customer.email || '',
        full_name: fullName,
        address_1: shipping.address_1 || '',
        address_2: shipping.address_2 || '',
        city: shipping.city || '',
        postcode: shipping.postcode || '',
        country: shipping.country || 'GB'
      };
      Object.keys(values).forEach(function (name) {
        const field = deliveryForm.elements[name];
        if (field && !field.value) {
          field.value = values[name];
        }
      });
    }

    const dobForm = root.querySelector('[data-kangoo-dob-form]');
    if (dobForm && state && state.dob && state.dob.value && !dobForm.elements.dob.value) {
      const parts = String(state.dob.value).match(/^(\d{4})-(\d{2})-(\d{2})$/);
      dobForm.elements.dob.value = parts ? [parts[3], parts[2], parts[1]].join(' / ') : '';
    }
  }

  function refreshState() {
    if (!root) {
      return Promise.resolve(null);
    }

    root.classList.add('is-loading');
    return api('/state').then(function (state) {
      renderDeliveryOptions(state);
      renderSummary(state);
      hydrateForms(state);
      return state;
    }).catch(function (error) {
      root.classList.add('has-error');
      setMessage('delivery', error.message, true);
      return null;
    }).finally(function () {
      root.classList.remove('is-loading');
    });
  }

  function formPayload(form) {
    const payload = {};
    const data = new FormData(form);
    data.forEach(function (value, key) {
      payload[key] = value;
    });
    if (form.elements.billing_same) {
      payload.billing_same = form.elements.billing_same.checked;
    }
    return payload;
  }

  function initCheckout() {
    if (!root || !config.active) {
      return;
    }

    const urlStep = new URL(window.location.href).searchParams.get('step');
    const storedStep = window.sessionStorage.getItem('kangooCustomCheckoutStep');
    setStep(urlStep || storedStep || root.dataset.step || 'delivery', false);

    root.addEventListener('click', function (event) {
      const target = event.target.closest('[data-kangoo-step-target]');
      if (!target || !root.contains(target)) {
        return;
      }

      event.preventDefault();
      const step = target.getAttribute('data-kangoo-step-target');
      if (step === 'payment') {
        setMessage('verify', 'Complete age verification before payment.', true);
        setStep('verify', true);
        return;
      }
      setStep(step, true);
    });

    const deliveryForm = root.querySelector('[data-kangoo-delivery-form]');
    if (deliveryForm) {
      deliveryForm.addEventListener('change', function (event) {
        if (event.target && event.target.name === 'billing_same') {
          const billing = root.querySelector('[data-kangoo-billing-fields]');
          if (billing) {
            billing.hidden = event.target.checked;
          }
        }
        if (event.target && event.target.name === 'shipping_rate') {
          root.querySelectorAll('.kangoo-custom-checkout__delivery-card').forEach(function (card) {
            card.classList.toggle('is-selected', Boolean(card.querySelector('input:checked')));
          });
        }
      });

      deliveryForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const button = deliveryForm.querySelector('button[type="submit"]');
        setMessage('delivery', '', false);
        setButtonLoading(button, true);
        api('/delivery', formPayload(deliveryForm)).then(function (state) {
          renderDeliveryOptions(state);
          renderSummary(state);
          setStep('verify', true);
        }).catch(function (error) {
          setMessage('delivery', error.message, true);
        }).finally(function () {
          setButtonLoading(button, false);
        });
      });
    }

    const dobForm = root.querySelector('[data-kangoo-dob-form]');
    if (dobForm) {
      dobForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const button = dobForm.querySelector('button[type="submit"]');
        setMessage('verify', '', false);
        setButtonLoading(button, true);
        api('/dob', { dob: dobForm.elements.dob.value }).then(function (state) {
          renderSummary(state);
          setStep('payment', true);
        }).catch(function (error) {
          setMessage('verify', error.message, true);
        }).finally(function () {
          setButtonLoading(button, false);
        });
      });
    }

    const noteToggle = root.querySelector('[data-kangoo-note-toggle]');
    const noteField = root.querySelector('[data-kangoo-order-note]');
    if (noteToggle && noteField) {
      noteToggle.addEventListener('change', function () {
        noteField.hidden = !noteToggle.checked;
      });
      noteField.addEventListener('blur', function () {
        api('/note', { note: noteField.value }).catch(function () {});
      });
    }

    const verifyButton = root.querySelector('[data-kangoo-start-verification]');
    if (verifyButton) {
      verifyButton.addEventListener('click', function () {
        const nativeButton = document.querySelector('[data-kangoo-av-start]');
        if (nativeButton) {
          nativeButton.click();
          return;
        }
        setMessage('verify', 'Photo-ID enforcement is currently off. Continue to payment after entering your date of birth.', false);
      });
    }

    refreshState();
  }

  function initCartStepPolish() {
    if (!cartRoot || (!config.enabled && !config.preview)) {
      return;
    }

    if (!cartRoot.querySelector('[data-kangoo-cart-stepper]')) {
      const stepper = document.createElement('div');
      stepper.className = 'kangoo-cart-stepper';
      stepper.setAttribute('data-kangoo-cart-stepper', '');
      stepper.innerHTML = [
        '<span class="is-active"><b>1</b><small>Cart</small></span>',
        '<span><b>2</b><small>Delivery details</small></span>',
        '<span><b>3</b><small>Verify your age</small></span>',
        '<span><b>4</b><small>Payment</small></span>'
      ].join('');
      const target = cartRoot.querySelector('.woocommerce, .wc-block-cart') || cartRoot;
      target.insertAdjacentElement('beforebegin', stepper);
    }

    cartRoot.querySelectorAll('.wc-proceed-to-checkout a.checkout-button, .wc-block-cart__submit-button, .wp-block-woocommerce-proceed-to-checkout-block a').forEach(function (link) {
      if (link.tagName.toLowerCase() !== 'a') {
        return;
      }
      link.href = config.checkoutUrl + (config.checkoutUrl.indexOf('?') === -1 ? '?' : '&') + 'kangoo_checkout=1&step=delivery';
      link.textContent = 'Continue to delivery details';
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initCheckout();
    initCartStepPolish();
  });
}());
