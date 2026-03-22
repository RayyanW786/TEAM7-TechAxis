import { apiJson, parsePositiveInt, setAlert, clearAlert, formatMoney, escapeHtml } from './shared/api.js';

const itemsContainer = document.getElementById('itemsContainer');
const totalText = document.getElementById('totalText');
const emptyState = document.getElementById('emptyState');
const messageBox = document.getElementById('messageBox');

function calculateTotal(cart) {
  return (cart.items || []).reduce((sum, item) => {
    const unitPrice = Number(item.unit_price || 0);
    const quantity = Number(item.quantity || 0);
    return sum + unitPrice * quantity;
  }, 0);
}

function inventoryStatusForItem(item) {
  const source = item.variant ?? item.product ?? {};
  const stock = Number(source.stock_quantity ?? 0);
  const threshold = Number(source.low_stock_threshold ?? 0);

  if (stock <= 0) {
    return { state: 'out_of_stock', label: 'Out of stock' };
  }

  if (stock <= threshold) {
    return { state: 'low_stock', label: 'Low stock' };
  }

  return { state: 'in_stock', label: 'In stock' };
}

function renderCart(cart) {
  clearAlert(messageBox);
  itemsContainer.innerHTML = '';

  const items = cart.items || [];
  emptyState.classList.toggle('d-none', items.length !== 0);
  const hasUnavailableItems = items.some(item => inventoryStatusForItem(item).state === 'out_of_stock');

  if (hasUnavailableItems) {
    setAlert(messageBox, 'Some items in your cart are currently out of stock. You can remove them or adjust quantities before checkout.', 'warning');
  }

  for (const item of items) {
    const productName = item.product?.name ?? 'Product';
    const variantTitle = item.variant?.title ? ` - ${item.variant.title}` : '';
    const unitPrice = Number(item.unit_price || 0);
    const quantityValue = Number(item.quantity || 1);
    const lineTotal = unitPrice * quantityValue;
    const inventoryStatus = inventoryStatusForItem(item);

    const row = document.createElement('div');
    row.className = 'list-group-item cart-line-item';

    row.innerHTML = `
      <div class="cart-line-shell">
        <div class="cart-line-meta">
          <div class="fw-semibold">${escapeHtml(productName)}${escapeHtml(variantTitle)}</div>
          <div class="text-muted small">${escapeHtml(formatMoney(unitPrice))} each</div>
          <div class="small mt-1 cart-stock-indicator cart-stock-indicator--${escapeHtml(inventoryStatus.state)}">${escapeHtml(inventoryStatus.label)}</div>
        </div>

        <div class="cart-line-actions">
          <div class="input-group input-group-sm cart-qty-group">
            <input class="form-control quantityInput" type="number" min="1" step="1" value="${quantityValue}">
            <button class="btn btn-outline-primary updateButton" type="button">Update</button>
            <button class="btn btn-outline-danger removeButton" type="button">Remove</button>
          </div>
          <div class="text-muted small mt-1">Line total: <span class="fw-semibold">${escapeHtml(formatMoney(lineTotal))}</span></div>
        </div>
      </div>
    `;

    const quantityInput = row.querySelector('.quantityInput');
    const updateButton = row.querySelector('.updateButton');
    const removeButton = row.querySelector('.removeButton');

    updateButton.addEventListener('click', async () => {
      clearAlert(messageBox);

      const quantity = parsePositiveInt(quantityInput.value);
      if (!quantity) {
        setAlert(messageBox, 'Quantity must be a whole number of at least 1.', 'danger');
        quantityInput.focus();
        return;
      }

      try {
        const updatedCart = await apiJson(`/api/cart/items/${item.id}`, {
          method: 'PATCH',
          body: JSON.stringify({ quantity }),
        });
        renderCart(updatedCart);
      } catch (error) {
        setAlert(messageBox, error.message || 'Could not update quantity.', 'danger');
      }
    });

    removeButton.addEventListener('click', async () => {
      clearAlert(messageBox);

      try {
        const updatedCart = await apiJson('/api/cart/items', {
          method: 'DELETE',
          body: JSON.stringify({
            product_id: item.product_id,
            variant_id: item.variant_id,
          }),
        });
        renderCart(updatedCart);
      } catch (error) {
        setAlert(messageBox, error.message || 'Could not remove item.', 'danger');
      }
    });

    itemsContainer.appendChild(row);
  }

  totalText.textContent = formatMoney(calculateTotal(cart));
}

async function init() {
  try {
    const cart = await apiJson('/api/cart');
    renderCart(cart);
  } catch (error) {
    setAlert(messageBox, error.message || 'Could not load cart.', 'danger');
  }
}

init();
