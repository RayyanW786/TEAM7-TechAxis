import { apiJson, formatMoney, escapeHtml } from './shared/api.js';

const STORAGE_KEY = 'tx_product_compare_v1';
const MAX_COMPARE_ITEMS = 2;

const toggleButtons = [...document.querySelectorAll('[data-compare-toggle]')];

if (toggleButtons.length === 0) {
  // This module is loaded only on pages that render compare controls.
} else {
  initCompare();
}

function initCompare() {
  const compareUi = createCompareUi();
  let selectedItems = normalizeSelection(readSelection());

  compareUi.modalElement.addEventListener('show.bs.modal', () => {
    compareUi.dock.classList.add('compare-dock-suspended');
    document.body.classList.remove('has-compare-dock');
  });

  compareUi.modalElement.addEventListener('hidden.bs.modal', () => {
    compareUi.dock.classList.remove('compare-dock-suspended');
    render();
  });

  persistSelection(selectedItems);
  render();

  for (const button of toggleButtons) {
    if (!button.dataset.compareDefaultLabel) {
      button.dataset.compareDefaultLabel = button.textContent.trim() || 'Add to compare';
    }

    button.addEventListener('click', async () => {
      const product = parseProductData(button);
      if (!product) {
        showMessage('Could not read product info for comparison.');
        return;
      }

      const existingIndex = selectedItems.findIndex(item => item.id === product.id);
      if (existingIndex >= 0) {
        selectedItems.splice(existingIndex, 1);
        persistSelection(selectedItems);
        render();
        return;
      }

      if (selectedItems.length >= MAX_COMPARE_ITEMS) {
        showMessage('You can compare only two products at once. Remove one first.');
        return;
      }

      selectedItems.push(product);
      persistSelection(selectedItems);
      render();

      if (selectedItems.length === MAX_COMPARE_ITEMS) {
        showMessage('Two products selected. Click Compare.');
      }
    });
  }

  compareUi.selectedContainer.addEventListener('click', event => {
    const removeButton = event.target.closest('[data-compare-remove-id]');
    if (!removeButton) return;

    const id = Number(removeButton.dataset.compareRemoveId || 0);
    if (!id) return;

    selectedItems = selectedItems.filter(item => item.id !== id);
    persistSelection(selectedItems);
    render();
  });

  compareUi.clearButton.addEventListener('click', () => {
    selectedItems = [];
    persistSelection(selectedItems);
    render();
  });

  compareUi.compareButton.addEventListener('click', async () => {
    if (selectedItems.length !== MAX_COMPARE_ITEMS) return;

    compareUi.setModalState('loading');
    compareUi.modalInstance.show();

    try {
      const detailed = await Promise.all(selectedItems.map(loadCompareProductDetails));
      compareUi.renderCompareTable(detailed);
      compareUi.setModalState('ready');
    } catch (_error) {
      compareUi.setModalState('error');
    }
  });

  function render() {
    selectedItems = normalizeSelection(readSelection());
    syncButtons(selectedItems);
    renderDock(selectedItems, compareUi);
  }

  let messageTimer = null;
  function showMessage(message) {
    compareUi.messageBox.textContent = message;
    compareUi.messageBox.classList.remove('d-none');

    if (messageTimer) window.clearTimeout(messageTimer);
    messageTimer = window.setTimeout(() => {
      compareUi.messageBox.classList.add('d-none');
      compareUi.messageBox.textContent = '';
    }, 2600);
  }
}

function readSelection() {
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (_error) {
    return [];
  }
}

function persistSelection(items) {
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(normalizeSelection(items)));
  } catch (_error) {
    // Ignore storage failures.
  }
}

function normalizeSelection(items) {
  if (!Array.isArray(items)) return [];

  const seen = new Set();
  const normalized = [];

  for (const item of items) {
    const id = Number(item?.id || 0);
    if (!id || seen.has(id)) continue;
    seen.add(id);

    normalized.push({
      id,
      slug: String(item.slug || ''),
      name: String(item.name || `Product ${id}`),
      price: toPrice(item.price),
      summary: String(item.summary || ''),
      url: String(item.url || ''),
      image: String(item.image || ''),
    });

    if (normalized.length >= MAX_COMPARE_ITEMS) break;
  }

  return normalized;
}

function parseProductData(button) {
  const id = Number(button.dataset.productId || 0);
  if (!id) return null;

  return {
    id,
    slug: button.dataset.productSlug || '',
    name: button.dataset.productName || `Product ${id}`,
    price: toPrice(button.dataset.productPrice),
    summary: button.dataset.productSummary || '',
    url: button.dataset.productUrl || '',
    image: button.dataset.productImage || '',
  };
}

function toPrice(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : null;
}

