export function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

export function formatMoney(amount) {
  return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(Number(amount ?? 0));
}

export function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export async function apiJson(url, options = {}) {
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

export function badgeClassForStockState(state) {
  if (state === 'out_of_stock') return 'admin-badge admin-badge--danger';
  if (state === 'low_stock') return 'admin-badge admin-badge--warning';
  return 'admin-badge admin-badge--success';
}

export function badgeClassForTicketStatus(status) {
  if (status === 'closed') return 'admin-badge admin-badge--muted';
  if (status === 'waiting_on_customer') return 'admin-badge admin-badge--warning';
  if (status === 'waiting_on_admin') return 'admin-badge admin-badge--danger';
  return 'admin-badge admin-badge--success';
}
