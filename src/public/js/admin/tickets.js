import { apiJson, badgeClassForTicketStatus, escapeHtml, formatMoney } from './shared.js';

const root = document.querySelector('[data-admin-tickets-root]');

if (root) {
  const app = root.querySelector('[data-admin-tickets-app]');
  const state = {
    meta: { admin_users: [], kind_options: [], status_options: [] },
    filters: { q: '', status: '', ticket_kind: '', assigned_to_user_id: '' },
    tickets: [],
    selectedTicket: null,
    loading: true,
    detailLoading: false,
    flash: null,
    keepThreadPinnedToBottom: false,
  };

  render();
  init();

  async function init() {
    state.meta = await apiJson('/api/admin/tickets/meta');
    await loadTickets();
  }

  function render() {
    app.innerHTML = `
      ${state.flash ? `<div class="admin-empty ${state.flash.type === 'error' ? 'admin-empty--warning' : ''}">${escapeHtml(state.flash.message)}</div>` : ''}

      ${renderOverview()}

      <section class="admin-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Filter inbox</h2>
            <p>Search by customer, order, product, ticket subject, or refund/product-support type.</p>
          </div>
        </div>
        <form class="admin-filter-bar" data-ticket-filters>
          <input type="text" name="q" value="${escapeHtml(state.filters.q)}" placeholder="Search subject, customer, order, or product">
          <select name="status">
            <option value="">All statuses</option>
            ${state.meta.status_options.map(status => `<option value="${status}" ${state.filters.status === status ? 'selected' : ''}>${ticketStatusLabel(status)}</option>`).join('')}
          </select>
          <select name="ticket_kind">
            <option value="">All ticket types</option>
            ${state.meta.kind_options.map(kind => `<option value="${kind}" ${state.filters.ticket_kind === kind ? 'selected' : ''}>${labelize(kind)}</option>`).join('')}
          </select>
          <select name="assigned_to_user_id">
            <option value="">Any assignee</option>
            <option value="unassigned" ${state.filters.assigned_to_user_id === 'unassigned' ? 'selected' : ''}>Unassigned</option>
            ${state.meta.admin_users.map(admin => `<option value="${admin.id}" ${state.filters.assigned_to_user_id === String(admin.id) ? 'selected' : ''}>${escapeHtml(admin.name)}</option>`).join('')}
          </select>
          <button type="submit">Apply</button>
        </form>
      </section>

      <div class="admin-two-column">
        <section class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Ticket list</h2>
              <p>${state.loading ? 'Loading tickets...' : `${state.tickets.length} ticket(s) loaded`}</p>
            </div>
          </div>
          ${renderTicketList()}
        </section>

        <section class="admin-detail-card">
          ${renderTicketDetail()}
        </section>
      </div>
    `;

    root.querySelector('[data-ticket-filters]')?.addEventListener('submit', event => {
      event.preventDefault();
      state.flash = null;
      const formData = new FormData(event.currentTarget);
      state.filters = {
        q: String(formData.get('q') || '').trim(),
        status: String(formData.get('status') || '').trim(),
        ticket_kind: String(formData.get('ticket_kind') || '').trim(),
        assigned_to_user_id: String(formData.get('assigned_to_user_id') || '').trim(),
      };
      loadTickets();
    });

    root.querySelectorAll('[data-ticket-select]').forEach(button => {
      button.addEventListener('click', () => {
        state.flash = null;
        loadTicketDetail(button.dataset.ticketSelect);
      });
    });

    root.querySelector('[data-ticket-assign-form]')?.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      try {
        const updatedTicket = await apiJson(`/api/tickets/${state.selectedTicket.id}/assign`, {
          method: 'POST',
          body: JSON.stringify({
            assigned_to_user_id: formData.get('assigned_to_user_id') ? Number(formData.get('assigned_to_user_id')) : null,
          }),
        });
        state.selectedTicket = { ...state.selectedTicket, ...updatedTicket };
        state.flash = { type: 'success', message: 'Assignee updated.' };
        render();
        await loadTicketDetail(state.selectedTicket.id, false);
        await loadTickets(false);
      } catch (error) {
        state.flash = { type: 'error', message: error.message || 'Could not save the assignee.' };
        render();
      }
    });

    root.querySelector('[data-ticket-close]')?.addEventListener('click', async () => {
      try {
        const updatedTicket = await apiJson(`/api/tickets/${state.selectedTicket.id}/close`, { method: 'POST' });
        state.selectedTicket = { ...state.selectedTicket, ...updatedTicket };
        state.flash = { type: 'success', message: 'Ticket closed.' };
        render();
        await loadTicketDetail(state.selectedTicket.id, false);
        await loadTickets(false);
      } catch (error) {
        state.flash = { type: 'error', message: error.message || 'Could not close the ticket.' };
        render();
      }
    });

    root.querySelector('[data-ticket-reopen]')?.addEventListener('click', async () => {
      try {
        const updatedTicket = await apiJson(`/api/tickets/${state.selectedTicket.id}/reopen`, { method: 'POST' });
        state.selectedTicket = { ...state.selectedTicket, ...updatedTicket };
        state.flash = { type: 'success', message: 'Ticket reopened.' };
        render();
        await loadTicketDetail(state.selectedTicket.id, false);
        await loadTickets(false);
      } catch (error) {
        state.flash = { type: 'error', message: error.message || 'Could not reopen the ticket.' };
        render();
      }
    });

    root.querySelector('[data-ticket-message-form]')?.addEventListener('submit', async event => {
      event.preventDefault();
      const form = event.currentTarget;
      const formData = new FormData(form);
      const isInternal = formData.get('is_internal') === '1';
      try {
        const updatedTicket = await apiJson(`/api/tickets/${state.selectedTicket.id}/messages`, {
          method: 'POST',
          body: JSON.stringify({
            body: String(formData.get('body') || '').trim(),
            is_internal: isInternal,
          }),
        });
        state.selectedTicket = updatedTicket;
        state.keepThreadPinnedToBottom = true;
        state.flash = {
          type: 'success',
          message: isInternal ? 'Internal note saved.' : 'Update sent to the ticket thread.',
        };
        form.reset();
        render();
        await loadTickets(false);
        const thread = root.querySelector('.admin-thread');
        if (thread) {
          thread.scrollTop = thread.scrollHeight;
        }
      } catch (error) {
        state.flash = { type: 'error', message: error.message || 'Could not send the update.' };
        render();
      }
    });

    if (state.keepThreadPinnedToBottom) {
      const thread = root.querySelector('.admin-thread');
      if (thread) {
        thread.scrollTop = thread.scrollHeight;
      }
      state.keepThreadPinnedToBottom = false;
    }
  }

  function renderOverview() {
    const totals = {
      loaded: state.tickets.length,
      open: state.tickets.filter(ticket => ticket.status !== 'closed').length,
      refund: state.tickets.filter(ticket => ticket.ticket_kind === 'refund_request').length,
      waitingAdmin: state.tickets.filter(ticket => ticket.status === 'waiting_on_admin').length,
    };

    return `
      <div class="admin-shell-grid admin-shell-grid--cards">
        <div class="admin-stat-card"><span>Loaded tickets</span><strong>${totals.loaded}</strong></div>
        <div class="admin-stat-card"><span>Open tickets</span><strong>${totals.open}</strong></div>
        <div class="admin-stat-card"><span>Refund requests</span><strong>${totals.refund}</strong></div>
        <div class="admin-stat-card"><span>Waiting on admin</span><strong>${totals.waitingAdmin}</strong></div>
      </div>
    `;
  }

  function renderTicketList() {
    if (state.loading) {
      return '<div class="admin-empty">Loading tickets...</div>';
    }

    if (!state.tickets.length) {
      return '<div class="admin-empty">No tickets matched the current filters.</div>';
    }

    return `
      <div class="admin-list">
        ${state.tickets.map(ticket => `
          <button type="button" class="admin-list-item ${state.selectedTicket?.id === ticket.id ? 'is-active' : ''}" data-ticket-select="${ticket.id}" style="display:grid; gap:0.35rem; align-self:start; height:auto; min-height:0; text-align:left;">
            <div class="admin-list-item-title">#${ticket.id} ${escapeHtml(ticket.subject)}</div>
            <div class="admin-list-item-meta">
              ${escapeHtml(ticket.creator?.name || 'Customer')} | ${labelize(ticket.ticket_kind)} | ${labelize(ticket.status)}
            </div>
          </button>
        `).join('')}
      </div>
    `;
  }

  function renderTicketDetail() {
    if (state.detailLoading) {
      return '<div class="admin-empty">Loading ticket detail...</div>';
    }

    if (!state.selectedTicket) {
      return '<div class="admin-empty">Select a ticket to view the conversation, automated order context, and admin actions.</div>';
    }

    const ticket = state.selectedTicket;

    return `
      <div class="admin-panel-head">
        <div>
          <h2>Ticket #${ticket.id}</h2>
          <p>#${ticket.id} | ${escapeHtml(ticket.creator?.name || 'Customer')} | ${escapeHtml(ticket.creator?.email || 'No email')}</p>
        </div>
        <span class="${badgeClassForTicketStatus(ticket.status)}">${labelize(ticket.status)}</span>
      </div>

      <div class="admin-mini-grid">
        <div class="admin-mini-card"><span>Customer</span><strong>#${ticket.creator?.id || 'N/A'}</strong></div>
        <div class="admin-mini-card"><span>Type</span><strong>${labelize(ticket.ticket_kind)}</strong></div>
        <div class="admin-mini-card"><span>Assignee</span><strong>${escapeHtml(ticket.assignee?.name || 'Unassigned')}</strong></div>
        <div class="admin-mini-card"><span>Order</span><strong>${ticket.order ? `#${ticket.order.id}` : 'Not linked'}</strong></div>
        <div class="admin-mini-card"><span>Product</span><strong>${escapeHtml(ticket.product?.name || ticket.order_item?.name_snapshot || 'Not linked')}</strong></div>
      </div>

      ${ticket.order_item ? `
        <div class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h3>Commerce context</h3>
              <p>Automatically attached when the ticket was created from a purchased item.</p>
            </div>
          </div>
          <div class="admin-mini-grid">
            <div class="admin-mini-card"><span>Quantity</span><strong>${ticket.order_item.quantity}</strong></div>
            <div class="admin-mini-card"><span>Unit price</span><strong>${escapeHtml(formatMoney(ticket.order_item.unit_price))}</strong></div>
            <div class="admin-mini-card"><span>Line total</span><strong>${escapeHtml(formatMoney(ticket.order_item.line_total))}</strong></div>
            <div class="admin-mini-card"><span>Order status</span><strong>${labelize(ticket.order?.status || 'unknown')}</strong></div>
          </div>
        </div>
      ` : ''}

      <form class="admin-inline-form" data-ticket-assign-form>
        <select name="assigned_to_user_id">
          <option value="">Unassigned</option>
          ${state.meta.admin_users.map(admin => `
            <option value="${admin.id}" ${ticket.assigned_to_user_id === admin.id ? 'selected' : ''}>${escapeHtml(admin.name)}</option>
          `).join('')}
        </select>
        <button type="submit" class="admin-submit">Save assignee</button>
        ${ticket.status === 'closed'
          ? '<button type="button" data-ticket-reopen>Reopen ticket</button>'
          : '<button type="button" data-ticket-close>Close ticket</button>'}
      </form>

      <div class="admin-thread">
        ${(ticket.messages || []).map(message => `
          <div class="admin-thread-message ${message.is_internal ? 'admin-thread-message--internal' : ''}">
            <div class="admin-thread-message-head">
              <span>${escapeHtml(message.sender?.name || 'User')}</span>
              <span>${escapeHtml(formatDate(message.created_at))}${message.is_internal ? ' | Internal note' : ''}</span>
            </div>
            <div class="admin-thread-message-body">${escapeHtml(message.body)}</div>
          </div>
        `).join('')}
      </div>

      <form class="admin-form-grid" data-ticket-message-form>
        <textarea class="full-width" name="body" placeholder="Reply to the customer or add an internal note..." required></textarea>
        <div class="admin-inline-form full-width" style="justify-content:space-between;align-items:center;">
          <label class="admin-help-text" style="display:inline-flex;align-items:center;gap:0.6rem;margin:0;">
            <input type="checkbox" name="is_internal" value="1" style="width:16px;min-width:16px;height:16px;min-height:16px;margin:0;accent-color:#dc2626;">
            Save as internal note
          </label>
          <button type="submit" class="admin-submit">Send update</button>
        </div>
      </form>
    `;
  }

  async function loadTickets(selectFirst = true) {
    state.loading = true;
    render();
    const params = new URLSearchParams({ per_page: '30' });
    Object.entries(state.filters).forEach(([key, value]) => {
      if (value) params.set(key, value);
    });
    const response = await apiJson(`/api/tickets?${params.toString()}`);
    state.tickets = response.data || [];
    state.loading = false;

    if (selectFirst && state.tickets.length && !state.selectedTicket) {
      await loadTicketDetail(state.tickets[0].id, false);
    } else {
      render();
    }
  }

  async function loadTicketDetail(ticketId, rerender = true) {
    state.detailLoading = true;
    if (rerender) render();
    state.selectedTicket = await apiJson(`/api/tickets/${ticketId}`);
    state.detailLoading = false;
    render();
  }
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : 'Just now';
}

function ticketStatusLabel(value) {
  if (value === 'open') return 'Open tickets';
  if (value === 'closed') return 'Closed tickets';
  return labelize(value);
}

function labelize(value) {
  return String(value || '').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
}
