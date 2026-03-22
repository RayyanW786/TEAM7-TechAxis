const root = document.querySelector('[data-admin-orders-root]');

if (root) {
  const app = root.querySelector('[data-admin-orders-app]');
  const state = {
    filters: { q: '', status: '', date_from: '', date_to: '' },
    orders: [],
    pagination: null,
    selectedOrder: null,
    loading: true,
    detailLoading: false,
    error: '',
  };

  render();
  loadOrders().catch(handleFatalError);

  function render() {
    app.innerHTML = `
      ${state.error ? `<div class="admin-empty admin-empty--warning">${escapeHtml(state.error)}</div>` : ''}

      <section class="admin-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Find orders</h2>
            <p>Search by order id, customer, email, product name, SKU, status, or date.</p>
          </div>
        </div>
        <form class="admin-filter-bar" data-order-filters>
          <input type="text" name="q" value="${escapeHtml(state.filters.q)}" placeholder="Search order, customer, email, product, or SKU">
          <select name="status">
            <option value="">All statuses</option>
            ${['pending', 'placed', 'processing', 'shipped', 'completed', 'cancelled', 'returned'].map(status => `
              <option value="${status}" ${state.filters.status === status ? 'selected' : ''}>${labelize(status)}</option>
            `).join('')}
          </select>
          <input type="date" name="date_from" value="${escapeHtml(state.filters.date_from)}">
          <input type="date" name="date_to" value="${escapeHtml(state.filters.date_to)}">
          <button type="submit">Apply</button>
        </form>
      </section>

      <div class="admin-two-column">
        <section class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Orders</h2>
              <p>${state.loading ? 'Loading orders...' : `${state.pagination?.total ?? state.orders.length} order(s) found`}</p>
            </div>
          </div>
          ${renderOrderList()}
        </section>

        <section class="admin-detail-card">
          ${renderOrderDetail()}
        </section>
      </div>
    `;

    root.querySelector('[data-order-filters]')?.addEventListener('submit', event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      state.filters = {
        q: String(formData.get('q') || '').trim(),
        status: String(formData.get('status') || '').trim(),
        date_from: String(formData.get('date_from') || '').trim(),
        date_to: String(formData.get('date_to') || '').trim(),
      };
      loadOrders().catch(handleFatalError);
    });

    root.querySelectorAll('[data-order-select]').forEach(button => {
      button.addEventListener('click', () => {
        loadOrderDetail(button.dataset.orderSelect).catch(handleFatalError);
      });
    });

    root.querySelector('[data-order-status-form]')?.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      try {
        await apiJson(`/api/orders/${state.selectedOrder.id}/status`, {
          method: 'PATCH',
          body: JSON.stringify({ status: String(formData.get('status') || '') }),
        });
        state.error = '';
        await loadOrderDetail(state.selectedOrder.id, false);
        await loadOrders(false);
      } catch (error) {
        handleFatalError(error);
      }
    });

    root.querySelector('[data-shipment-create-form]')?.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      try {
        await apiJson(`/api/orders/${state.selectedOrder.id}/shipments`, {
          method: 'POST',
          body: JSON.stringify({
            carrier: String(formData.get('carrier') || '').trim() || null,
            tracking_number: String(formData.get('tracking_number') || '').trim() || null,
            shipped_at: String(formData.get('shipped_at') || '').trim() || null,
            delivered_at: String(formData.get('delivered_at') || '').trim() || null,
          }),
        });
        state.error = '';
        event.currentTarget.reset();
        await loadOrderDetail(state.selectedOrder.id, false);
      } catch (error) {
        handleFatalError(error);
      }
    });

    root.querySelectorAll('[data-shipment-delete]').forEach(button => {
      button.addEventListener('click', async () => {
        try {
          await apiJson(`/api/orders/${state.selectedOrder.id}/shipments/${button.dataset.shipmentDelete}`, {
            method: 'DELETE',
          });
          state.error = '';
          await loadOrderDetail(state.selectedOrder.id, false);
        } catch (error) {
          handleFatalError(error);
        }
      });
    });
  }

  function renderOrderList() {
    if (state.loading) {
      return '<div class="admin-empty">Loading orders...</div>';
    }

    if (!state.orders.length) {
      return '<div class="admin-empty">No orders matched the current filters.</div>';
    }

    return `
      <div class="admin-list">
        ${state.orders.map(order => `
          <button type="button" class="admin-list-item ${state.selectedOrder?.id === order.id ? 'is-active' : ''}" data-order-select="${order.id}">
            <div class="admin-list-item-title">Order #${order.id}</div>
            <div class="admin-list-item-meta">
              ${escapeHtml(order.user?.name || 'Guest customer')} | ${labelize(order.status)} | ${escapeHtml(formatMoney(order.total_amount))}
            </div>
          </button>
        `).join('')}
      </div>
    `;
  }

  function renderOrderDetail() {
    if (state.detailLoading) {
      return '<div class="admin-empty">Loading order detail...</div>';
    }

    if (!state.selectedOrder) {
      return '<div class="admin-empty">Select an order to inspect its items, customer details, discounts, notes, and shipments.</div>';
    }

    const order = state.selectedOrder;

    return `
      <div class="admin-panel-head">
        <div>
          <h2>Order #${order.id}</h2>
          <p>${escapeHtml(order.user?.name || 'Guest customer')} | ${escapeHtml(order.user?.email || 'No email')}</p>
        </div>
        <span class="admin-badge">${labelize(order.status)}</span>
      </div>

      <div class="admin-mini-grid">
        <div class="admin-mini-card"><span>Total</span><strong>${escapeHtml(formatMoney(order.total_amount))}</strong></div>
        <div class="admin-mini-card"><span>Discount</span><strong>${escapeHtml(formatMoney(order.discount_total || 0))}</strong></div>
        <div class="admin-mini-card"><span>Placed</span><strong>${escapeHtml(formatDate(order.created_at))}</strong></div>
        <div class="admin-mini-card"><span>Shipments</span><strong>${order.shipments?.length || 0}</strong></div>
      </div>

      <form class="admin-inline-form" data-order-status-form>
        <select name="status">
          ${['pending', 'placed', 'processing', 'shipped', 'completed', 'cancelled', 'returned'].map(status => `
            <option value="${status}" ${order.status === status ? 'selected' : ''}>${labelize(status)}</option>
          `).join('')}
        </select>
        <button type="submit" class="admin-submit">Update status</button>
      </form>

      <div class="admin-table-shell">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Qty</th>
              <th>Unit</th>
              <th>Line total</th>
            </tr>
          </thead>
          <tbody>
            ${(order.items || []).map(item => `
              <tr>
                <td>
                  <strong>${escapeHtml(item.name_snapshot || item.product?.name || 'Item')}</strong>
                  <p>${escapeHtml(item.variant?.title || item.sku_snapshot || '')}</p>
                </td>
                <td>${item.quantity}</td>
                <td>${escapeHtml(formatMoney(item.unit_price))}</td>
                <td>${escapeHtml(formatMoney(item.line_total))}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>

      <div class="admin-panel">
        <div class="admin-panel-head">
          <div>
            <h3>Shipments</h3>
            <p>Create and remove shipment records while the order is being processed.</p>
          </div>
        </div>
        ${(order.shipments || []).length ? `
          <div class="admin-list">
            ${(order.shipments || []).map(shipment => `
              <div class="admin-list-item">
                <div class="admin-list-item-title">${escapeHtml(shipment.carrier || 'Shipment')}</div>
                <div class="admin-list-item-meta">
                  Tracking: ${escapeHtml(shipment.tracking_number || 'Not provided')} | Shipped ${escapeHtml(formatDate(shipment.shipped_at))}
                </div>
                <div class="admin-inline-form" style="margin-top:0.75rem;">
                  <button type="button" data-shipment-delete="${shipment.id}">Delete shipment</button>
                </div>
              </div>
            `).join('')}
          </div>
        ` : '<div class="admin-empty">No shipments recorded yet.</div>'}

        <form class="admin-form-grid" data-shipment-create-form>
          <input type="text" name="carrier" placeholder="Carrier">
          <input type="text" name="tracking_number" placeholder="Tracking number">
          <input type="datetime-local" name="shipped_at">
          <input type="datetime-local" name="delivered_at">
          <div class="admin-inline-form full-width">
            <button type="submit" class="admin-submit">Add shipment</button>
          </div>
        </form>
      </div>

      <div class="admin-panel">
        <div class="admin-panel-head">
          <div>
            <h3>Addresses and notes</h3>
            <p>Use the saved addresses and order notes when processing fulfilment.</p>
          </div>
        </div>
        <div class="admin-mini-grid">
          <div class="admin-mini-card">
            <span>Shipping</span>
            <strong>${escapeHtml(renderAddress(order.shipping_address))}</strong>
          </div>
          <div class="admin-mini-card">
            <span>Billing</span>
            <strong>${escapeHtml(renderAddress(order.billing_address))}</strong>
          </div>
        </div>
        <div class="admin-help-text">${escapeHtml(order.notes || 'No order notes were added by the customer.')}</div>
      </div>
    `;
  }

  async function loadOrders(selectFirst = true) {
    state.loading = true;
    state.error = '';
    render();

    const params = new URLSearchParams();
    Object.entries(state.filters).forEach(([key, value]) => {
      if (value) params.set(key, value);
    });

    const response = await apiJson(`/api/orders?${params.toString()}`);
    state.orders = response.data || [];
    state.pagination = response;
    state.loading = false;

    if (selectFirst && state.orders.length && !state.selectedOrder) {
      await loadOrderDetail(state.orders[0].id, false);
    } else {
      render();
    }
  }

  async function loadOrderDetail(orderId, rerender = true) {
    state.detailLoading = true;
    state.error = '';
    if (rerender) render();
    state.selectedOrder = await apiJson(`/api/orders/${orderId}`);
    state.detailLoading = false;
    render();
  }

  function handleFatalError(error) {
    state.loading = false;
    state.detailLoading = false;
    state.error = error?.message || 'The admin orders page could not load right now.';
    render();
  }
}

function formatDate(value) {
  if (!value) return 'Not set';
  return new Date(value).toLocaleString();
}

function renderAddress(address) {
  if (!address) return 'Not provided';
  return [address.recipient_name, address.line1, address.city, address.postal_code].filter(Boolean).join(', ');
}

function labelize(value) {
  return String(value || '').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function formatMoney(amount) {
  return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(Number(amount ?? 0));
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function apiJson(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers || {}),
    },
  });

  const isJson = response.headers.get('content-type')?.includes('application/json');
  const payload = isJson ? await response.json() : null;

  if (!response.ok) {
    throw new Error(payload?.message || 'Request failed');
  }

  return payload;
}
