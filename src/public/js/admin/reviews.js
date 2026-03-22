(function () {
  const root = document.querySelector('[data-admin-reviews-root]');
  if (!root) return;

  const app = root.querySelector('[data-admin-reviews-app]');
  if (!app) return;

  const state = {
    products: { loading: true, data: null, filters: { q: '', rating: '', verified: '', date_from: '', date_to: '' }, page: 1 },
    service: { loading: true, data: null, filters: { q: '', rating: '', date_from: '', date_to: '' }, page: 1 },
  };

  render();
  loadProductReviews();
  loadServiceReviews();

  function render() {
    app.innerHTML = `
      <div class="admin-reviews-grid">
        <section class="admin-review-panel">
          <div class="admin-review-panel-head">
            <div>
              <h2>Product reviews</h2>
              <p>Track verified buyer feedback and identify product quality patterns.</p>
            </div>
          </div>
          ${renderSummaryCards(state.products.data?.summary, true)}
          ${renderProductFilters(state.products.filters)}
          ${renderTable('product', state.products)}
        </section>

        <section class="admin-review-panel">
          <div class="admin-review-panel-head">
            <div>
              <h2>Service reviews</h2>
              <p>Monitor testimonials about the wider Tech Axis experience.</p>
            </div>
          </div>
          ${renderSummaryCards(state.service.data?.summary, false)}
          ${renderServiceFilters(state.service.filters)}
          ${renderTable('service', state.service)}
        </section>
      </div>
    `;

    root.querySelector('[data-product-review-filters]')?.addEventListener('submit', event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      state.products.filters = {
        q: String(formData.get('q') || '').trim(),
        rating: String(formData.get('rating') || '').trim(),
        verified: String(formData.get('verified') || '').trim(),
        date_from: String(formData.get('date_from') || '').trim(),
        date_to: String(formData.get('date_to') || '').trim(),
      };
      loadProductReviews(1);
    });

    root.querySelector('[data-service-review-filters]')?.addEventListener('submit', event => {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      state.service.filters = {
        q: String(formData.get('q') || '').trim(),
        rating: String(formData.get('rating') || '').trim(),
        date_from: String(formData.get('date_from') || '').trim(),
        date_to: String(formData.get('date_to') || '').trim(),
      };
      loadServiceReviews(1);
    });

    root.querySelectorAll('[data-admin-product-page]').forEach(button => {
      button.addEventListener('click', () => loadProductReviews(Number(button.dataset.adminProductPage || 1)));
    });

    root.querySelectorAll('[data-admin-service-page]').forEach(button => {
      button.addEventListener('click', () => loadServiceReviews(Number(button.dataset.adminServicePage || 1)));
    });
  }

  async function loadProductReviews(page = 1) {
    state.products.loading = true;
    state.products.page = page;
    render();

    try {
      const params = new URLSearchParams({ page: String(page) });
      Object.entries(state.products.filters).forEach(([key, value]) => {
        if (value) params.set(key, value);
      });

      const response = await fetch(`/api/admin/reviews/products?${params.toString()}`, { credentials: 'same-origin' });
      state.products.data = await response.json();
    } catch (_error) {
      state.products.data = { reviews: { items: [] } };
    } finally {
      state.products.loading = false;
      render();
    }
  }

  async function loadServiceReviews(page = 1) {
    state.service.loading = true;
    state.service.page = page;
    render();

    try {
      const params = new URLSearchParams({ page: String(page) });
      Object.entries(state.service.filters).forEach(([key, value]) => {
        if (value) params.set(key, value);
      });

      const response = await fetch(`/api/admin/reviews/service?${params.toString()}`, { credentials: 'same-origin' });
      state.service.data = await response.json();
    } catch (_error) {
      state.service.data = { reviews: { items: [] } };
    } finally {
      state.service.loading = false;
      render();
    }
  }
})();

function renderSummaryCards(summary, includeVerified) {
  const safeSummary = summary || { average_rating: 0, review_count: 0, verified_review_count: 0 };

  return `
    <div class="admin-review-summary">
      <div class="admin-review-stat">
        <span>Average rating</span>
        <strong>${Number(safeSummary.average_rating || 0).toFixed(2)}</strong>
      </div>
      <div class="admin-review-stat">
        <span>Total reviews</span>
        <strong>${safeSummary.review_count || 0}</strong>
      </div>
      ${includeVerified ? `
        <div class="admin-review-stat">
          <span>Verified reviews</span>
          <strong>${safeSummary.verified_review_count || 0}</strong>
        </div>
      ` : ''}
    </div>
  `;
}

