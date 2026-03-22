import { apiJson, escapeHtml, formatMoney } from './shared.js';

const root = document.querySelector('[data-admin-discounts-root]');

if (root) {
  const app = root.querySelector('[data-admin-discounts-app]');
  const state = {
    filters: { q: '' },
    codes: [],
    selectedCode: null,
    loading: true,
  };

  render();
  loadCodes();

  function render() {
    app.innerHTML = `
      ${renderOverview()}

      <div class="admin-two-column">
        <section class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Existing codes</h2>
              <p>Track usage limits, schedules, activation state, and remaining availability.</p>
            </div>
          </div>
          <form class="admin-inline-form" data-discount-filters>
            <input type="text" name="q" value="${escapeHtml(state.filters.q)}" placeholder="Search by code">
            <button type="submit">Search</button>
          </form>
          ${renderCodeList()}
        </section>

        <section class="admin-detail-card">
          <div class="admin-panel-head">
            <div>
              <h2>${state.selectedCode ? `Edit ${escapeHtml(state.selectedCode.code)}` : 'Create a discount code'}</h2>
              <p>Order-wide discounts use the existing SQL checkout rules and remain the single source of truth.</p>
            </div>
          </div>
          ${renderForm()}
        </section>
      </div>
    `;

    root.querySelector('[data-discount-filters]')?.addEventListener('submit', event => {
      event.preventDefault();
      state.filters.q = String(new FormData(event.currentTarget).get('q') || '').trim();
      loadCodes();
    });

    root.querySelectorAll('[data-code-select]').forEach(button => {
      button.addEventListener('click', async () => {
        state.selectedCode = await apiJson(`/api/discount-codes/${button.dataset.codeSelect}`);
        render();
      });
    });

    root.querySelector('[data-discount-form]')?.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      const payload = {
        code: String(formData.get('code') || '').trim(),
        type: String(formData.get('type') || '').trim(),
        amount: Number(formData.get('amount') || 0),
        max_uses: formData.get('max_uses') ? Number(formData.get('max_uses')) : null,
        max_uses_per_user: formData.get('max_uses_per_user') ? Number(formData.get('max_uses_per_user')) : null,
        min_order_total: formData.get('min_order_total') ? Number(formData.get('min_order_total')) : null,
        starts_at: String(formData.get('starts_at') || '').trim() || null,
        expires_at: String(formData.get('expires_at') || '').trim() || null,
        is_active: formData.get('is_active') === '1',
      };

      if (state.selectedCode) {
        await apiJson(`/api/discount-codes/${state.selectedCode.id}`, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
      } else {
        await apiJson('/api/discount-codes', {
          method: 'POST',
          body: JSON.stringify(payload),
        });
      }

      state.selectedCode = null;
      await loadCodes();
    });

    root.querySelector('[data-code-delete]')?.addEventListener('click', async () => {
      if (!state.selectedCode) return;
      await apiJson(`/api/discount-codes/${state.selectedCode.id}`, { method: 'DELETE' });
      state.selectedCode = null;
      await loadCodes();
    });

    root.querySelector('[data-code-reset]')?.addEventListener('click', () => {
      state.selectedCode = null;
      render();
    });
  }

  function renderOverview() {
    const active = state.codes.filter(code => code.is_active).length;
    const inactive = state.codes.length - active;
    const redemptions = state.codes.reduce((sum, code) => sum + Number(code.redemptions_count || 0), 0);

    return `
      <div class="admin-shell-grid admin-shell-grid--cards">
        <div class="admin-stat-card"><span>Total codes</span><strong>${state.codes.length}</strong></div>
        <div class="admin-stat-card"><span>Active codes</span><strong>${active}</strong></div>
        <div class="admin-stat-card"><span>Inactive codes</span><strong>${inactive}</strong></div>
        <div class="admin-stat-card"><span>Total redemptions</span><strong>${redemptions}</strong></div>
      </div>
    `;
  }

  function renderCodeList() {
    if (state.loading) {
      return '<div class="admin-empty">Loading discount codes...</div>';
    }

    if (!state.codes.length) {
      return '<div class="admin-empty">No discount codes found yet.</div>';
    }

    return `
      <div class="admin-list">
        ${state.codes.map(code => `
          <button type="button" class="admin-list-item ${state.selectedCode?.id === code.id ? 'is-active' : ''}" data-code-select="${code.id}" style="display:grid; gap:0.35rem; align-self:start; height:auto; min-height:0; text-align:left;">
            <div class="admin-list-item-title">${escapeHtml(code.code)}</div>
            <div class="admin-list-item-meta">
              ${escapeHtml(code.type)} | ${code.type === 'percentage' ? `${Number(code.amount).toFixed(2)}%` : formatMoney(code.amount)} | ${code.redemptions_count || 0}${code.max_uses ? ` / ${code.max_uses}` : ''} uses
            </div>
            <div>${code.is_active ? '<span class="admin-badge admin-badge--success">Active</span>' : '<span class="admin-badge admin-badge--muted">Inactive</span>'}</div>
          </button>
        `).join('')}
      </div>
    `;
  }

  function renderForm() {
    const code = state.selectedCode;

    return `
      ${code ? `
        <div class="admin-mini-grid">
          <div class="admin-mini-card"><span>Code</span><strong>${escapeHtml(code.code)}</strong></div>
          <div class="admin-mini-card"><span>Type</span><strong>${escapeHtml(code.type)}</strong></div>
          <div class="admin-mini-card"><span>Uses</span><strong>${code.redemptions_count || 0}${code.max_uses ? ` / ${code.max_uses}` : ''}</strong></div>
          <div class="admin-mini-card"><span>Status</span><strong>${code.is_active ? 'Active' : 'Inactive'}</strong></div>
        </div>
      ` : ''}

      <form class="admin-form-grid" data-discount-form>
        <input type="text" name="code" placeholder="Code" value="${escapeHtml(code?.code || '')}" required>
        <select name="type">
          <option value="percentage" ${code?.type === 'percentage' ? 'selected' : ''}>Percentage</option>
          <option value="fixed" ${code?.type === 'fixed' ? 'selected' : ''}>Fixed amount</option>
        </select>
        <input type="number" min="0.01" step="0.01" name="amount" placeholder="Amount" value="${escapeHtml(code?.amount || '')}" required>
        <input type="number" min="1" step="1" name="max_uses" placeholder="Max uses" value="${escapeHtml(code?.max_uses || '')}">
        <input type="number" min="1" step="1" name="max_uses_per_user" placeholder="Max uses per user" value="${escapeHtml(code?.max_uses_per_user || '')}">
        <input type="number" min="0" step="0.01" name="min_order_total" placeholder="Minimum order total" value="${escapeHtml(code?.min_order_total || '')}">
        <input type="datetime-local" name="starts_at" value="${toDateTimeLocal(code?.starts_at)}">
        <input type="datetime-local" name="expires_at" value="${toDateTimeLocal(code?.expires_at)}">
        <label class="admin-help-text">
          <input type="checkbox" name="is_active" value="1" ${code ? (code.is_active ? 'checked' : '') : 'checked'}>
          Active and available at checkout
        </label>
        <div class="admin-inline-form full-width">
          <button type="submit" class="admin-submit">${code ? 'Save changes' : 'Create code'}</button>
          <button type="button" data-code-reset>Reset form</button>
          ${code ? '<button type="button" data-code-delete>Delete code</button>' : ''}
        </div>
      </form>
    `;
  }

  async function loadCodes() {
    state.loading = true;
    render();
    const params = new URLSearchParams({ per_page: '50' });
    if (state.filters.q) params.set('q', state.filters.q);
    const response = await apiJson(`/api/discount-codes?${params.toString()}`);
    state.codes = response.data || [];
    state.loading = false;
    render();
  }
}

function toDateTimeLocal(value) {
  if (!value) return '';
  const date = new Date(value);
  const pad = part => String(part).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
