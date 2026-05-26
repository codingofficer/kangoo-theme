(function () {
  const root = document.querySelector('[data-pouch-compare]');
  const json = document.querySelector('[data-compare-products]');
  const results = document.querySelector('[data-compare-results]');

  if (!root || !json || !results) {
    return;
  }

  let products = [];

  try {
    products = JSON.parse(json.textContent || '[]');
  } catch (error) {
    products = [];
  }

  const search = root.querySelector('[data-compare-search]');
  const picker = root.querySelector('[data-compare-picker]');
  const selectedBar = root.querySelector('[data-compare-selected]');
  const table = results.querySelector('[data-compare-table]');
  const clearButton = results.querySelector('[data-compare-clear]');
  const selected = [];
  const maxSelected = 4;

  function productText(product) {
    return [
      product.name,
      product.brand,
      product.flavour,
      product.strength,
      product.categories ? product.categories.join(' ') : ''
    ].join(' ').toLowerCase();
  }

  function formatMoney(value, currency) {
    const amount = Number(value || 0);

    if (!amount) {
      return 'N/A';
    }

    return decodeHtml(currency || '£') + amount.toFixed(2);
  }

  function strengthLevel(product) {
    const mg = Number(product.mg || 0);
    const text = String(product.strength || '').toLowerCase();

    if (mg >= 15 || text.includes('extra')) return 'Extra strong';
    if (mg >= 10 || text.includes('strong')) return 'Strong';
    if (mg >= 5 || text.includes('medium')) return 'Balanced';
    if (mg > 0 || text.includes('light')) return 'Light';
    return 'Not specified';
  }

  function bestFor(product) {
    const flavour = (product.name + ' ' + product.flavour).toLowerCase();
    const level = strengthLevel(product);

    if (level === 'Extra strong') return 'Experienced users';
    if (level === 'Strong') return 'Noticeable kick';
    if (/mint|menthol|ice|cool|freeze/.test(flavour)) return 'Fresh daily use';
    if (/berry|mango|tropical|peach|melon|fruit/.test(flavour)) return 'Flavour exploring';
    if (/citrus|lemon|lime|orange/.test(flavour)) return 'After meals';
    return 'Everyday rotation';
  }

  function productCard(product) {
    const isSelected = selected.includes(product.id);
    const disabled = !isSelected && selected.length >= maxSelected;
    const image = product.image ? '<img src="' + escapeHtml(product.image) + '" alt="">' : '';

    return '<article class="compare-product' + (isSelected ? ' is-selected' : '') + '">' +
      '<a class="compare-product__image" href="' + escapeHtml(product.url) + '">' + image + '</a>' +
      '<div class="compare-product__body">' +
        '<h3>' + escapeHtml(product.name) + '</h3>' +
        '<p>' + escapeHtml([product.brand, product.strength, product.flavour].filter(Boolean).join(' - ')) + '</p>' +
        '<div class="compare-product__meta">' +
          '<span>' + escapeHtml(formatMoney(product.pricePerPouch, product.currency)) + ' per pouch</span>' +
          '<span>' + (product.stock ? 'In stock' : 'Sold Out') + '</span>' +
        '</div>' +
        '<button type="button" class="btn ' + (isSelected ? 'btn--secondary' : 'btn--primary') + '" data-compare-toggle="' + product.id + '"' + (disabled ? ' disabled' : '') + '>' +
          (isSelected ? 'Remove' : 'Compare') +
        '</button>' +
      '</div>' +
    '</article>';
  }

  function visibleProducts() {
    const query = search ? search.value.trim().toLowerCase() : '';

    if (!query) {
      return products.slice(0, 24);
    }

    return products.filter(function (product) {
      return productText(product).includes(query);
    }).slice(0, 24);
  }

  function renderPicker() {
    const visible = visibleProducts();

    if (!visible.length) {
      picker.innerHTML = '<div class="pouch-compare__empty">No products found. Try searching mint, berry, strong, ZYN or VELO.</div>';
      return;
    }

    picker.innerHTML = visible.map(productCard).join('');
  }

  function renderSelectedBar() {
    if (!selected.length) {
      selectedBar.innerHTML = '<span>Pick 2 to 4 products to compare.</span>';
      return;
    }

    selectedBar.innerHTML = '<span>' + selected.length + ' of ' + maxSelected + ' selected</span>' +
      selected.map(function (id) {
        const product = products.find(function (item) { return item.id === id; });
        return product ? '<button type="button" data-compare-toggle="' + product.id + '">' + escapeHtml(product.name) + '</button>' : '';
      }).join('');
  }

  function row(label, mapper) {
    const selectedProducts = selected.map(function (id) {
      return products.find(function (product) { return product.id === id; });
    }).filter(Boolean);

    return '<tr><th scope="row">' + escapeHtml(label) + '</th>' +
      selectedProducts.map(function (product) {
        return '<td>' + mapper(product) + '</td>';
      }).join('') +
    '</tr>';
  }

  function renderTable() {
    if (selected.length < 2) {
      results.hidden = true;
      table.innerHTML = '';
      return;
    }

    const selectedProducts = selected.map(function (id) {
      return products.find(function (product) { return product.id === id; });
    }).filter(Boolean);

    const header = '<thead><tr><th scope="col">Detail</th>' + selectedProducts.map(function (product) {
      const image = product.image ? '<img src="' + escapeHtml(product.image) + '" alt="">' : '';
      return '<th scope="col"><a href="' + escapeHtml(product.url) + '">' + image + '<span>' + escapeHtml(product.name) + '</span></a></th>';
    }).join('') + '</tr></thead>';

    const body = '<tbody>' +
      row('Brand', function (product) { return escapeHtml(product.brand || 'N/A'); }) +
      row('Strength', function (product) { return escapeHtml(product.strength || strengthLevel(product)); }) +
      row('Strength feel', function (product) { return '<strong>' + escapeHtml(strengthLevel(product)) + '</strong>'; }) +
      row('Flavour', function (product) { return escapeHtml(product.flavour || 'N/A'); }) +
      row('Price', function (product) { return product.priceHtml || escapeHtml(formatMoney(product.price, product.currency)); }) +
      row('Price per pouch', function (product) { return escapeHtml(formatMoney(product.pricePerPouch, product.currency)); }) +
      row('Pouches per can', function (product) { return escapeHtml(String(product.pouchCount || 'N/A')); }) +
      row('Best for', function (product) { return escapeHtml(bestFor(product)); }) +
      row('Availability', function (product) { return product.stock ? '<span class="compare-stock compare-stock--in">In stock</span>' : '<span class="compare-stock">Sold Out</span>'; }) +
      row('Action', function (product) { return '<a class="btn btn--primary" href="' + escapeHtml(product.url) + '">View pouch</a>'; }) +
    '</tbody>';

    table.innerHTML = header + body;
    results.hidden = false;
  }

  function renderAll() {
    renderSelectedBar();
    renderPicker();
    renderTable();
  }

  function toggleProduct(id) {
    const productId = Number(id);
    const index = selected.indexOf(productId);

    if (index >= 0) {
      selected.splice(index, 1);
    } else if (selected.length < maxSelected) {
      selected.push(productId);
    }

    renderAll();
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

  function decodeHtml(value) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = String(value || '');
    return textarea.value;
  }

  root.addEventListener('click', function (event) {
    const button = event.target.closest('[data-compare-toggle]');

    if (!button) {
      return;
    }

    toggleProduct(button.getAttribute('data-compare-toggle'));
  });

  if (search) {
    search.addEventListener('input', renderPicker);
  }

  if (clearButton) {
    clearButton.addEventListener('click', function () {
      selected.splice(0, selected.length);
      renderAll();
    });
  }

  renderAll();
}());