function renderProductFilters(filters) {
  return `
    <form class="admin-review-filters" data-product-review-filters>
      <input type="text" name="q" value="${escapeHtml(filters.q)}" placeholder="Search product, user, title, or text">
      <select name="rating">
        <option value="">All ratings</option>
        <option value="5" ${filters.rating === '5' ? 'selected' : ''}>5 stars</option>
        <option value="4" ${filters.rating === '4' ? 'selected' : ''}>4 stars</option>
        <option value="3" ${filters.rating === '3' ? 'selected' : ''}>3 stars</option>
        <option value="2" ${filters.rating === '2' ? 'selected' : ''}>2 stars</option>
        <option value="1" ${filters.rating === '1' ? 'selected' : ''}>1 star</option>
      </select>
      <select name="verified">
        <option value="">All review types</option>
        <option value="verified" ${filters.verified === 'verified' ? 'selected' : ''}>Verified only</option>
        <option value="unverified" ${filters.verified === 'unverified' ? 'selected' : ''}>Unverified only</option>
      </select>
      <input type="date" name="date_from" value="${escapeHtml(filters.date_from)}">
      <input type="date" name="date_to" value="${escapeHtml(filters.date_to)}">
      <button type="submit">Apply</button>
    </form>
  `;
}

function renderServiceFilters(filters) {
  return `
    <form class="admin-review-filters" data-service-review-filters>
      <input type="text" name="q" value="${escapeHtml(filters.q)}" placeholder="Search user or comment">
      <select name="rating">
        <option value="">All ratings</option>
        <option value="5" ${filters.rating === '5' ? 'selected' : ''}>5 stars</option>
        <option value="4" ${filters.rating === '4' ? 'selected' : ''}>4 stars</option>
        <option value="3" ${filters.rating === '3' ? 'selected' : ''}>3 stars</option>
        <option value="2" ${filters.rating === '2' ? 'selected' : ''}>2 stars</option>
        <option value="1" ${filters.rating === '1' ? 'selected' : ''}>1 star</option>
      </select>
      <input type="date" name="date_from" value="${escapeHtml(filters.date_from)}">
      <input type="date" name="date_to" value="${escapeHtml(filters.date_to)}">
      <button type="submit">Apply</button>
    </form>
  `;
}

function renderTable(type, state) {
  if (state.loading) {
    return '<div class="admin-review-empty">Loading reviews...</div>';
  }

  const items = state.data?.reviews?.items || [];
  if (items.length === 0) {
    return '<div class="admin-review-empty">No reviews found for the current filters.</div>';
  }

  if (type === 'product') {
    return `
      <div class="admin-review-table-shell">
        <table class="admin-review-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>User</th>
              <th>Rating</th>
              <th>Verified</th>
              <th>Review</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            ${items.map(item => `
              <tr>
                <td>${escapeHtml(item.product?.name || 'Unknown product')}</td>
                <td>${escapeHtml(item.user?.name || 'Unknown user')}</td>
                <td>${item.rating}/5</td>
                <td>${item.verified_purchase ? 'Yes' : 'No'}</td>
                <td>
                  <strong>${escapeHtml(item.title || 'No title')}</strong>
                  <p>${escapeHtml(item.body || 'No body')}</p>
                </td>
                <td>${escapeHtml(item.created_at_label || '')}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        ${renderAdminPagination(state.data?.reviews, 'product')}
      </div>
    `;
  }

  return `
    <div class="admin-review-table-shell">
      <table class="admin-review-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Rating</th>
            <th>Comment</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          ${items.map(item => `
            <tr>
              <td>${escapeHtml(item.user?.name || 'Unknown user')}</td>
              <td>${item.rating}/5</td>
              <td>${escapeHtml(item.comment || 'No comment')}</td>
              <td>${escapeHtml(item.created_at_label || '')}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      ${renderAdminPagination(state.data?.reviews, 'service')}
    </div>
  `;
}

function renderAdminPagination(page, type) {
  if (!page || page.last_page <= 1) return '';

  const attr = type === 'product' ? 'data-admin-product-page' : 'data-admin-service-page';

  return `
    <div class="admin-review-pagination">
      <button type="button" ${attr}="${Math.max(1, page.current_page - 1)}" ${page.current_page === 1 ? 'disabled' : ''}>Previous</button>
      <span>Page ${page.current_page} of ${page.last_page}</span>
      <button type="button" ${attr}="${Math.min(page.last_page, page.current_page + 1)}" ${page.current_page === page.last_page ? 'disabled' : ''}>Next</button>
    </div>
  `;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