function syncButtons(selectedItems) {
  const selectedIds = new Set(selectedItems.map(item => item.id));

  for (const button of toggleButtons) {
    const id = Number(button.dataset.productId || 0);
    const selected = id > 0 && selectedIds.has(id);
    const defaultLabel = button.dataset.compareDefaultLabel || 'Add to compare';

    button.classList.toggle('active', selected);
    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
    button.textContent = selected ? 'Remove from compare' : defaultLabel;
  }
}

function renderDock(selectedItems, compareUi) {
  compareUi.dock.classList.toggle('d-none', selectedItems.length === 0);
  document.body.classList.toggle('has-compare-dock', selectedItems.length > 0);

  compareUi.countText.textContent = `${selectedItems.length} of ${MAX_COMPARE_ITEMS} selected`;
  compareUi.compareButton.disabled = selectedItems.length !== MAX_COMPARE_ITEMS;

  if (selectedItems.length === 0) {
    compareUi.selectedContainer.innerHTML = '';
    compareUi.messageBox.classList.add('d-none');
    compareUi.messageBox.textContent = '';
    return;
  }

  compareUi.selectedContainer.innerHTML = selectedItems
    .map(item => {
      const url = item.url || `/products/${encodeURIComponent(item.slug || String(item.id))}`;
      return `
        <div class="compare-chip">
          <a class="compare-chip-link" href="${escapeHtml(url)}">${escapeHtml(item.name)}</a>
          <button type="button" class="compare-chip-remove" data-compare-remove-id="${item.id}" aria-label="Remove ${escapeHtml(item.name)} from compare">x</button>
        </div>
      `;
    })
    .join('');
}

async function loadCompareProductDetails(storedItem) {
  const fallbackImages = storedItem.image ? [storedItem.image] : [];

  const fallback = {
    id: storedItem.id,
    name: storedItem.name,
    url: storedItem.url || `/products/${encodeURIComponent(storedItem.slug || String(storedItem.id))}`,
    image: storedItem.image || '',
    images: fallbackImages,
    priceText: storedItem.price === null ? 'N/A' : formatMoney(storedItem.price),
    brand: 'N/A',
    category: 'N/A',
    sku: 'N/A',
    variants: 'No',
    summary: storedItem.summary || 'No summary available.',
  };

  try {
    const data = await apiJson(`/api/products/${storedItem.id}`);
    const product = data?.product;
    if (!product) return fallback;

    const effectivePrice = Number(data.effective_price ?? product.price);
    const variantCount = Array.isArray(product.variants) ? product.variants.length : 0;
    const images = Array.isArray(product.images)
      ? product.images.map(entry => entry?.url).filter(Boolean)
      : [];

    return {
      id: storedItem.id,
      name: product.name || fallback.name,
      url: storedItem.url || (product.slug ? `/products/${product.slug}` : fallback.url),
      image: (Array.isArray(product.images) && product.images[0]?.url) ? product.images[0].url : fallback.image,
      images: images.length > 0 ? images : fallbackImages,
      priceText: Number.isFinite(effectivePrice) ? formatMoney(effectivePrice) : fallback.priceText,
      brand: product.brand?.name || 'N/A',
      category: product.category?.name || 'N/A',
      sku: product.sku || 'N/A',
      variants: product.has_variants ? `Yes (${variantCount})` : 'No',
      summary: product.summary || fallback.summary,
    };
  } catch (_error) {
    return fallback;
  }
}

function initCompareImageCarousels(rootElement) {
  const carousels = [...rootElement.querySelectorAll('.compare-image-carousel')];

  for (const carousel of carousels) {
    const images = [...carousel.querySelectorAll('.compare-carousel-image')];
    if (images.length <= 1) continue;

    const prevButton = carousel.querySelector('[data-carousel-dir="-1"]');
    const nextButton = carousel.querySelector('[data-carousel-dir="1"]');
    const counter = carousel.querySelector('.compare-carousel-counter');
    let currentIndex = 0;

    const render = () => {
      images.forEach((image, index) => {
        image.classList.toggle('is-active', index === currentIndex);
      });

      if (counter) {
        counter.textContent = `${currentIndex + 1} / ${images.length}`;
      }
    };

    prevButton?.addEventListener('click', () => {
      currentIndex = (currentIndex - 1 + images.length) % images.length;
      render();
    });

    nextButton?.addEventListener('click', () => {
      currentIndex = (currentIndex + 1) % images.length;
      render();
    });

    render();
  }
}

