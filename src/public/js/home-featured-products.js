(() => {
  const grid = document.getElementById('featured-products-grid');
  if (!grid) return;

  const apiUrl = grid.dataset.apiUrl;
  const productBaseUrl = grid.dataset.productBaseUrl;

  const escapeHtml = (unsafe) => {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  };

  const formatPrice = (value) => {
    const n = Number(value);
    if (Number.isNaN(n)) return '';
    return `£${n.toFixed(2)}`;
  };

  const renderLoading = () => {
    grid.innerHTML = '';
    for (let i = 0; i < 4; i += 1) {
      const card = document.createElement('div');
      card.className = 'featured-item';
      card.innerHTML = `
        <div class="product-image" style="background:#111; height:180px; border-radius:12px;"></div>
        <h3 class="product-title">Loading...</h3>
        <p style="opacity:.7">Fetching products</p>
        <div class="product-price">&nbsp;</div>
      `;
      grid.appendChild(card);
    }
  };

  const renderError = (message) => {
    grid.innerHTML = `
      <div class="featured-item" style="grid-column: 1 / -1;">
        <h3 class="product-title">Featured items unavailable</h3>
        <p>${escapeHtml(message)}</p>
      </div>
    `;
  };

  const renderProducts = (items) => {
    grid.innerHTML = '';

    items.forEach((p) => {
      const slug = p.slug;
      const name = p.name;
      const summary = p.summary || '';
      const price = p.effective_price;
      const imgUrl = p.primary_image_url || '/images/techaxis-logo.png';
      const imgAlt = p.primary_image_alt || name;

      const card = document.createElement('div');
      card.className = 'featured-item';
      card.innerHTML = `
        <img src="${escapeHtml(imgUrl)}" alt="${escapeHtml(imgAlt)}" class="product-image">
        <h3 class="product-title">
          <a href="${escapeHtml(productBaseUrl)}/${escapeHtml(slug)}">${escapeHtml(name)}</a>
        </h3>
        <p>${escapeHtml(summary)}</p>
        <div class="product-price">${escapeHtml(formatPrice(price))}</div>
        <a href="/products/${escapeHtml(slug)}" class="primary-btn">View Product</a>
      `;
      grid.appendChild(card);
    });
  };

  const run = async () => {
    if (!apiUrl || !productBaseUrl) {
      renderError('Missing configuration for featured products.');
      return;
    }

    renderLoading();

    try {
      const res = await fetch(apiUrl, { headers: { Accept: 'application/json' } });
      if (!res.ok) throw new Error(`API error (${res.status})`);

      const data = await res.json();
      const items = Array.isArray(data?.items) ? data.items : [];

      if (items.length === 0) {
        renderError('No products found. Seed the database and try again.');
        return;
      }

      renderProducts(items.slice(0, 4));
    } catch (err) {
      renderError(err?.message || 'Could not load featured items.');
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
