(function () {
  const config = window.kangooProductReviews || {};

  function updateHelpfulButton(button, count) {
    const label = button.querySelector('span:last-child');

    if (label) {
      label.textContent = 'Helpful (' + count + ')';
    }

    button.classList.add('is-voted');
    button.disabled = true;
  }

  document.addEventListener('click', function (event) {
    const summaryLink = event.target.closest('.product-review-summary');

    if (summaryLink && window.matchMedia('(max-width: 767px)').matches) {
      const accordion = document.querySelector('.product-review-accordion');

      if (accordion) {
        event.preventDefault();
        accordion.open = true;
        accordion.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
      }
    }

    const button = event.target.closest('[data-kangoo-review-helpful]');

    if (!button || button.disabled || !config.rest_url) {
      return;
    }

    const reviewId = button.getAttribute('data-kangoo-review-helpful');

    if (!reviewId) {
      return;
    }

    button.disabled = true;

    fetch(config.rest_url.replace(/\/$/, '') + '/reviews/' + encodeURIComponent(reviewId) + '/helpful', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json'
      }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Review helpful request failed');
        }

        return response.json();
      })
      .then(function (data) {
        updateHelpfulButton(button, data.helpful_count || 0);
      })
      .catch(function () {
        button.disabled = false;
      });
  });
})();
