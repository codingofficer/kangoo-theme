(function () {
  'use strict';

  const config = window.kangooAgeVerification || {};
  if (!config.enabled) return;

  document.documentElement.classList.add('kangoo-av-enforced');
  document.documentElement.classList.toggle('kangoo-av-verified', Boolean(config.verified));

  function returnToVerification() {
    if (!config.verified || window.sessionStorage.getItem('kangooAvJustVerified') !== '1') return;
    window.sessionStorage.removeItem('kangooAvJustVerified');
    window.requestAnimationFrame(function () {
      const card = document.querySelector('[data-kangoo-av]');
      if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

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
      if (!response.ok) throw new Error(payload.message || 'Verification could not be completed.');
      return payload;
    });
  }

  function getEmail() {
    const field = document.querySelector('#email, #billing_email, input[name="billing_email"], input[type="email"]');
    return field ? field.value.trim() : '';
  }

  function setMessage(card, message, error) {
    const element = card.querySelector('[data-kangoo-av-message]');
    if (!element) return;
    element.textContent = message || '';
    element.classList.toggle('is-error', Boolean(error));
  }

  function lockPayment() {
    if (config.verified) return;
    document.querySelectorAll('.wc-block-components-checkout-place-order-button, #place_order').forEach(function (button) {
      button.disabled = true;
      button.setAttribute('aria-disabled', 'true');
      button.setAttribute('title', 'Complete photo-ID age verification first.');
    });
  }

  function verify(button) {
    const card = button.closest('[data-kangoo-av]');
    if (!card || button.disabled) return;

    button.disabled = true;
    button.classList.add('is-loading');
    button.textContent = 'Opening secure verification...';
    setMessage(card, '', false);

    api('/session', { email: getEmail() }).then(function (session) {
      if (session.verified) {
        window.sessionStorage.setItem('kangooAvJustVerified', '1');
        window.location.reload();
        return null;
      }
      if (!window.Stripe || !config.publishableKey || !session.clientSecret) {
        throw new Error('Stripe Identity is not fully configured.');
      }
      return window.Stripe(config.publishableKey).verifyIdentity(session.clientSecret);
    }).then(function (result) {
      if (!result) return null;
      if (result.error) throw new Error(result.error.message || 'Verification was not completed.');
      setMessage(card, 'Checking your verification result...', false);
      return api('/check', {});
    }).then(function (status) {
      if (!status) return;
      if (status.verified) {
        setMessage(card, 'Age verified. Loading secure payment options...', false);
        window.sessionStorage.setItem('kangooAvJustVerified', '1');
        window.location.reload();
        return;
      }
      throw new Error(status.message || 'Verification needs another attempt.');
    }).catch(function (error) {
      button.disabled = false;
      button.classList.remove('is-loading');
      button.textContent = 'Try photo-ID verification again';
      setMessage(card, error.message, true);
    });
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-kangoo-av-start]');
    if (button) verify(button);
  });

  const observer = new MutationObserver(lockPayment);
  observer.observe(document.documentElement, { childList: true, subtree: true });
  lockPayment();
  returnToVerification();
}());
