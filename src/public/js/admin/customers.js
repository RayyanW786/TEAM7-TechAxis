import { apiJson, escapeHtml, formatMoney } from './shared.js';

const root = document.querySelector('[data-admin-customers-root]');

if (root) {
  const app = root.querySelector('[data-admin-customers-app]');
  const state = {
    filters: { q: '' },
    customers: [],
    selectedCustomer: null,
    detail: null,
    loading: true,
    detailLoading: false,
  };

  render();
  loadCustomers();

  function render() {
    app.innerHTML = `
      ${renderOverview()}

      <section class="admin-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Find customers</h2>
            <p>Search by name, email, or customer ID and jump into recent order context instantly.</p>
          </div>
        </div>
        <form class="admin-filter-bar" data-customer-filters>
          <input type="text" name="q" value="${escapeHtml(state.filters.q)}" placeholder="Search customer name, email, or #id">
          <button type="submit">Search</button>
        </form>
      </section>

      <div class="admin-two-column">
        <section class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Customers</h2>
              <p>${state.loading ? 'Loading customers...' : `${state.customers.length} customer(s) loaded`}</p>
            </div>
          </div>
          ${renderCustomerList()}
        </section>

        <section class="admin-detail-card">
          ${renderCustomerDetail()}
        </section>
      </div>
    `;

    root.querySelector('[data-customer-filters]')?.addEventListener('submit', event => {
      event.preventDefault();
      state.filters.q = String(new FormData(event.currentTarget).get('q') || '').trim();
      loadCustomers();
    });

    root.querySelectorAll('[data-customer-select]').forEach(button => {
      button.addEventListener('click', () => loadCustomerDetail(button.dataset.customerSelect));
    });

    root.querySelector('[data-customer-form]')?.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      const payload = {
        name: String(formData.get('name') || '').trim(),
        email: String(formData.get('email') || '').trim(),
        phone: String(formData.get('phone') || '').trim() || null,
        date_of_birth: String(formData.get('date_of_birth') || '').trim() || null,
      };
      const password = String(formData.get('password') || '').trim();
      if (password) payload.password = password;

      if (state.selectedCustomer) {
        await apiJson(`/api/admin/customers/${state.selectedCustomer.id}`, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
        await loadCustomerDetail(state.selectedCustomer.id);
      } else {
        payload.password = payload.password || 'TempPass123!';
        const created = await apiJson('/api/admin/customers', {
          method: 'POST',
          body: JSON.stringify(payload),
        });
        await loadCustomers(false);
        await loadCustomerDetail(created.id);
      }
    });

    root.querySelector('[data-customer-delete]')?.addEventListener('click', async () => {
      if (!state.selectedCustomer) return;
      await apiJson(`/api/admin/customers/${state.selectedCustomer.id}`, { method: 'DELETE' });
      state.selectedCustomer = null;
      state.detail = null;
      await loadCustomers();
    });

    root.querySelector('[data-customer-reset]')?.addEventListener('click', () => {
      state.selectedCustomer = null;
      state.detail = null;
      render();
    });
  }

  function renderOverview() {
    const selected = state.detail?.customer;
    const stats = state.detail?.stats;

    return `
      <div class="admin-shell-grid admin-shell-grid--cards">
        <div class="admin-stat-card"><span>Loaded customers</span><strong>${state.customers.length}</strong></div>
        <div class="admin-stat-card"><span>Selected customer</span><strong>${selected ? `#${selected.id}` : 'None'}</strong></div>
        <div class="admin-stat-card"><span>Lifetime value</span><strong>${escapeHtml(formatMoney(stats?.lifetime_value || 0))}</strong></div>
        <div class="admin-stat-card"><span>Recent orders</span><strong>${stats?.order_count || 0}</strong></div>
      </div>
    `;
  }

  function renderCustomerList() {
    if (state.loading) return '<div class="admin-empty">Loading customers...</div>';
    if (!state.customers.length) return '<div class="admin-empty">No customers matched the current search.</div>';

    return `
      <div class="admin-list">
        ${state.customers.map(customer => `
          <button type="button" class="admin-list-item ${state.selectedCustomer?.id === customer.id ? 'is-active' : ''}" data-customer-select="${customer.id}" style="display:grid; gap:0.35rem; align-self:start; height:auto; min-height:0; text-align:left;">
            <div class="admin-list-item-title">#${customer.id} ${escapeHtml(customer.name)}</div>
            <div class="admin-list-item-meta">${escapeHtml(customer.email)} | ${customer.orders_count || 0} order(s)</div>
          </button>
        `).join('')}
      </div>
    `;
  }

  function renderCustomerDetail() {
    if (state.detailLoading) return '<div class="admin-empty">Loading customer detail...</div>';

    const customer = state.detail?.customer;
    const stats = state.detail?.stats;

    return `
      <div class="admin-panel-head">
        <div>
          <h2>${customer ? `Edit ${escapeHtml(customer.name)}` : 'Create customer'}</h2>
          <p>${customer ? 'Update account information and review recent orders and saved addresses.' : 'Create a customer account directly from the admin area.'}</p>
        </div>
      </div>

      ${customer ? `
        <div class="admin-mini-grid">
          <div class="admin-mini-card"><span>Customer ID</span><strong>#${customer.id}</strong></div>
          <div class="admin-mini-card"><span>Lifetime value</span><strong>${escapeHtml(formatMoney(stats?.lifetime_value || 0))}</strong></div>
          <div class="admin-mini-card"><span>Total orders</span><strong>${stats?.order_count || 0}</strong></div>
          <div class="admin-mini-card"><span>Saved addresses</span><strong>${customer.addresses?.length || 0}</strong></div>
        </div>
      ` : ''}

      <form class="admin-form-grid" data-customer-form>
        <input type="text" name="name" placeholder="Full name" value="${escapeHtml(customer?.name || '')}" required>
        <input type="email" name="email" placeholder="Email address" value="${escapeHtml(customer?.email || '')}" required>
        <input type="text" name="phone" placeholder="Phone number" value="${escapeHtml(customer?.customer_profile?.phone || '')}">
        <input type="date" name="date_of_birth" value="${escapeHtml(customer?.customer_profile?.date_of_birth || '')}">
        <input type="password" class="full-width" name="password" placeholder="${customer ? 'Leave blank to keep current password' : 'Password (defaults to TempPass123! if left blank)'}">
        <div class="admin-inline-form full-width">
          <button type="submit" class="admin-submit">${customer ? 'Save customer' : 'Create customer'}</button>
          <button type="button" data-customer-reset>Reset form</button>
          ${customer ? '<button type="button" data-customer-delete>Delete customer</button>' : ''}
        </div>
      </form>

      ${customer ? `
        <div class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h3>Saved addresses</h3>
            </div>
          </div>
          ${(customer.addresses || []).length ? `
            <div class="admin-list">
              ${(customer.addresses || []).map(address => `
                <div class="admin-list-item">
                  <div class="admin-list-item-title">${escapeHtml(address.label || 'Address')}</div>
                  <div class="admin-list-item-meta">${escapeHtml([address.recipient_name, address.line1, address.city, address.postal_code].filter(Boolean).join(', '))}</div>
                </div>
              `).join('')}
            </div>
          ` : '<div class="admin-empty">No saved addresses for this customer yet.</div>'}
        </div>

        <div class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h3>Recent orders</h3>
            </div>
          </div>
          ${(customer.orders || []).length ? `
            <div class="admin-table-shell">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  ${(customer.orders || []).map(order => `
                    <tr>
                      <td>#${order.id}</td>
                      <td>${escapeHtml(order.status)}</td>
                      <td>${escapeHtml(formatMoney(order.total_amount || 0))}</td>
                      <td>${escapeHtml(formatDate(order.created_at))}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          ` : '<div class="admin-empty">No recent orders to show.</div>'}
        </div>
      ` : ''}
    `;
  }

  async function loadCustomers(selectFirst = true) {
    state.loading = true;
    render();
    const params = new URLSearchParams({ per_page: '50' });
    if (state.filters.q) params.set('q', state.filters.q);
    const response = await apiJson(`/api/admin/customers?${params.toString()}`);
    state.customers = response.data || [];
    state.loading = false;

    if (selectFirst && state.customers.length && !state.selectedCustomer) {
      await loadCustomerDetail(state.customers[0].id);
    } else {
      render();
    }
  }

  async function loadCustomerDetail(customerId) {
    state.detailLoading = true;
    render();
    state.detail = await apiJson(`/api/admin/customers/${customerId}`);
    state.selectedCustomer = state.detail.customer;
    state.detailLoading = false;
    render();
  }
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString() : 'N/A';
}
