import { apiJson, escapeHtml } from './shared.js';

const root = document.querySelector('[data-product-form-root][data-mode="edit"]');

if (root) {
  const alertBox = root.querySelector('[data-product-alert]');
  const saveButton = root.querySelector('[data-save-product]');
  const deleteButton = root.querySelector('[data-delete-product]');
  const categorySelect = root.querySelector('#category_id');
  const brandSelect = root.querySelector('#brand_id');
  const imagesList = root.querySelector('[data-images-list]');
  const imagesEmpty = root.querySelector('[data-images-empty]');
  const variantsList = root.querySelector('[data-variants-list]');
  const variantsEmpty = root.querySelector('[data-variants-empty]');
  const hasVariantsInput = root.querySelector('#has_variants');
  const productId = Number(root.dataset.productId);

  let productState = null;

  init().catch(error => {
    showAlert(error.message || 'Could not load the product editor.');
  });

  async function init() {
    await Promise.all([loadLookups(), loadProduct()]);

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
    deleteButton?.addEventListener('click', destroyProduct);
  }

  async function loadLookups() {
    const [categoriesResponse, brandsResponse] = await Promise.all([
      apiJson('/api/categories'),
      apiJson('/api/brands?per_page=100'),
    ]);

    renderCategoryOptions(categorySelect, categoriesResponse.items || [], root.dataset.initialCategoryId || '');
    renderBrandOptions(brandSelect, brandsResponse.data || [], root.dataset.initialBrandId || '');
  }

  async function loadProduct() {
    const response = await apiJson(`/api/products/${productId}`);
    productState = response.product || response;
    hydrateFromProduct(productState);
  }

  function hydrateFromProduct(product) {
    root.querySelector('#name').value = product.name || '';
    root.querySelector('#slug').value = product.slug || '';
    root.querySelector('#sku').value = product.sku || '';
    root.querySelector('#status').value = product.status?.value || product.status || 'active';
    root.querySelector('#summary').value = product.summary || '';
    root.querySelector('#description').value = product.description || '';
    root.querySelector('#price').value = product.price ?? '';
    root.querySelector('#stock_quantity').value = product.stock_quantity ?? 0;
    root.querySelector('#low_stock_threshold').value = product.low_stock_threshold ?? 5;
    hasVariantsInput.checked = Boolean(product.has_variants || (product.variants || []).length);

    renderImageRows(product.images || []);
    renderVariantRows(product.variants || []);

    syncEmptyState(imagesList, imagesEmpty);
    syncEmptyState(variantsList, variantsEmpty);
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

  function renderImageRows(images) {
    if (!imagesList) return;
    imagesList.innerHTML = '';
    images.forEach(image => addImageRow(image));
    if (!images.length) addImageRow();
  }

  function renderVariantRows(variants) {
    if (!variantsList) return;
    variantsList.innerHTML = '';
    variants.forEach(variant => addVariantRow(variant));
    if (!variants.length) addVariantRow();
  }

  function addImageRow(initial = {}) {
    if (!imagesList) return;

    const row = document.createElement('div');
    row.className = 'product-repeat-item';
    row.dataset.imageRow = '1';
    row.dataset.imageId = initial.id ? String(initial.id) : '';
    row.innerHTML = `
      <div class="product-repeat-item-head">
        <strong>${initial.id ? `Image #${escapeHtml(initial.id)}` : 'Image'}</strong>
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
          <img data-image-preview class="${initial.url ? '' : 'd-none'}" alt="Product image preview" src="${escapeHtml(initial.url || '')}">
        </div>
      </div>
    `;

    const urlInput = row.querySelector('[data-image-url]');
    const preview = row.querySelector('[data-image-preview]');
    row.querySelector('[data-remove-image-row]')?.addEventListener('click', () => {
      row.remove();
      syncEmptyState(imagesList, imagesEmpty);
    });

    urlInput.addEventListener('input', () => {
      const value = urlInput.value.trim();
      preview.src = value;
      preview.classList.toggle('d-none', !value);
    });

    imagesList.appendChild(row);
  }

  function addVariantRow(initial = {}) {
    if (!variantsList) return;

    const row = document.createElement('div');
    row.className = 'product-repeat-item';
    row.dataset.variantRow = '1';
    row.dataset.variantId = initial.id ? String(initial.id) : '';
    row.innerHTML = `
      <div class="product-repeat-item-head">
        <strong>${initial.id ? `Variant #${escapeHtml(initial.id)}` : 'Variant'}</strong>
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
      </div>
    `;

    row.querySelector('[data-remove-variant-row]')?.addEventListener('click', () => {
      if (row.dataset.variantId) {
        row.dataset.removed = '1';
        row.classList.add('is-removed');
        row.querySelectorAll('input, button:not([data-remove-variant-row])').forEach(el => {
          el.disabled = true;
        });
        row.querySelector('[data-remove-variant-row]').textContent = 'Marked for removal';
      } else {
        row.remove();
      }
      syncEmptyState(variantsList, variantsEmpty);
    });

    variantsList.appendChild(row);
  }

  function syncEmptyState(list, emptyState) {
    if (!list || !emptyState) return;
    const activeRows = [...list.querySelectorAll('[data-image-row], [data-variant-row]')].filter(row => row.dataset.removed !== '1');
    emptyState.classList.toggle('d-none', activeRows.length > 0);
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
    return [...imagesList.querySelectorAll('[data-image-row]')]
      .filter(row => row.dataset.removed !== '1')
      .map((row, index) => {
        const url = row.querySelector('[data-image-url]')?.value.trim() || '';
        const altText = row.querySelector('[data-image-alt]')?.value.trim() || null;
        const sortOrder = row.querySelector('[data-image-sort]')?.value;

        return {
          url,
          alt_text: altText,
          sort_order: sortOrder === '' ? index : Number(sortOrder),
        };
      })
      .filter(image => image.url !== '');
  }

  function collectVariants() {
    return [...variantsList.querySelectorAll('[data-variant-row]')]
      .map(row => {
        const variantId = row.dataset.variantId ? Number(row.dataset.variantId) : null;
        const title = row.querySelector('[data-variant-title]')?.value.trim() || null;
        const sku = row.querySelector('[data-variant-sku]')?.value.trim() || '';
        const price = row.querySelector('[data-variant-price]')?.value;
        const stockQuantity = row.querySelector('[data-variant-stock]')?.value;
        const lowStockThreshold = row.querySelector('[data-variant-threshold]')?.value;

        return {
          variant_id: variantId,
          title,
          sku,
          price: price === '' ? null : Number(price),
          stock_quantity: stockQuantity === '' ? 0 : Number(stockQuantity),
          low_stock_threshold: lowStockThreshold === '' ? 5 : Number(lowStockThreshold),
          removed: row.dataset.removed === '1',
        };
      })
      .filter(variant => variant.removed === false && (variant.sku || variant.title || variant.price !== null));
  }

  async function submit() {
    clearAlert();
    saveButton.disabled = true;
    saveButton.textContent = 'Saving...';

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

      const response = await apiJson(`/api/products/${productId}`, {
        method: 'PATCH',
        body: JSON.stringify(basePayload),
      });

      const updatedId = response.id ?? response.product?.id ?? productId;

      await syncImages(updatedId, images);
      const variantWarnings = await syncVariants(updatedId, variants);

      await loadProduct();
      if (variantWarnings.length) {
        showAlert(`Product updated, but some variants could not be removed: ${variantWarnings.join(' ')}`, 'warning');
      } else {
        showAlert('Product updated successfully.', 'success');
      }
    } catch (error) {
      showAlert(error.message || 'Could not update the product.');
    } finally {
      saveButton.disabled = false;
      saveButton.textContent = 'Update product';
    }
  }

  async function syncImages(id, images) {
    const existingImages = await apiJson(`/api/products/${id}/images`);
    for (const existing of existingImages.items || []) {
      await apiJson(`/api/products/${id}/images/${existing.id}`, {
        method: 'DELETE',
      });
    }

    for (const image of images) {
      await apiJson(`/api/products/${id}/images`, {
        method: 'POST',
        body: JSON.stringify(image),
      });
    }
  }

  async function syncVariants(id, variants) {
    const existingRows = productState?.variants || [];
    const existingIds = new Set(existingRows.map(variant => variant.id));
    const remainingIds = new Set();
    const warnings = [];

    for (const variant of variants) {
      if (variant.variant_id) {
        remainingIds.add(variant.variant_id);
      }

      await apiJson(`/api/products/${id}/variants`, {
        method: 'POST',
        body: JSON.stringify({
          variant_id: variant.variant_id || null,
          sku: variant.sku,
          title: variant.title,
          price: variant.price,
          stock_quantity: variant.stock_quantity,
          low_stock_threshold: variant.low_stock_threshold,
        }),
      });
    }

    const toDelete = [...existingIds].filter(existingId => !remainingIds.has(existingId));
    for (const variantId of toDelete) {
      try {
        await apiJson(`/api/products/${id}/variants/${variantId}`, {
          method: 'DELETE',
        });
      } catch (error) {
        warnings.push(`Variant #${variantId}: ${error.message}`);
      }
    }

    return warnings;
  }

  async function destroyProduct() {
    if (!confirm('Delete this product?')) return;

    try {
      await apiJson(`/api/products/${productId}`, {
        method: 'DELETE',
      });
      window.location.href = '/admin/products';
    } catch (error) {
      showAlert(error.message || 'Could not delete the product.');
    }
  }
}
