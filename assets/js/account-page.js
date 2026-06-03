/* ========================================================================
FILE: assets/js/account-page.js
========================================================================= */

document.addEventListener('DOMContentLoaded', function () {
  const accountRoot = document.querySelector('.woocommerce-account .woocommerce');

  if (!accountRoot) {
    return;
  }

  const nav = accountRoot.querySelector('.woocommerce-MyAccount-navigation');
  const content = accountRoot.querySelector('.woocommerce-MyAccount-content');

  if (!nav || !content) {
    return;
  }

  const currentLink = nav.querySelector('li.is-active a');
  const currentLabel = currentLink ? currentLink.textContent.trim() : (window.kangooAccountPage?.menu_label || 'Account menu');

  const toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.className = 'account-mobile-toggle';
  toggle.setAttribute('aria-expanded', 'false');
  toggle.innerHTML = `
    <span class="account-mobile-toggle__label">${currentLabel}</span>
    <span class="account-mobile-toggle__icon" aria-hidden="true">+</span>
  `;

  nav.parentNode.insertBefore(toggle, nav);

  function closeMenu() {
    accountRoot.classList.remove('is-account-menu-open');
    toggle.setAttribute('aria-expanded', 'false');
    const icon = toggle.querySelector('.account-mobile-toggle__icon');
    if (icon) {
      icon.textContent = '+';
    }
  }

  function openMenu() {
    accountRoot.classList.add('is-account-menu-open');
    toggle.setAttribute('aria-expanded', 'true');
    const icon = toggle.querySelector('.account-mobile-toggle__icon');
    if (icon) {
      icon.textContent = '−';
    }
  }

  toggle.addEventListener('click', function () {
    if (accountRoot.classList.contains('is-account-menu-open')) {
      closeMenu();
      return;
    }

    openMenu();
  });

  nav.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
      closeMenu();
    });
  });

  function copyText(value) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(value);
    }

    return new Promise(function (resolve, reject) {
      const textarea = document.createElement('textarea');
      textarea.value = value;
      textarea.setAttribute('readonly', '');
      textarea.style.position = 'fixed';
      textarea.style.top = '-1000px';
      textarea.style.left = '-1000px';
      document.body.appendChild(textarea);
      textarea.select();

      try {
        const didCopy = document.execCommand('copy');
        document.body.removeChild(textarea);
        didCopy ? resolve() : reject(new Error('Copy command was blocked.'));
      } catch (error) {
        document.body.removeChild(textarea);
        reject(error);
      }
    });
  }

  content.querySelectorAll('.kangoo-referrals-copy-button[data-copy-value]').forEach(function (button) {
    let resetTimer = null;
    const label = button.querySelector('span:last-child');
    const originalLabel = button.getAttribute('data-copy-label') || (label ? label.textContent : 'Copy');
    const successLabel = button.getAttribute('data-copy-success') || 'Copied';

    button.addEventListener('click', function () {
      const value = button.getAttribute('data-copy-value') || '';

      if (!value) {
        return;
      }

      copyText(value).then(function () {
        if (label) {
          label.textContent = successLabel;
        }

        button.classList.add('is-copied');
        window.clearTimeout(resetTimer);
        resetTimer = window.setTimeout(function () {
          if (label) {
            label.textContent = originalLabel;
          }

          button.classList.remove('is-copied');
        }, 2000);
      }).catch(function () {
        const field = button.closest('.kangoo-referrals-copy-field');
        const input = field ? field.querySelector('input') : null;

        if (input) {
          input.focus();
          input.select();
        }
      });
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeMenu();
    }
  });

  const passwordInputs = content.querySelectorAll('input[type="password"]');

  content.querySelectorAll('input, select, textarea').forEach(function (input) {
    const field = input.closest('.form-row, .woocommerce-form-row, p');
    const key = input.getAttribute('id') || input.getAttribute('name') || '';

    if (!field || !key) {
      return;
    }

    if (key === 'account_first_name') {
      field.classList.add('account-field--first-name');
    } else if (key === 'account_last_name') {
      field.classList.add('account-field--last-name');
    } else if (key === 'kangoo_account_dob') {
      field.classList.add('account-field--dob');
    } else if (key === 'account_display_name') {
      field.classList.add('account-field--display-name');
    } else if (key === 'account_email') {
      field.classList.add('account-field--email');
    } else if (key.indexOf('password') !== -1) {
      field.classList.add('account-field--password');
    }
  });

  content.querySelectorAll('p, .form-row, tr, li, div').forEach(function (element) {
    const text = (element.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

    if (text === 'default currency' || text.indexOf('default currency') === 0) {
      element.remove();
    }
  });

  passwordInputs.forEach(function (input) {
    const wrapper = document.createElement('div');
    wrapper.className = 'account-password-wrap';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'account-password-toggle';
    button.setAttribute('aria-label', 'Toggle password visibility');
    button.textContent = 'Show';

    button.addEventListener('click', function () {
      const isPassword = input.getAttribute('type') === 'password';
      input.setAttribute('type', isPassword ? 'text' : 'password');
      button.textContent = isPassword ? 'Hide' : 'Show';
    });

    wrapper.appendChild(button);
  });
});
