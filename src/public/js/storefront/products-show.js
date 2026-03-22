import { apiJson, parsePositiveInt, setAlert, clearAlert, formatMoney } from './shared/api.js';

const addToCartButton = document.getElementById('addToCartButton');
const quantityInput = document.getElementById('quantityInput');
const messageBox = document.getElementById('messageBox');
const variantSelect = document.getElementById('variantSelect');
const priceText = document.getElementById('priceText');
const stockBadge = document.querySelector('[data-product-stock-badge]');
const stockMessage = document.querySelector('[data-product-stock-message]');
const mainImage = document.getElementById('mainImage');
const mainCarouselEl = document.getElementById('productCarousel');
const modalCarouselEl = document.getElementById('productCarouselModal');
const imageModalEl = document.getElementById('imageModal');

if (mainCarouselEl && modalCarouselEl && imageModalEl) {
  imageModalEl.addEventListener('shown.bs.modal', () => {
    const mainActiveIndex = [...mainCarouselEl.querySelectorAll('.carousel-item')]
      .findIndex(item => item.classList.contains('active'));

    if (mainActiveIndex >= 0) {
      // Bootstrap Carousel API
      const modalCarousel = bootstrap.Carousel.getOrCreateInstance(modalCarouselEl);
      modalCarousel.to(mainActiveIndex);
    }
  });
}


function showSuccess(message) {
  setAlert(messageBox, message, 'success');
}
function showError(message) {
  setAlert(messageBox, message, 'danger');
}

function stockCopyFromState(state) {
  switch (state) {
    case 'out_of_stock':
      return {
        label: 'Out of stock',
        message: 'This item is currently unavailable. You can still browse the details and check back soon.',
      };
    case 'low_stock':
      return {
        label: 'Low stock',
        message: 'Stock is running low, so it may sell out soon.',
      };
    default:
      return {
        label: 'In stock',
        message: 'Ready to ship while current stock lasts.',
      };
  }
}

function setStockVisualState(state) {
  const safeState = ['in_stock', 'low_stock', 'out_of_stock'].includes(state) ? state : 'in_stock';

  if (stockBadge) {
    stockBadge.textContent = stockCopyFromState(safeState).label;
    stockBadge.classList.remove('stock-badge-in', 'stock-badge-low', 'stock-badge-out');
    stockBadge.classList.add(
      safeState === 'out_of_stock'
        ? 'stock-badge-out'
        : safeState === 'low_stock'
          ? 'stock-badge-low'
          : 'stock-badge-in'
    );
  }

  if (stockMessage) {
    stockMessage.textContent = stockCopyFromState(safeState).message;
  }

  if (addToCartButton) {
    addToCartButton.dataset.stockState = safeState;
    addToCartButton.disabled = safeState === 'out_of_stock';
    addToCartButton.textContent = safeState === 'out_of_stock' ? 'Out of stock' : 'Add to cart';
  }
}

document.querySelectorAll('[data-thumb-url]').forEach((button) => {
  button.addEventListener('click', () => {
    if (mainImage) mainImage.src = button.dataset.thumbUrl;
  });
});

if (variantSelect && priceText) {
  variantSelect.addEventListener('change', () => {
    const option = variantSelect.selectedOptions[0];
    const price = option?.dataset?.price;
    if (price) priceText.textContent = formatMoney(price);

    if (!option?.value) {
      setStockVisualState(addToCartButton.dataset.initialStockState || addToCartButton.dataset.stockState || 'in_stock');
      return;
    }

    const stockQuantity = Number(option.dataset.stockQuantity || 0);
    const lowStockThreshold = Number(option.dataset.lowStockThreshold || 0);
    const nextState = stockQuantity <= 0
      ? 'out_of_stock'
      : stockQuantity <= lowStockThreshold
        ? 'low_stock'
        : 'in_stock';

    setStockVisualState(nextState);
  });
}

if (addToCartButton) {
  setStockVisualState(addToCartButton.dataset.initialStockState || addToCartButton.dataset.stockState || 'in_stock');
}

if (addToCartButton) {
  addToCartButton.addEventListener('click', async () => {
    clearAlert(messageBox);

    const productId = Number(addToCartButton.dataset.productId);
    const variantRequired = addToCartButton.dataset.requiresVariant === '1';

    const quantity = parsePositiveInt(quantityInput.value);
    if (!quantity) {
      showError('Quantity must be a whole number of at least 1.');
      quantityInput.focus();
      return;
    }

    const variantId = variantSelect?.value ? Number(variantSelect.value) : null;
    if (variantRequired && !variantId) {
      showError('Please select a variant before adding to cart.');
      variantSelect?.focus();
      return;
    }

    if (addToCartButton.dataset.stockState === 'out_of_stock') {
      showError('This item is currently out of stock.');
      return;
    }

    addToCartButton.disabled = true;
    addToCartButton.textContent = 'Adding...';

    try {
      await apiJson('/api/cart/items', {
        method: 'POST',
        body: JSON.stringify({
          product_id: productId,
          variant_id: variantId,
          quantity,
        }),
      });

      showSuccess('Added to cart.');
      addToCartButton.textContent = 'Added!';
    } catch (error) {
      showError(error.message || 'Could not add to cart.');
      addToCartButton.textContent = 'Add to cart';
    } finally {
      addToCartButton.disabled = false;
    }
  });
}
