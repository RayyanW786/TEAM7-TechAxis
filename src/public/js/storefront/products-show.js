import { apiJson, parsePositiveInt, setAlert, clearAlert, formatMoney } from './shared/api.js';

const addToCartButton = document.getElementById('addToCartButton');
const quantityInput = document.getElementById('quantityInput');
const messageBox = document.getElementById('messageBox');
const variantSelect = document.getElementById('variantSelect');
const priceText = document.getElementById('priceText');
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
  });
}

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
