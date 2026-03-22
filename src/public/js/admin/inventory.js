import { apiJson, badgeClassForStockState, escapeHtml, formatMoney } from './shared.js';

const root = document.querySelector('[data-admin-inventory-root]');

if (root) {
  const app = root.querySelector('[data-admin-inventory-app]');
  const state = {
    filters: { q: '' },
    summary: null,
    stock: [],
    priorities: [],
    alerts: [],
    transactions: [],
    selectedProductId: null,
    selectedVariantId: null,
    transactionMode: 'incoming',
    loading: true,
    savingTransaction: false,
    flash: null,
  };

  render();
  loadAll();

  async function loadAll() {
    state.loading = true;
    render();

    try {
      const [summary, stock, priorities, alerts, transactions] = await Promise.all([
        apiJson('/api/inventory/summary'),
        apiJson(`/api/inventory/stock?per_page=20${state.filters.q ? `&q=${encodeURIComponent(state.filters.q)}` : ''}`),
        apiJson('/api/inventory/restock-priorities'),
        apiJson('/api/inventory/alerts?per_page=8'),
        apiJson('/api/inventory/transactions?per_page=8'),
      ]);

      state.summary = summary;
      state.stock = stock.data || [];
      state.priorities = priorities.items || [];
      state.alerts = alerts.data || [];
      state.transactions = transactions.data || [];

      hydrateSelection();
    } catch (error) {
      state.flash = {
        type: 'error',
        message: error.message || 'Could not refresh the inventory dashboard.',
      };
    } finally {
      state.loading = false;
      state.savingTransaction = false;
      render();
    }
  }

  function hydrateSelection() {
    const selectedProduct = state.stock.find(product => product.id === state.selectedProductId) || state.stock[0] || null;
    state.selectedProductId = selectedProduct?.id ?? null;

    if (!selectedProduct) {
      state.selectedVariantId = null;
      return;
    }

    const variants = selectedProduct.variants || [];
    const selectedVariantExists = variants.some(variant => variant.id === state.selectedVariantId);

    if (!selectedVariantExists) {
      state.selectedVariantId = variants[0]?.id ?? null;
    }
  }

  function getSelectedProduct() {
    return state.stock.find(product => product.id === state.selectedProductId) || null;
  }

  function getSelectedVariant(product = getSelectedProduct()) {
    if (!product?.variants?.length) {
      return null;
    }

    return product.variants.find(variant => variant.id === state.selectedVariantId) || product.variants[0] || null;
  }

  function render() {
    app.innerHTML = `
      ${state.flash ? `<div class="admin-empty ${state.flash.type === 'error' ? 'admin-empty--warning' : 'admin-empty--success'}">${escapeHtml(state.flash.message)}</div>` : ''}
      ${renderSummary()}

      <div class="admin-two-column">
        <section class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Stock operations</h2>
              <p>Select a product, choose a variant if needed, then record an incoming delivery or a manual stock change.</p>
            </div>
          </div>
          <form class="admin-stack-form" data-inventory-adjustment-form>
            ${renderSelectedProductPanel()}

            <div class="admin-form-grid">
              <input type="number" name="quantity_change" step="1" placeholder="Quantity change" value="${state.transactionMode === 'incoming' ? '1' : ''}" required>
              <input type="text" name="reason" placeholder="Reason, e.g. incoming_stock" value="${state.transactionMode === 'incoming' ? 'incoming_stock' : ''}" required>
              <input type="text" name="order_id" inputmode="numeric" placeholder="Order ID (optional)">
            </div>
            <div class="admin-inline-form">
              <button type="submit" class="admin-submit" ${state.savingTransaction ? 'disabled' : ''}>${state.savingTransaction ? 'Saving transaction...' : 'Save inventory transaction'}</button>
            </div>
          </form>
        </section>

        <section class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Restock priority</h2>
              <p>Focus first on products with the highest urgency and strongest recent demand.</p>
            </div>
          </div>
          ${renderPriorities()}
        </section>
      </div>

      <section class="admin-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Stock browser</h2>
            <p>Click a product to load it into the operation panel. Variant items can then be selected directly from the card.</p>
          </div>
        </div>
        <form class="admin-filter-bar" data-inventory-stock-filters>
          <input type="text" name="q" value="${escapeHtml(state.filters.q)}" placeholder="Search product, slug, or SKU">
          <button type="submit">Search stock</button>
        </form>
        ${renderStockTable()}
      </section>

      <div class="admin-two-column">
        <section class="admin-panel">
          <div class="admin-panel-head"><div><h2>Recent alerts</h2><p>Friendly status changes and stock warnings.</p></div></div>
          ${renderAlerts()}
        </section>
        <section class="admin-panel">
          <div class="admin-panel-head"><div><h2>Recent transactions</h2><p>Incoming stock and manual adjustments recorded by staff.</p></div></div>
          ${renderTransactions()}
        </section>
      </div>
    `;

    root.querySelector('[data-inventory-adjustment-form]')?.addEventListener('submit', async event => {
      event.preventDefault();
      const form = event.currentTarget;
      const formData = new FormData(form);

      const selectedProduct = getSelectedProduct();
      const selectedVariant = getSelectedVariant(selectedProduct);

      if (!selectedProduct) {
        setPanelMessage('Select a product before recording stock changes.', 'warning');
        return;
      }

      try {
        state.savingTransaction = true;
        state.flash = null;
        render();

        await apiJson('/api/inventory/transactions', {
          method: 'POST',
          body: JSON.stringify({
            product_id: selectedProduct.id,
            variant_id: selectedVariant?.id || null,
            quantity_change: Number(formData.get('quantity_change')),
            reason: String(formData.get('reason') || '').trim(),
            order_id: formData.get('order_id') ? Number(formData.get('order_id')) : null,
          }),
        });

        form.reset();
        state.transactionMode = 'incoming';
        state.flash = {
          type: 'success',
          message: 'Inventory transaction saved. Stock, alerts, and recent transactions have been refreshed.',
        };
        applyTransactionLocally({
          productId: selectedProduct.id,
          variantId: selectedVariant?.id || null,
          quantityChange: Number(formData.get('quantity_change')),
          reason: String(formData.get('reason') || '').trim(),
          orderId: formData.get('order_id') ? Number(formData.get('order_id')) : null,
          productName: selectedProduct.name,
          variantTitle: selectedVariant?.title || selectedVariant?.sku || null,
        });
        render();
        await loadAll();
      } catch (error) {
        state.savingTransaction = false;
        state.flash = {
          type: 'error',
          message: error.message || 'Could not save the inventory transaction.',
        };
        render();
      }
    });

    root.querySelector('[data-inventory-stock-filters]')?.addEventListener('submit', async event => {
      event.preventDefault();
      state.filters.q = String(new FormData(event.currentTarget).get('q') || '').trim();
      await loadAll();
    });

    root.querySelectorAll('[data-select-product]').forEach(button => {
      button.addEventListener('click', () => {
        state.selectedProductId = Number(button.dataset.selectProduct);
        state.selectedVariantId = null;
        render();
      });
    });

    root.querySelectorAll('[data-select-variant]').forEach(button => {
      button.addEventListener('click', () => {
        state.selectedVariantId = Number(button.dataset.selectVariant);
        render();
      });
    });

    root.querySelectorAll('[data-transaction-mode]').forEach(button => {
      button.addEventListener('click', () => {
        state.transactionMode = button.dataset.transactionMode;
        render();
      });
    });
  }

  function renderSummary() {
    if (state.loading || !state.summary) {
      return '<div class="admin-empty">Loading inventory summary...</div>';
    }

    return `
      <div class="admin-shell-grid admin-shell-grid--cards">
        <div class="admin-stat-card"><span>Active products</span><strong>${state.summary.active_products}</strong></div>
        <div class="admin-stat-card"><span>Low stock</span><strong>${state.summary.low_stock_count}</strong></div>
        <div class="admin-stat-card"><span>Out of stock</span><strong>${state.summary.out_of_stock_count}</strong></div>
        <div class="admin-stat-card"><span>Inventory alerts</span><strong>${state.summary.alert_count}</strong></div>
      </div>
    `;
  }

  function renderPriorities() {
    if (!state.priorities.length) {
      return '<div class="admin-empty">No urgent restock priorities right now.</div>';
    }

    return `
      <div class="admin-list">
        ${state.priorities.map(item => `
          <div class="admin-list-item">
            <div class="admin-list-item-title">${escapeHtml(item.name)}</div>
            <div class="admin-list-item-meta">
              Current stock: ${item.current_stock} / Recent 30-day demand: ${item.recent_quantity}
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }

  function renderSelectedProductPanel() {
    const product = getSelectedProduct();
    if (!product) {
      return `
        <div class="admin-empty">
          Search for a product above, then click it from the stock browser to start recording transactions.
        </div>
      `;
    }

    const selectedVariant = getSelectedVariant(product);

    return `
      <div class="admin-selected-card">
        <div class="admin-selected-card__head">
          <div>
            <div class="admin-shell-kicker">Selected product</div>
            <h3 style="margin:0.55rem 0 0;font-size:1.1rem;">${escapeHtml(product.name)}</h3>
            <p style="margin:0.3rem 0 0;color:rgba(255,255,255,0.68);">${escapeHtml([product.brand, product.category].filter(Boolean).join(' / ') || product.slug)}</p>
          </div>
          <span class="${badgeClassForStockState(product.stock_state)}">${escapeHtml(product.stock_label)}</span>
        </div>

        <div class="admin-selected-card__stats">
          <span><strong>Product ID</strong>#${product.id}</span>
          <span><strong>Total stock</strong>${product.total_stock} units</span>
          <span><strong>${product.has_variants ? 'Stock tracking' : 'Low stock threshold'}</strong>${product.has_variants ? 'Variant-based inventory' : `${product.low_stock_threshold} units`}</span>
        </div>

        <div>
          <div class="admin-help-text">Choose a variant if you are adjusting a specific sellable option.</div>
          <div class="admin-choice-strip" style="margin-top:0.75rem;">
            ${product.variants?.length
              ? product.variants.map(variant => `
                <button
                  type="button"
                  class="admin-choice-pill ${state.selectedVariantId === variant.id ? 'is-active' : ''}"
                  data-select-variant="${variant.id}"
                >
                  <span>${escapeHtml(variant.title || variant.sku || 'Variant')}</span>
                  <small>${escapeHtml(String(variant.stock_quantity || 0))} units in stock</small>
                </button>
              `).join('')
              : '<div class="admin-empty">This product has no variants. The transaction will apply to the product stock directly.</div>'}
          </div>
        </div>

        <div class="admin-inline-form">
          <button type="button" class="admin-submit" data-transaction-mode="incoming">Incoming stock</button>
          <button type="button" data-transaction-mode="adjustment">Manual adjustment</button>
        </div>

        <div class="admin-help-text">
          ${product.has_variants
            ? `Using product ID <strong>${product.id}</strong>${selectedVariant ? ` and variant ID <strong>${selectedVariant.id}</strong>` : ''}. Sellable stock is tracked per variant, and the total shown above is the sum of those variant units.`
            : `Using product ID <strong>${product.id}</strong>${selectedVariant ? ` and variant ID <strong>${selectedVariant.id}</strong>` : ''}.`}
        </div>
      </div>
    `;
  }

  function renderStockTable() {
    if (state.loading) return '<div class="admin-empty">Loading stock view...</div>';

    return `
      <div class="admin-table-shell">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Brand / Category</th>
              <th>Stock state</th>
              <th>Total stock</th>
              <th>Variant detail</th>
            </tr>
          </thead>
          <tbody>
            ${state.stock.map(product => `
              <tr style="${state.selectedProductId === product.id ? 'background:rgba(220,38,38,0.08);' : ''}">
                <td>
                  <button type="button" data-select-product="${product.id}" style="display:flex;flex-direction:column;gap:0.2rem;padding:0;background:none;border:0;color:inherit;cursor:pointer;text-align:left;width:100%;">
                    <strong>${escapeHtml(product.name)}</strong>
                    <span>${escapeHtml(product.slug)}</span>
                  </button>
                </td>
                <td>${escapeHtml([product.brand, product.category].filter(Boolean).join(' / '))}</td>
                <td><span class="${badgeClassForStockState(product.stock_state)}">${escapeHtml(product.stock_label)}</span></td>
                <td>${product.total_stock}</td>
                <td>
                  ${product.variants?.length
                    ? product.variants.map(variant => `
                      <button type="button" data-select-variant="${variant.id}" style="display:inline-flex;align-items:center;gap:0.35rem;margin:0 0.35rem 0.35rem 0;padding:0.45rem 0.7rem;border-radius:999px;border:1px solid ${state.selectedVariantId === variant.id ? 'rgba(220,38,38,0.55)' : 'rgba(255,255,255,0.12)'};background:${state.selectedVariantId === variant.id ? 'rgba(220,38,38,0.15)' : 'rgba(255,255,255,0.04)'};color:#fff;cursor:pointer;">
                        ${escapeHtml(variant.title || variant.sku || 'Variant')} | Stock ${variant.stock_quantity}
                      </button>
                    `).join('')
                    : 'Simple product'}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderAlerts() {
    if (!state.alerts.length) return '<div class="admin-empty">No recent inventory alerts.</div>';

    return `
      <div class="admin-list">
        ${state.alerts.map(alert => `
          <div class="admin-list-item">
            <div class="admin-list-item-title">${escapeHtml(alert.product?.name || 'Product')}</div>
            <div class="admin-list-item-meta">
              ${escapeHtml(alertScopeLabel(alert))}
            </div>
            <div class="admin-list-item-meta">
              ${formatAlertType(alert.alert_type)}. ${formatCurrentAlertQuantity(alert)}
            </div>
            <div class="admin-list-item-meta">
              ${formatAlertHistory(alert)}
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }

  function renderTransactions() {
    if (!state.transactions.length) return '<div class="admin-empty">No recent inventory transactions.</div>';

    return `
      <div class="admin-list">
        ${state.transactions.map(transaction => `
          <div class="admin-list-item">
            <div class="admin-list-item-title">${escapeHtml(transaction.product?.name || 'Product')}</div>
            <div class="admin-list-item-meta">
              ${transaction.quantity_change > 0 ? '+' : ''}${transaction.quantity_change} ${formatTransactionReason(transaction.reason)}${transaction.order_id ? ` for order #${transaction.order_id}` : ''}
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }

  function setPanelMessage(message, type = 'warning') {
    state.flash = { type: type === 'warning' || type === 'error' ? 'error' : 'success', message };
    render();
  }

  function applyTransactionLocally({ productId, variantId, quantityChange, reason, orderId, productName, variantTitle }) {
    const product = state.stock.find(item => item.id === productId);

    if (product) {
      const previousPresentation = deriveStockPresentation(product);
      product.total_stock = Number(product.total_stock || 0) + quantityChange;

      let updatedVariant = null;
      if (variantId && Array.isArray(product.variants)) {
        const variant = product.variants.find(item => item.id === variantId);
        if (variant) {
          variant.stock_quantity = Number(variant.stock_quantity || 0) + quantityChange;
          updatedVariant = variant;
        }
      }

      const nextPresentation = deriveStockPresentation(product);
      product.stock_state = nextPresentation.stockState;
      product.stock_label = nextPresentation.stockLabel;

      if (previousPresentation.stockState !== nextPresentation.stockState) {
        state.alerts = [
          {
            id: `local-alert-${Date.now()}`,
            item_type: updatedVariant ? 'variant' : 'product',
            alert_type: mapStockStateToAlertType(nextPresentation.stockState),
            new_qty: updatedVariant ? updatedVariant.stock_quantity : product.total_stock,
            product: {
              name: product.name,
              has_variants: product.has_variants,
            },
            variant: updatedVariant
              ? {
                  title: updatedVariant.title,
                  sku: updatedVariant.sku,
                }
              : null,
          },
          ...state.alerts,
        ].slice(0, 8);
      }
    }

    state.transactions = [
      {
        id: `local-${Date.now()}`,
        quantity_change: quantityChange,
        reason,
        order_id: orderId,
        product: { name: productName },
        variant: variantTitle ? { title: variantTitle } : null,
      },
      ...state.transactions,
    ].slice(0, 8);
  }

  function deriveStockPresentation(product) {
    if (!product?.has_variants) {
      const totalStock = Number(product?.total_stock || 0);
      const threshold = Number(product?.low_stock_threshold || 0);

      if (totalStock <= 0) {
        return { stockState: 'out_of_stock', stockLabel: 'Out of stock' };
      }

      if (totalStock <= threshold) {
        return { stockState: 'low_stock', stockLabel: 'Low stock' };
      }

      return { stockState: 'in_stock', stockLabel: 'In stock' };
    }

    const variants = Array.isArray(product?.variants) ? product.variants : [];
    const totalStock = Number(product?.total_stock || 0);

    if (totalStock <= 0) {
      return { stockState: 'out_of_stock', stockLabel: 'Out of stock' };
    }

    const hasLowVariant = variants.some(variant => {
      const stock = Number(variant?.stock_quantity || 0);
      const threshold = Number(variant?.low_stock_threshold || 0);
      return stock > 0 && stock <= threshold;
    });

    if (hasLowVariant) {
      return { stockState: 'low_stock', stockLabel: 'Low stock' };
    }

    return { stockState: 'in_stock', stockLabel: 'In stock' };
  }

  function mapStockStateToAlertType(stockState) {
    if (stockState === 'out_of_stock') return 'OUT_OF_STOCK';
    if (stockState === 'low_stock') return 'LOW_STOCK';
    return 'IN_STOCK';
  }

  function alertScopeLabel(alert) {
    if (alert?.variant?.title || alert?.variant?.sku) {
      return `Variant: ${alert.variant.title || alert.variant.sku}`;
    }

    if (alert?.product?.has_variants) {
      return 'Variant-tracked product summary';
    }

    return 'Product stock level';
  }

function formatAlertType(type) {
  return String(type || 'Inventory alert')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
}

function formatAlertQuantity(alert) {
  const quantity = Number(alert?.new_qty ?? 0);
  if (quantity <= 0) {
    return 'No units left.';
  }

  return `${quantity} unit${quantity === 1 ? '' : 's'} left.`;
}

function formatCurrentAlertQuantity(alert) {
  const quantity = Number(alert?.current_qty ?? alert?.new_qty ?? 0);
  if (quantity <= 0) {
    return 'Current stock: no units left.';
  }

  return `Current stock: ${quantity} unit${quantity === 1 ? '' : 's'} left.`;
}

function formatAlertHistory(alert) {
  const snapshotQty = Number(alert?.new_qty ?? 0);
  const currentQty = Number(alert?.current_qty ?? snapshotQty);

  if (snapshotQty === currentQty) {
    return `Alert triggered at ${formatAlertQuantity(alert).toLowerCase()}`;
  }

  if (snapshotQty <= 0) {
    return `Alert originally triggered when stock hit zero.`;
  }

  return `Alert originally triggered at ${snapshotQty} unit${snapshotQty === 1 ? '' : 's'}.`;
}

  function formatTransactionReason(reason) {
    const text = String(reason || 'manual adjustment').replaceAll('_', ' ');
    return `for ${text}`;
  }
}
