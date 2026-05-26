(function () {
  const root = document.querySelector('[data-build-box]');
  const json = document.querySelector('[data-box-products-json]');

  if (!root || !json) {
    return;
  }

  let products = [];

  try {
    products = JSON.parse(json.textContent || '[]');
  } catch (error) {
    products = [];
  }

  const sizeButtons = Array.from(root.querySelectorAll('[data-box-size]'));
  const search = root.querySelector('[data-box-search]');
  const brandFilter = root.querySelector('[data-box-brand]');
  const flavourFilter = root.querySelector('[data-box-flavour]');
  const productGrid = root.querySelector('[data-box-products]');
  const productPager = root.querySelector('[data-box-pager]');
  const selectedList = root.querySelector('[data-box-selected]');
  const title = root.querySelector('[data-box-summary-title]');
  const meter = root.querySelector('[data-box-meter]');
  const totalEl = root.querySelector('[data-box-total]');
  const savingEl = root.querySelector('[data-box-saving]');
  const submit = root.querySelector('[data-box-submit]');
  let boxSize = 5;
  let page = 1;
  const pageSize = 5;
  const quantities = {};

  function selectedCount() {
    return Object.keys(quantities).reduce(function (sum, id) {
      return sum + quantities[id];
    }, 0);
  }

  function productById(id) {
    return products.find(function (product) {
      return product.id === Number(id);
    });
  }

  function money(value, currency) {
    const amount = Number(value || 0);
    const symbol = decodeHtml(currency || '£');
    return symbol + amount.toFixed(2);
  }

  function getTotals() {
    return Object.keys(quantities).reduce(function (totals, id) {
      const product = productById(id);

      if (!product) {
        return totals;
      }

      const qty = quantities[id];
      totals.price += Number(product.price || 0) * qty;
      totals.regular += Number(product.regularPrice || product.price || 0) * qty;
      totals.currency = product.currency || totals.currency;
      return totals;
    }, { price: 0, regular: 0, currency: '£' });
  }

  function filterProducts() {
    const query = search ? search.value.trim().toLowerCase() : '';
    const brand = brandFilter ? brandFilter.value : '';
    const flavour = flavourFilter ? flavourFilter.value : '';

    return products.filter(function (product) {
      const haystack = [
        product.name,
        product.brand,
        product.flavour,
        product.strength
      ].join(' ').toLowerCase();

      if (query && !haystack.includes(query)) {
        return false;
      }

      if (brand && String(product.brand || '') !== brand) {
        return false;
      }

      if (flavour && String(product.flavour || '') !== flavour) {
        return false;
      }

      return true;
    });
  }

  function populateFilter(select, values) {
    if (!select) {
      return;
    }

    Array.from(new Set(values.filter(Boolean))).sort().forEach(function (value) {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = value;
      select.appendChild(option);
    });
  }

  function productCard(product) {
    const qty = quantities[product.id] || 0;
    const remaining = boxSize - selectedCount();
    const stockQuantity = Number.isFinite(Number(product.stockQuantity)) && Number(product.stockQuantity) > 0 ? Number(product.stockQuantity) : null;
    const lowStockText = stockQuantity !== null && stockQuantity < 3 ? '<em class="box-product__stock">Only ' + stockQuantity + ' left</em>' : '';
    const canAdd = product.stock && (qty > 0 || remaining > 0) && (stockQuantity === null || qty < stockQuantity);
    const image = product.image ? '<img src="' + escapeHtml(product.image) + '" alt="">' : '';

    return '<article class="box-product' + (qty ? ' is-selected' : '') + '">' +
      '<a class="box-product__image" href="' + escapeHtml(product.url) + '">' + image + '</a>' +
      '<div class="box-product__body">' +
        '<h3>' + escapeHtml(product.name) + '</h3>' +
        '<p>' + escapeHtml([product.strength, product.flavour].filter(Boolean).join(' - ')) + '</p>' +
        '<div class="box-product__meta">' +
          '<span>' + (product.priceHtml || escapeHtml(money(product.price, product.currency))) + '</span>' +
          '<span>' + product.pouchCount + ' pouches</span>' +
        '</div>' +
        lowStockText +
        '<div class="box-product__qty">' +
          '<button type="button" data-box-minus="' + product.id + '" aria-label="Remove one" ' + (!qty ? 'disabled' : '') + '>-</button>' +
          '<strong>' + qty + '</strong>' +
          '<button type="button" data-box-plus="' + product.id + '" aria-label="Add one" ' + (!canAdd || remaining <= 0 ? 'disabled' : '') + '>+</button>' +
        '</div>' +
      '</div>' +
    '</article>';
  }

  function renderProducts() {
    const filtered = filterProducts();
    const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
    page = Math.min(page, totalPages);
    const visible = filtered.slice((page - 1) * pageSize, page * pageSize);

    if (!visible.length) {
      productGrid.innerHTML = '<div class="build-box__empty">No matching pouches found.</div>';
      if (productPager) {
        productPager.innerHTML = '';
      }
      return;
    }

    productGrid.innerHTML = visible.map(productCard).join('');

    if (productPager) {
      productPager.innerHTML = totalPages > 1 ? '<button type="button" data-box-page="prev" ' + (page <= 1 ? 'disabled' : '') + '>Previous</button>' +
        '<span>Page ' + page + ' of ' + totalPages + '</span>' +
        '<button type="button" data-box-page="next" ' + (page >= totalPages ? 'disabled' : '') + '>Next</button>' : '';
    }
  }

  function renderSelected() {
    const ids = Object.keys(quantities).filter(function (id) {
      return quantities[id] > 0;
    });

    if (!ids.length) {
      selectedList.innerHTML = '<p>Start adding pouches to build your bundle.</p>';
      return;
    }

    selectedList.innerHTML = ids.map(function (id) {
      const product = productById(id);

      if (!product) {
        return '';
      }

      return '<div class="build-box__selected-item">' +
        '<span>' + escapeHtml(product.name) + '</span>' +
        '<strong>x' + quantities[id] + '</strong>' +
      '</div>';
    }).join('');
  }

  function renderSummary() {
    const count = selectedCount();
    const totals = getTotals();
    const saving = Math.max(0, totals.regular - totals.price);

    title.textContent = count + ' of ' + boxSize + ' cans';
    meter.style.width = Math.min(100, (count / boxSize) * 100) + '%';
    totalEl.textContent = money(totals.price, totals.currency);
    savingEl.textContent = money(saving, totals.currency);

    submit.disabled = count !== boxSize;
    submit.textContent = count === boxSize ? 'Add bundle to cart - ' + money(totals.price, totals.currency) : 'Fill your bundle';
  }

  function renderAll() {
    sizeButtons.forEach(function (button) {
      button.classList.toggle('is-active', Number(button.getAttribute('data-box-size')) === boxSize);
    });

    renderProducts();
    renderSelected();
    renderSummary();
  }

  function trimToSize() {
    let count = selectedCount();
    const ids = Object.keys(quantities).reverse();

    ids.forEach(function (id) {
      while (count > boxSize && quantities[id] > 0) {
        quantities[id] -= 1;
        count -= 1;
      }

      if (quantities[id] <= 0) {
        delete quantities[id];
      }
    });
  }

  function addProduct(id) {
    if (selectedCount() >= boxSize) {
      return;
    }

    const product = productById(id);
    const stockQuantity = product && Number.isFinite(Number(product.stockQuantity)) && Number(product.stockQuantity) > 0 ? Number(product.stockQuantity) : null;

    if (stockQuantity !== null && (quantities[id] || 0) >= stockQuantity) {
      return;
    }

    quantities[id] = (quantities[id] || 0) + 1;
    renderAll();
  }

  function removeProduct(id) {
    if (!quantities[id]) {
      return;
    }

    quantities[id] -= 1;

    if (quantities[id] <= 0) {
      delete quantities[id];
    }

    renderAll();
  }

  function setButtonState(text, loading, keepText) {
    if (!keepText) {
      submit.textContent = text;
    }

    submit.disabled = loading;
    submit.classList.toggle('is-loading', loading);
  }

  function addLineToCart(productId, quantity) {
    if (!window.kangooAjaxCart || !window.kangooAjaxCart.ajax_url || !window.kangooAjaxCart.add_to_cart_nonce) {
      return Promise.reject(new Error('Cart is unavailable.'));
    }

    const formData = new FormData();
    formData.append('action', 'kangoo_ajax_add_to_cart');
    formData.append('nonce', window.kangooAjaxCart.add_to_cart_nonce);
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    return fetch(window.kangooAjaxCart.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).then(function (response) {
      return response.json();
    }).then(function (payload) {
      if (!payload || payload.success === false) {
        throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Could not add to cart.');
      }

      return payload.data || {};
    });
  }

  function applyFragments(data) {
    const fragments = data.fragments || {};

    Object.keys(fragments).forEach(function (selector) {
      const nodes = document.querySelectorAll(selector);
      nodes.forEach(function (node) {
        node.outerHTML = fragments[selector];
      });
    });

    if (window.jQuery) {
      window.jQuery(document.body).trigger('added_to_cart', [fragments, data.cart_hash, window.jQuery(submit)]);
      window.jQuery(document.body).trigger('wc_fragments_refreshed');
    }

    if (window.kangooOpenCartDrawerIfWasEmpty) {
      window.kangooOpenCartDrawerIfWasEmpty(fragments);
    }
  }

  function addBoxToCart() {
    if (selectedCount() !== boxSize) {
      return;
    }

    const lines = Object.keys(quantities).map(function (id) {
      return {
        id: id,
        quantity: quantities[id]
      };
    });

    setButtonState('', true, true);

    lines.reduce(function (promise, line) {
      return promise.then(function (lastData) {
        return addLineToCart(line.id, line.quantity).then(function (data) {
          return data || lastData;
        });
      });
    }, Promise.resolve(null)).then(function (lastData) {
      if (lastData) {
        applyFragments(lastData);
      }

      setButtonState('Added to cart', true);
      if (window.kangooSparkButton) {
        window.kangooSparkButton(submit);
      }
      window.setTimeout(function () {
        renderSummary();
      }, 1400);
    }).catch(function (error) {
      setButtonState(error.message || 'Could not add bundle', false);
      window.setTimeout(renderSummary, 1800);
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

  function decodeHtml(value) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = String(value || '');
    return textarea.value;
  }

  root.addEventListener('click', function (event) {
    const sizeButton = event.target.closest('[data-box-size]');
    const plus = event.target.closest('[data-box-plus]');
    const minus = event.target.closest('[data-box-minus]');
    const pageButton = event.target.closest('[data-box-page]');

    if (sizeButton) {
      boxSize = Number(sizeButton.getAttribute('data-box-size')) || 5;
      trimToSize();
      renderAll();
      return;
    }

    if (plus) {
      addProduct(plus.getAttribute('data-box-plus'));
      return;
    }

    if (minus) {
      removeProduct(minus.getAttribute('data-box-minus'));
      return;
    }

    if (pageButton) {
      page += pageButton.getAttribute('data-box-page') === 'next' ? 1 : -1;
      renderProducts();
    }
  });

  if (search) {
    search.addEventListener('input', function () {
      page = 1;
      renderProducts();
    });
  }

  [brandFilter, flavourFilter].forEach(function (field) {
    if (!field) {
      return;
    }

    field.addEventListener('change', function () {
      page = 1;
      renderProducts();
    });
  });

  submit.addEventListener('click', addBoxToCart);

  populateFilter(brandFilter, products.map(function (product) { return product.brand; }));
  populateFilter(flavourFilter, products.map(function (product) { return product.flavour; }));
  renderAll();
}());
