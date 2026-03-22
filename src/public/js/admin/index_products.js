fetch('/api/products', { credentials: 'same-origin' })
  .then(response => response.json())
  .then(data => {
    const container = document.getElementById('product-list');
    if (!container) return;

    const products = data.data || data.items || [];
    container.innerHTML = '';

    if (!products.length) {
      container.innerHTML = '<div class="admin-empty">No products found.</div>';
      return;
    }

    products.forEach(product => {
      const el = document.createElement('a');
      el.href = '/admin/products/' + product.id;
      el.className = 'admin-link-card';
      el.innerHTML = `
        <strong>${escapeHtml(product.name)}</strong>
        <span>${escapeHtml(formatMoney(product.price ?? product.effective_price ?? 0))} | Stock ${product.stock_quantity ?? 0}</span>
      `;
      container.appendChild(el);
    });
  })
  .catch(() => {
    const container = document.getElementById('product-list');
    if (container) {
      container.innerHTML = '<div class="admin-empty">Failed to load products.</div>';
    }
  });

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
