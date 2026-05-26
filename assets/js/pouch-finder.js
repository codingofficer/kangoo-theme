(function () {
  const finder = document.querySelector('[data-pouch-finder]');
  const result = document.querySelector('[data-finder-result]');
  const productJson = document.querySelector('[data-finder-products]');

  if (!finder || !result || !productJson) {
    return;
  }

  let products = [];

  try {
    products = JSON.parse(productJson.textContent || '[]');
  } catch (error) {
    products = [];
  }

  const steps = Array.from(finder.querySelectorAll('[data-step]'));
  const nextButton = finder.querySelector('[data-next-step]');
  const prevButton = finder.querySelector('[data-prev-step]');
  const count = finder.querySelector('[data-step-count]');
  const title = finder.querySelector('[data-step-title]');
  const helper = finder.querySelector('[data-step-helper]');
  const bar = finder.querySelector('[data-progress-bar]');
  const resetButton = result.querySelector('[data-reset-finder]');
  const resultTitle = result.querySelector('[data-result-title]');
  const resultCopy = result.querySelector('[data-result-copy]');
  const resultChips = result.querySelector('[data-result-chips]');
  const resultProducts = result.querySelector('[data-result-products]');
  let currentStep = 0;

  const stepMeta = [
    ['How experienced are you?', 'This helps us keep the strength recommendation sensible.'],
    ['What strength feels right?', 'Choose the intensity you are aiming for.'],
    ['Pick a flavour direction', 'We will match the closest flavour family first.'],
    ['When will you use it?', 'Use case helps refine strength and flavour.'],
    ['How much kick do you want?', 'One last check before we recommend your match.']
  ];

  const flavourTerms = {
    mint: ['mint', 'menthol', 'ice', 'cool', 'freeze', 'spearmint', 'peppermint'],
    fruit: ['berry', 'blueberry', 'strawberry', 'raspberry', 'apple', 'mango', 'peach', 'melon', 'fruit'],
    citrus: ['citrus', 'lemon', 'lime', 'orange', 'grapefruit'],
    sweet: ['cola', 'vanilla', 'coffee', 'caramel', 'candy', 'cherry', 'dessert', 'tropical']
  };

  function getValue(name) {
    const input = finder.querySelector('input[name="' + name + '"]:checked');
    return input ? input.value : '';
  }

  function updateStep() {
    steps.forEach(function (step, index) {
      step.classList.toggle('is-active', index === currentStep);
    });

    count.textContent = 'Step ' + (currentStep + 1) + ' of ' + steps.length;
    title.textContent = stepMeta[currentStep][0];
    helper.textContent = stepMeta[currentStep][1];
    bar.style.width = (((currentStep + 1) / steps.length) * 100) + '%';
    prevButton.style.visibility = currentStep === 0 ? 'hidden' : 'visible';
    nextButton.textContent = currentStep === steps.length - 1 ? 'Show my match' : 'Next';
    syncNextButton();
  }

  function syncNextButton() {
    const activeStep = steps[currentStep];
    const checked = activeStep.querySelector('input:checked');
    nextButton.disabled = !checked;
    nextButton.classList.toggle('is-disabled', !checked);
  }

  function targetStrength(values) {
    let target = values.strength;

    if (values.experience === 'new' && (target === 'strong' || target === 'extra')) {
      target = 'medium';
    }

    if (values.sensitivity === 'smooth' && target === 'extra') {
      target = 'strong';
    }

    if (values.sensitivity === 'maximum' && values.experience === 'regular') {
      target = target === 'light' ? 'medium' : target;
    }

    return target;
  }

  function productStrengthBucket(product) {
    const mg = Number(product.mg || 0);

    if (!mg) {
      const text = String(product.strength || '').toLowerCase();
      if (text.includes('extra')) return 'extra';
      if (text.includes('strong')) return 'strong';
      if (text.includes('medium')) return 'medium';
      if (text.includes('light')) return 'light';
      return '';
    }

    if (mg < 5) return 'light';
    if (mg < 10) return 'medium';
    if (mg < 15) return 'strong';
    return 'extra';
  }

  function flavourScore(product, flavour) {
    if (flavour === 'any') {
      return 8;
    }

    const haystack = (product.name + ' ' + product.flavour).toLowerCase();
    const terms = flavourTerms[flavour] || [];
    return terms.some(function (term) { return haystack.includes(term); }) ? 22 : 0;
  }

  function strengthScore(product, strength) {
    const bucket = productStrengthBucket(product);
    const order = ['light', 'medium', 'strong', 'extra'];
    const distance = Math.abs(order.indexOf(bucket) - order.indexOf(strength));

    if (!bucket || distance > 3) {
      return 0;
    }

    return [30, 18, 7, 0][distance];
  }

  function useCaseScore(product, useCase) {
    const text = (product.name + ' ' + product.flavour).toLowerCase();

    if (useCase === 'after-meal' && /mint|menthol|ice|cool|citrus|lemon|lime/.test(text)) return 10;
    if (useCase === 'focus' && /mint|menthol|ice|cool/.test(text)) return 8;
    if (useCase === 'night-out' && /berry|cola|cherry|mango|tropical|strong/.test(text)) return 8;
    if (useCase === 'daily') return 6;
    return 0;
  }

  function buildResultCopy(values, strength) {
    const labels = {
      light: 'a lighter, smoother pouch',
      medium: 'a balanced pouch with a clear but controlled feel',
      strong: 'a stronger pouch with a more noticeable kick',
      extra: 'an extra strong pouch for experienced users'
    };

    const flavour = values.flavour === 'any' ? 'open flavour profile' : values.flavour + ' flavour profile';
    return 'We would start you around ' + labels[strength] + ', with a ' + flavour + '. You can use the alternatives below to go smoother or stronger.';
  }

  function renderProducts(scored) {
    if (!scored.length) {
      resultProducts.innerHTML = '<div class="pouch-finder__empty">No matching products were found yet. Add products with strength and flavour attributes to improve this finder.</div>';
      return;
    }

    resultProducts.innerHTML = scored.slice(0, 3).map(function (item, index) {
      const product = item.product;
      const tag = index === 0 ? 'Best match' : (index === 1 ? 'Smooth alternative' : 'Another good option');
      const image = product.image ? '<img src="' + escapeHtml(product.image) + '" alt="">' : '';
      const meta = [product.brand, product.strength, product.flavour].filter(Boolean).join(' - ');

      return '<article class="pouch-finder-card">' +
        '<a class="pouch-finder-card__image" href="' + escapeHtml(product.url) + '">' + image + '</a>' +
        '<div class="pouch-finder-card__body">' +
          '<span>' + tag + '</span>' +
          '<h3><a href="' + escapeHtml(product.url) + '">' + escapeHtml(product.name) + '</a></h3>' +
          '<p>' + escapeHtml(meta) + '</p>' +
          '<div class="pouch-finder-card__price">' + (product.price || '') + '</div>' +
          '<a class="btn btn--primary" href="' + escapeHtml(product.url) + '">View pouch</a>' +
        '</div>' +
      '</article>';
    }).join('');
  }

  function showResult() {
    const values = {
      experience: getValue('experience'),
      strength: getValue('strength'),
      flavour: getValue('flavour'),
      useCase: getValue('use_case'),
      sensitivity: getValue('sensitivity')
    };
    const strength = targetStrength(values);
    const scored = products
      .filter(function (product) { return product.stock; })
      .map(function (product) {
        return {
          product: product,
          score: strengthScore(product, strength) + flavourScore(product, values.flavour) + useCaseScore(product, values.useCase)
        };
      })
      .sort(function (a, b) { return b.score - a.score; });

    resultTitle.textContent = strength.charAt(0).toUpperCase() + strength.slice(1) + ' ' + (values.flavour === 'any' ? 'pouch' : values.flavour) + ' match';
    resultCopy.textContent = buildResultCopy(values, strength);
    resultChips.innerHTML = [
      'Strength: ' + strength,
      'Flavour: ' + values.flavour.replace('-', ' '),
      'Use: ' + values.useCase.replace('-', ' ')
    ].map(function (chip) {
      return '<span>' + escapeHtml(chip) + '</span>';
    }).join('');

    renderProducts(scored);
    result.hidden = false;
    result.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

  finder.addEventListener('change', syncNextButton);

  nextButton.addEventListener('click', function () {
    if (nextButton.disabled) {
      return;
    }

    if (currentStep < steps.length - 1) {
      currentStep += 1;
      updateStep();
      return;
    }

    showResult();
  });

  prevButton.addEventListener('click', function () {
    currentStep = Math.max(0, currentStep - 1);
    updateStep();
  });

  if (resetButton) {
    resetButton.addEventListener('click', function () {
      finder.querySelectorAll('input[type="radio"]').forEach(function (input) {
        input.checked = false;
      });

      currentStep = 0;
      result.hidden = true;
      updateStep();
      finder.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  updateStep();
}());
