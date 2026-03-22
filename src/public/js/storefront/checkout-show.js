import { apiJson, setAlert, clearAlert, formatMoney, escapeHtml } from './shared/api.js';

const messageBox = document.getElementById('messageBox');

const addressSelectBlock = document.getElementById('addressSelectBlock');
const addressFormBlock = document.getElementById('addressFormBlock');

const shippingSelect = document.getElementById('shippingSelect');
const billingSelect = document.getElementById('billingSelect');
const billingSameCheckbox = document.getElementById('billingSameCheckbox');

const discountCodeInput = document.getElementById('discountCodeInput');
const applyDiscountButton = document.getElementById('applyDiscountButton');
const removeDiscountButton = document.getElementById('removeDiscountButton');
const discountMessage = document.getElementById('discountMessage');
const notesInput = document.getElementById('notesInput');

const summaryContainer = document.getElementById('summaryContainer');
const checkoutStockWarnings = document.getElementById('checkoutStockWarnings');
const subtotalText = document.getElementById('subtotalText');
const discountText = document.getElementById('discountText');
const totalText = document.getElementById('totalText');

const placeOrderButton = document.getElementById('placeOrderButton');
const saveAddressButton = document.getElementById('saveAddressButton');

let currentDiscountPreview = null;
let currentCart = null;

function calcTotal(cart) {
  return (cart.items || []).reduce((sum, item) => sum + Number(item.unit_price || 0) * Number(item.quantity || 0), 0);
}

function inventoryStatusForItem(item) {
  const source = item.variant ?? item.product ?? {};
  const stock = Number(source.stock_quantity ?? 0);
  const threshold = Number(source.low_stock_threshold ?? 0);

  if (stock <= 0) return { state: 'out_of_stock', label: 'Out of stock' };
  if (stock <= threshold) return { state: 'low_stock', label: 'Low stock' };
  return { state: 'in_stock', label: 'In stock' };
}

function renderDiscountPreview(cart) {
  const subtotal = currentDiscountPreview?.subtotal_amount ?? calcTotal(cart);
  const discount = currentDiscountPreview?.discount_total ?? 0;
  const total = currentDiscountPreview?.total_amount ?? subtotal;

  subtotalText.textContent = formatMoney(subtotal);
  discountText.textContent = discount > 0 ? `-${formatMoney(discount)}` : formatMoney(0);
  totalText.textContent = formatMoney(total);

  removeDiscountButton.classList.toggle('d-none', !currentDiscountPreview?.applied_code);
}

async function previewDiscount(cart) {
  const code = discountCodeInput.value.trim();

  if (!code) {
    currentDiscountPreview = null;
    discountMessage.textContent = 'Apply a valid code to preview the discount before placing your order.';
    renderDiscountPreview(cart);
    return;
  }

  applyDiscountButton.disabled = true;
  applyDiscountButton.textContent = 'Applying...';

  try {
    currentDiscountPreview = await apiJson('/api/cart/discount-preview', {
      method: 'POST',
      body: JSON.stringify({ discount_code: code }),
    });

    discountMessage.textContent = currentDiscountPreview.discount_total > 0
      ? `Discount code ${currentDiscountPreview.applied_code} applied successfully.`
      : 'This code is valid but does not change the current total.';
  } catch (error) {
    currentDiscountPreview = null;
    discountMessage.textContent = error.message || 'Discount code could not be applied.';
    setAlert(messageBox, discountMessage.textContent, 'warning');
  } finally {
    applyDiscountButton.disabled = false;
    applyDiscountButton.textContent = 'Apply';
    renderDiscountPreview(cart);
  }
}

function formatAddress(address) {
  const parts = [];
  if (address.label) parts.push(`[${address.label}]`);
  if (address.line1) parts.push(address.line1);
  if (address.city) parts.push(address.city);
  if (address.postal_code) parts.push(address.postal_code);
  return parts.join(' - ');
}

function syncBilling() {
  billingSelect.disabled = billingSameCheckbox.checked;
  if (billingSameCheckbox.checked) billingSelect.value = shippingSelect.value;
}

function renderAddresses(addresses) {
  shippingSelect.innerHTML = '';
  billingSelect.innerHTML = '';

  if (!addresses.length) {
    addressSelectBlock.classList.add('d-none');
    addressFormBlock.classList.remove('d-none');
    return;
  }

  addressFormBlock.classList.add('d-none');
  addressSelectBlock.classList.remove('d-none');

  for (const address of addresses) {
    const label = formatAddress(address);

    const shippingOption = document.createElement('option');
    shippingOption.value = address.id;
    shippingOption.textContent = label;
    shippingSelect.appendChild(shippingOption);

    const billingOption = document.createElement('option');
    billingOption.value = address.id;
    billingOption.textContent = label;
    billingSelect.appendChild(billingOption);
  }

  const defaultShipping = addresses.find(a => a.is_default_shipping);
  if (defaultShipping) shippingSelect.value = String(defaultShipping.id);

  billingSameCheckbox.checked = true;
  syncBilling();
}

