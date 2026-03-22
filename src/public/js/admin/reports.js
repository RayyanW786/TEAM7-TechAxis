import { apiJson, escapeHtml, formatMoney } from './shared.js';

const root = document.querySelector('[data-admin-reports-root]');

if (root) {
  const app = root.querySelector('[data-admin-reports-app]');
  const state = {
    overview: null,
    orderStatusBreakdown: [],
    ticketKindBreakdown: [],
    salesSummary: null,
    loading: true,
  };

  render();
  loadOverview();

  async function loadOverview() {
    state.loading = true;
    render();
    const response = await apiJson('/api/admin/overview');
    state.overview = response.overview;
    state.orderStatusBreakdown = response.order_status_breakdown || [];
    state.ticketKindBreakdown = response.ticket_kind_breakdown || [];
    state.loading = false;
    render();
  }

  function render() {
    app.innerHTML = `
      ${renderOverview()}

      <div class="admin-two-column">
        <section class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Order status mix</h2>
              <p>Live breakdown of the operational order queue.</p>
            </div>
          </div>
          ${renderBreakdown(state.orderStatusBreakdown)}
        </section>

        <section class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Ticket mix</h2>
              <p>Support load split between refund requests and general product support.</p>
            </div>
          </div>
          ${renderBreakdown(state.ticketKindBreakdown)}
        </section>
      </div>

      <section class="admin-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Sales summary window</h2>
            <p>Run the existing SQL sales summary for any reporting window you want.</p>
          </div>
        </div>
        <form class="admin-form-grid" data-sales-summary-form>
          <input type="date" name="from" required>
          <input type="date" name="to" required>
          <div class="admin-inline-form full-width">
            <button type="submit" class="admin-submit">Run summary</button>
          </div>
        </form>
        ${renderSalesSummary()}
      </section>
    `;

    root.querySelector('[data-sales-summary-form]')?.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      state.salesSummary = await apiJson(`/api/admin/sales-summary?from=${encodeURIComponent(String(formData.get('from')))}&to=${encodeURIComponent(String(formData.get('to')))}`);
      render();
    });
  }

  function renderOverview() {
    if (state.loading || !state.overview) {
      return '<div class="admin-empty">Loading reporting overview...</div>';
    }

    return `
      <div class="admin-shell-grid admin-shell-grid--cards">
        <div class="admin-stat-card"><span>Total orders</span><strong>${state.overview.total_orders}</strong></div>
        <div class="admin-stat-card"><span>Open orders</span><strong>${state.overview.open_orders}</strong></div>
        <div class="admin-stat-card"><span>Shipments in transit</span><strong>${state.overview.shipments_in_transit}</strong></div>
        <div class="admin-stat-card"><span>Revenue last 30 days</span><strong>${escapeHtml(formatMoney(state.overview.revenue_last_30_days || 0))}</strong></div>
      </div>
    `;
  }

  function renderBreakdown(items) {
    if (!items.length) return '<div class="admin-empty">No reporting data found.</div>';

    return `
      <div class="admin-list">
        ${items.map(item => `
          <div class="admin-list-item">
            <div class="admin-list-item-title">${escapeHtml(labelize(item.status || item.ticket_kind))}</div>
            <div class="admin-list-item-meta">${item.total} item(s)</div>
          </div>
        `).join('')}
      </div>
    `;
  }

  function renderSalesSummary() {
    if (!state.salesSummary) {
      return '<div class="admin-empty">Choose a date window to generate a SQL-backed sales summary.</div>';
    }

    return `
      <div class="admin-mini-grid">
        ${Object.entries(state.salesSummary).map(([key, value]) => `
          <div class="admin-mini-card">
            <span>${escapeHtml(labelize(key))}</span>
            <strong>${key.includes('amount') ? escapeHtml(formatMoney(value || 0)) : escapeHtml(String(value ?? '0'))}</strong>
          </div>
        `).join('')}
      </div>
    `;
  }
}

function labelize(value) {
  return String(value || '').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
}
