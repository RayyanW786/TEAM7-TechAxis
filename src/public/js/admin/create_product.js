import { apiJson, escapeHtml } from './shared.js';

const root = document.querySelector('[data-product-form-root][data-mode="create"]');

if (root) {
  const alertBox = root.querySelector('[data-product-alert]');
  const saveButton = root.querySelector('[data-save-product]');
  const categorySelect = root.querySelector('#category_id');
  const brandSelect = root.querySelector('#brand_id');
  const imagesList = root.querySelector('[data-images-list]');
  const imagesEmpty = root.querySelector('[data-images-empty]');
  const variantsList = root.querySelector('[data-variants-list]');
  const variantsEmpty = root.querySelector('[data-variants-empty]');
  const hasVariantsInput = root.querySelector('#has_variants');

  init().catch(error => {
    showAlert(error.message || 'Could not load the product editor.');
  });

  async function init() {
    await loadLookups();
    addImageRow();
    syncEmptyState(imagesList, imagesEmpty);
    syncEmptyState(variantsList, variantsEmpty);

    root.querySelector('[data-add-image]')?.addEventListener('click', () => {
      addImageRow();
      syncEmptyState(imagesList, imagesEmpty);
    });

    root.querySelector('[data-add-variant]')?.addEventListener('click', () => {
      addVariantRow();
      syncEmptyState(variantsList, variantsEmpty);
    });

    hasVariantsInput?.addEventListener('change', () => {
      if (hasVariantsInput.checked && !variantsList.querySelector('[data-variant-row]')) {
        addVariantRow();
        syncEmptyState(variantsList, variantsEmpty);
      }
    });

    saveButton?.addEventListener('click', submit);
  }

  async function loadLookups() {
    const [categoriesResponse, brandsResponse] = await Promise.all([
      apiJson('/api/categories'),
      apiJson('/api/brands?per_page=100'),
    ]);

    renderCategoryOptions(categorySelect, categoriesResponse.items || [], root.dataset.initialCategoryId || '');
    renderBrandOptions(brandSelect, brandsResponse.data || [], root.dataset.initialBrandId || '');
  }

  function renderCategoryOptions(select, categories, selectedValue = '') {
    if (!select) return;

    select.innerHTML = [
      '<option value="">Select category</option>',
      ...categories.map(category => {
        const value = category.category_id ?? category.id;
        return `<option value="${escapeHtml(value)}" ${String(value) === String(selectedValue) ? 'selected' : ''}>${escapeHtml(category.name)}</option>`;
      }),
    ].join('');
  }

  function renderBrandOptions(select, brands, selectedValue = '') {
    if (!select) return;

    select.innerHTML = [
      '<option value="">No brand</option>',
      ...brands.map(brand => {
        const value = brand.id;
        return `<option value="${escapeHtml(value)}" ${String(value) === String(selectedValue) ? 'selected' : ''}>${escapeHtml(brand.name)}</option>`;
      }),
    ].join('');
  }

  function addImageRow(initial = {}) {
    if (!imagesList) return;

    const row = document.createElement('div');
    row.className = 'product-repeat-item';
    row.dataset.imageRow = '1';
    row.innerHTML = `
      <div class="product-repeat-item-head">
        <strong>Image</strong>
        <button type="button" class="product-small-button" data-remove-image-row>Remove</button>
      </div>
      <div class="product-repeat-grid">
        <div class="product-field">
          <label>Image URL</label>
          <input type="text" data-image-url placeholder="https://example.com/image.jpg" value="${escapeHtml(initial.url || '')}">
        </div>
        <div class="product-field">
          <label>Alt text</label>
          <input type="text" data-image-alt placeholder="Descriptive alt text" value="${escapeHtml(initial.alt_text || '')}">
        </div>
        <div class="product-field">
          <label>Sort order</label>
          <input type="number" min="0" step="1" data-image-sort value="${escapeHtml(initial.sort_order ?? 0)}">
        </div>
        <div class="product-image-preview">
          <label class="product-editor-note">Live preview</label>
          <img data-image-preview class="d-none" alt="Product image preview">
        </div>
      </div>
    `;

    const urlInput = row.querySelector('[data-image-url]');
    const preview = row.querySelector('[data-image-preview]');
    const removeButton = row.querySelector('[data-remove-image-row]');

    const syncPreview = () => {
      const value = urlInput.value.trim();
      preview.src = value;
      preview.classList.toggle('d-none', !value);
    };

    urlInput.addEventListener('input', syncPreview);
    removeButton?.addEventListener('click', () => {
      row.remove();
      syncEmptyState(imagesList, imagesEmpty);
    });

    imagesList.appendChild(row);
    syncPreview();
  }

  function addVariantRow(initial = {}) {
    if (!variantsList) return;

    const row = document.createElement('div');
    row.className = 'product-repeat-item';
    row.dataset.variantRow = '1';
    row.innerHTML = `
      <div class="product-repeat-item-head">
        <strong>Variant</strong>
        <button type="button" class="product-small-button" data-remove-variant-row>Remove</button>
      </div>
      <div class="product-repeat-grid product-repeat-grid--variants">
        <div class="product-field">
          <label>Title</label>
          <input type="text" data-variant-title placeholder="Red / 512GB / Large" value="${escapeHtml(initial.title || '')}">
        </div>
        <div class="product-field">
          <label>SKU</label>
          <input type="text" data-variant-sku placeholder="SKU-RED-512" value="${escapeHtml(initial.sku || '')}">
        </div>
        <div class="product-field">
          <label>Price</label>
          <input type="number" min="0" step="0.01" data-variant-price placeholder="0.00" value="${escapeHtml(initial.price ?? '')}">
        </div>
        <div class="product-field">
          <label>Stock</label>
          <input type="number" min="0" step="1" data-variant-stock placeholder="0" value="${escapeHtml(initial.stock_quantity ?? 0)}">
        </div>
        <div class="product-field">
          <label>Low stock threshold</label>
          <input type="number" min="0" step="1" data-variant-threshold placeholder="5" value="${escapeHtml(initial.low_stock_threshold ?? 5)}">
        </div>
        <input type="hidden" data-variant-id value="">
      </div>
    `;

    row.querySelector('[data-remove-variant-row]')?.addEventListener('click', () => {
      row.remove();
      syncEmptyState(variantsList, variantsEmpty);
    });

    variantsList.appendChild(row);
  }

  function syncEmptyState(list, emptyState) {
    if (!list || !emptyState) return;
    emptyState.classList.toggle('d-none', list.querySelectorAll('[data-image-row], [data-variant-row]').length > 0);
  }

  function showAlert(message, type = 'danger') {
    if (!alertBox) return;

    alertBox.innerHTML = `
      <div class="alert alert-${type}" role="alert">
        ${escapeHtml(message)}
      </div>
    `;
  }

  function clearAlert() {
    if (!alertBox) return;
    alertBox.innerHTML = '';
  }

  function collectBasePayload() {
    const name = root.querySelector('#name')?.value.trim() || '';
    const slug = root.querySelector('#slug')?.value.trim() || '';
    const sku = root.querySelector('#sku')?.value.trim() || null;
    const status = root.querySelector('#status')?.value || 'active';
    const categoryId = Number(root.querySelector('#category_id')?.value || 0);
    const brandId = root.querySelector('#brand_id')?.value ? Number(root.querySelector('#brand_id').value) : null;
    const summary = root.querySelector('#summary')?.value.trim() || null;
    const description = root.querySelector('#description')?.value.trim() || null;
    const price = root.querySelector('#price')?.value;
    const stockQuantity = root.querySelector('#stock_quantity')?.value;
    const lowStockThreshold = root.querySelector('#low_stock_threshold')?.value;
    const hasVariants = !!root.querySelector('#has_variants')?.checked;

    if (!name || !slug || !categoryId || !price) {
      throw new Error('Name, slug, category, and price are required.');
    }

    return {
      category_id: categoryId,
      brand_id: brandId,
      name,
      slug,
      sku,
      status,
      summary,
      description,
      price: Number(price),
      stock_quantity: stockQuantity === '' ? 0 : Number(stockQuantity),
      has_variants: hasVariants,
      low_stock_threshold: lowStockThreshold === '' ? 5 : Number(lowStockThreshold),
    };
  }

  function collectImages() {
    return [...imagesList.querySelectorAll('[data-image-row]')].map((row, index) => {
      const url = row.querySelector('[data-image-url]')?.value.trim() || '';
      const altText = row.querySelector('[data-image-alt]')?.value.trim() || null;
      const sortOrder = row.querySelector('[data-image-sort]')?.value;

      return {
        url,
        alt_text: altText,
        sort_order: sortOrder === '' ? index : Number(sortOrder),
      };
    }).filter(image => image.url !== '');
  }

  function collectVariants() {
    return [...variantsList.querySelectorAll('[data-variant-row]')].map(row => {
      const variantId = row.querySelector('[data-variant-id]')?.value;
      const title = row.querySelector('[data-variant-title]')?.value.trim() || null;
      const sku = row.querySelector('[data-variant-sku]')?.value.trim() || '';
      const price = row.querySelector('[data-variant-price]')?.value;
      const stockQuantity = row.querySelector('[data-variant-stock]')?.value;
      const lowStockThreshold = row.querySelector('[data-variant-threshold]')?.value;

      return {
        variant_id: variantId ? Number(variantId) : null,
        title,
        sku,
        price: price === '' ? null : Number(price),
        stock_quantity: stockQuantity === '' ? 0 : Number(stockQuantity),
        low_stock_threshold: lowStockThreshold === '' ? 5 : Number(lowStockThreshold),
      };
    }).filter(variant => variant.sku || variant.title || variant.price !== null);
  }

  async function submit() {
    clearAlert();
    saveButton.disabled = true;
    saveButton.textContent = 'Creating...';

    try {
      const basePayload = collectBasePayload();
      const images = collectImages();
      const variants = collectVariants();

      if (basePayload.has_variants && variants.length === 0) {
        throw new Error('Add at least one variant or turn off the variant option.');
      }

      if (variants.some(variant => variant.sku && variant.price === null)) {
        throw new Error('Each variant must include a SKU and a price.');
      }

      const response = await apiJson('/api/products', {
        method: 'POST',
        body: JSON.stringify(basePayload),
      });

      const productId = response.id ?? response.product?.id;
      if (!productId) {
        throw new Error('The product was created, but its id could not be resolved.');
      }

      await syncImages(productId, images);
      await syncVariants(productId, variants);

      window.location.href = `/admin/products/${productId}`;
    } catch (error) {
      showAlert(error.message || 'Could not create the product.');
    } finally {
      saveButton.disabled = false;
      saveButton.textContent = 'Create product';
    }
  }

  async function syncImages(productId, images) {
    for (const image of images) {
      await apiJson(`/api/products/${productId}/images`, {
        method: 'POST',
        body: JSON.stringify(image),
      });
    }
  }

  async function syncVariants(productId, variants) {
    if (!variants.length) return;

    for (const variant of variants) {
      await apiJson(`/api/products/${productId}/variants`, {
        method: 'POST',
        body: JSON.stringify({
          sku: variant.sku,
          title: variant.title,
          price: variant.price,
          stock_quantity: variant.stock_quantity,
          low_stock_threshold: variant.low_stock_threshold,
        }),
      });
    }
  }
}