function renderSummary(cart) {
  summaryContainer.innerHTML = '';
  const warnings = [];

  for (const item of cart.items || []) {
    const productName = item.product?.name ?? 'Product';
    const variantTitle = item.variant?.title ? ` - ${item.variant.title}` : '';
    const unitPrice = Number(item.unit_price || 0);
    const quantity = Number(item.quantity || 0);
    const line = unitPrice * quantity;
    const inventoryStatus = inventoryStatusForItem(item);

    const row = document.createElement('div');
    row.className = 'list-group-item checkout-summary-item';
    row.innerHTML = `
      <div class="checkout-summary-shell">
        <div>
          <div class="fw-semibold">${escapeHtml(productName)}${escapeHtml(variantTitle)}</div>
          <div class="text-muted small">${escapeHtml(String(quantity))} x ${escapeHtml(formatMoney(unitPrice))}</div>
          <div class="small mt-1 cart-stock-indicator cart-stock-indicator--${escapeHtml(inventoryStatus.state)}">${escapeHtml(inventoryStatus.label)}</div>
        </div>
        <div class="fw-semibold">${escapeHtml(formatMoney(line))}</div>
      </div>
    `;
    summaryContainer.appendChild(row);

    if (inventoryStatus.state === 'out_of_stock') {
      warnings.push(`${productName}${variantTitle} is currently out of stock.`);
    } else if (inventoryStatus.state === 'low_stock') {
      warnings.push(`${productName}${variantTitle} is running low on stock.`);
    }
  }

  checkoutStockWarnings.classList.toggle('d-none', warnings.length === 0);
  checkoutStockWarnings.textContent = warnings.join(' ');
  renderDiscountPreview(cart);
}

billingSameCheckbox.addEventListener('change', syncBilling);
shippingSelect.addEventListener('change', syncBilling);

saveAddressButton.addEventListener('click', async () => {
  clearAlert(messageBox);

  const payload = {
    label: document.getElementById('address_label').value.trim() || null,
    recipient_name: document.getElementById('address_recipient').value.trim() || null,
    line1: document.getElementById('address_line1').value.trim(),
    line2: document.getElementById('address_line2').value.trim() || null,
    city: document.getElementById('address_city').value.trim(),
    region: document.getElementById('address_region').value.trim() || null,
    postal_code: document.getElementById('address_postal').value.trim(),
    is_default_shipping: document.getElementById('address_default').checked,
  };

  if (!payload.line1 || !payload.city || !payload.postal_code) {
    setAlert(messageBox, 'Line 1, City and Postal code are required.', 'danger');
    return;
  }

  saveAddressButton.disabled = true;
  saveAddressButton.textContent = 'Saving...';

  try {
    await apiJson('/api/me/addresses', { method: 'POST', body: JSON.stringify(payload) });
    const addresses = await apiJson('/api/me/addresses');
    renderAddresses(addresses);
  } catch (error) {
    setAlert(messageBox, error.message || 'Could not save address.', 'danger');
  } finally {
    saveAddressButton.disabled = false;
    saveAddressButton.textContent = 'Save address';
  }
});

applyDiscountButton.addEventListener('click', async () => {
  clearAlert(messageBox);

  try {
    const cart = await apiJson('/api/cart');
    await previewDiscount(cart);
  } catch (error) {
    setAlert(messageBox, error.message || 'Could not validate discount code.', 'danger');
  }
});

removeDiscountButton.addEventListener('click', () => {
  currentDiscountPreview = null;
  discountCodeInput.value = '';
  discountMessage.textContent = 'Discount removed. You can apply another code at any time.';
  if (currentCart) {
    renderDiscountPreview(currentCart);
  }
  removeDiscountButton.classList.add('d-none');
});

placeOrderButton.addEventListener('click', async () => {
  clearAlert(messageBox);

  if (checkoutStockWarnings.textContent.toLowerCase().includes('out of stock')) {
    setAlert(messageBox, 'Please remove any out-of-stock items before placing the order.', 'warning');
    return;
  }

  const shippingId = Number(shippingSelect.value || 0);
  const billingId = Number(billingSameCheckbox.checked ? shippingSelect.value : billingSelect.value || 0);

  if (!shippingId || !billingId) {
    setAlert(messageBox, 'Select shipping and billing addresses.', 'danger');
    return;
  }

  placeOrderButton.disabled = true;
  placeOrderButton.textContent = 'Placing order...';

  try {
    const order = await apiJson('/api/cart/checkout', {
      method: 'POST',
      body: JSON.stringify({
        shipping_address_id: shippingId,
        billing_address_id: billingId,
        discount_code: discountCodeInput.value.trim() || null,
        notes: notesInput.value.trim() || null,
      }),
    });

    window.location.href = `/orders/${order.id}`;
  } catch (error) {
    setAlert(messageBox, error.message || 'Checkout failed.', 'danger');
  } finally {
    placeOrderButton.disabled = false;
    placeOrderButton.textContent = 'Place order';
  }
});

async function init() {
  clearAlert(messageBox);

  try {
    const [cart, addresses] = await Promise.all([
      apiJson('/api/cart'),
      apiJson('/api/me/addresses'),
    ]);
    currentCart = cart;

    if (!cart.items || cart.items.length === 0) {
      setAlert(messageBox, 'Your cart is empty.', 'warning');
    }

    renderSummary(cart);
    renderAddresses(addresses);
    if (discountCodeInput.value.trim()) {
      await previewDiscount(cart);
    } else {
      renderDiscountPreview(cart);
    }
  } catch (error) {
    setAlert(messageBox, error.message || 'Could not load checkout.', 'danger');
  }
}

init();
