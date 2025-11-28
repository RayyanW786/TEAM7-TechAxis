export function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

export function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

export function formatMoney(amount) {
  const number = Number(amount ?? 0);
  return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(number);
}

export function parsePositiveInt(rawValue) {
  const number = Number(rawValue);
  if (!Number.isInteger(number) || number < 1) return null;
  return number;
}

export function setAlert(alertElement, message, type) {
  // type: 'success' | 'danger' | 'warning' | 'info'
  alertElement.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
  alertElement.classList.add('alert', `alert-${type}`);
  alertElement.textContent = message;
}

export function clearAlert(alertElement) {
  alertElement.classList.add('d-none');
  alertElement.textContent = '';
  alertElement.classList.remove('alert', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
}

export async function apiJson(url, options = {}) {
  const csrfToken = getCsrfToken();

  const response = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers || {}),
    },
  });

  if (!response.ok) {
    let message = 'Request failed';
    try {
      const data = await response.json();
      message = data.message || message;
    } catch (_) {}
    throw new Error(message);
  }

  return response.json();
}