function createCompareUi() {
  const dock = document.createElement('div');
  dock.id = 'compareDock';
  dock.className = 'compare-dock d-none';
  dock.innerHTML = `
    <div class="compare-dock-inner">
      <div class="compare-dock-heading">
        <span class="small text-muted">Product compare</span>
        <strong id="compareDockCount">0 of 2 selected</strong>
      </div>
      <div id="compareDockSelected" class="compare-dock-selected"></div>
      <div class="compare-dock-actions">
        <button id="compareNowButton" type="button" class="btn btn-primary btn-sm" disabled>Compare</button>
        <button id="compareClearButton" type="button" class="btn btn-outline-secondary btn-sm">Clear</button>
      </div>
    </div>
    <div id="compareDockMessage" class="compare-dock-message d-none"></div>
  `;

  document.body.appendChild(dock);

  const modal = document.createElement('div');
  modal.id = 'compareModal';
  modal.className = 'modal fade';
  modal.tabIndex = -1;
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="h5 mb-0">Product comparison</h2>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="compareLoading">Loading comparison...</div>
          <div id="compareError" class="alert alert-danger d-none mb-0">Could not load product details for comparison.</div>
          <div id="compareContent" class="d-none"></div>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  const loading = modal.querySelector('#compareLoading');
  const error = modal.querySelector('#compareError');
  const content = modal.querySelector('#compareContent');

  return {
    dock,
    modalElement: modal,
    selectedContainer: dock.querySelector('#compareDockSelected'),
    countText: dock.querySelector('#compareDockCount'),
    compareButton: dock.querySelector('#compareNowButton'),
    clearButton: dock.querySelector('#compareClearButton'),
    messageBox: dock.querySelector('#compareDockMessage'),
    modalInstance: bootstrap.Modal.getOrCreateInstance(modal),
    setModalState(state) {
      const isLoading = state === 'loading';
      const isError = state === 'error';
      const isReady = state === 'ready';

      loading.classList.toggle('d-none', !isLoading);
      error.classList.toggle('d-none', !isError);
      content.classList.toggle('d-none', !isReady);
    },
        renderCompareTable(items) {
      const renderProductCell = (item) => {
        const images = Array.isArray(item.images) ? item.images : [];
        const imageBlock = images.length > 0
          ? `
            <div class="compare-image-carousel" aria-label="${escapeHtml(item.name)} images">
              ${images.map((imageUrl, index) => `
                <img
                  src="${escapeHtml(imageUrl)}"
                  alt="${escapeHtml(item.name)} image ${index + 1}"
                  class="compare-carousel-image ${index === 0 ? 'is-active' : ''}"
                  loading="lazy"
                >
              `).join('')}
              ${images.length > 1 ? `
                <button type="button" class="compare-carousel-control prev" data-carousel-dir="-1" aria-label="Previous image">&lsaquo;</button>
                <button type="button" class="compare-carousel-control next" data-carousel-dir="1" aria-label="Next image">&rsaquo;</button>
                <div class="compare-carousel-counter">1 / ${images.length}</div>
              ` : ''}
            </div>
          `
          : `<div class="compare-product-image compare-product-image-placeholder">No image</div>`;

        return `
          <article class="compare-product-cell">
            <h3 class="h6 mb-0">${escapeHtml(item.name)}</h3>
            <div class="fw-semibold compare-product-price">${escapeHtml(item.priceText)}</div>
            <a href="${escapeHtml(item.url)}" class="small text-decoration-none compare-product-link">View product</a>
            ${imageBlock}
          </article>
        `;
      };

      const rows = [
        { label: 'Brand', values: items.map(item => item.brand) },
        { label: 'Category', values: items.map(item => item.category) },
        { label: 'SKU', values: items.map(item => item.sku) },
        { label: 'Variants', values: items.map(item => item.variants) },
        { label: 'Summary', values: items.map(item => item.summary) },
      ];

      const rowsHtml = rows
        .map(row => `
          <div class="compare-field-row">
            <div class="compare-field-label">${escapeHtml(row.label)}</div>
            <div class="compare-field-values">
              <div class="compare-field-value">${escapeHtml(row.values[0] ?? 'N/A')}</div>
              <div class="compare-field-value">${escapeHtml(row.values[1] ?? 'N/A')}</div>
            </div>
          </div>
        `)
        .join('');

      const firstItem = items[0] ?? {
        name: 'Product A',
        priceText: 'N/A',
        url: '#',
        images: [],
      };
      const secondItem = items[1] ?? {
        name: 'Product B',
        priceText: 'N/A',
        url: '#',
        images: [],
      };

      content.innerHTML = `
        <div class="compare-fields">
          <div class="compare-field-row compare-product-row">
            <div class="compare-field-label">Product</div>
            <div class="compare-field-values compare-product-values">
              <div class="compare-field-value">
                ${renderProductCell(firstItem)}
              </div>
              <div class="compare-field-value">
                ${renderProductCell(secondItem)}
              </div>
            </div>
          </div>
          ${rowsHtml}
        </div>
      `;

      initCompareImageCarousels(content);
    },
  };
}
