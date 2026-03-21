import { apiJson, clearAlert, escapeHtml, formatMoney, setAlert } from './shared/api.js';

const reviewsRoot = document.querySelector('[data-product-reviews-root]');

if (reviewsRoot) {
  initProductReviews(reviewsRoot);
}

async function initProductReviews(root) {
  const app = root.querySelector('[data-product-reviews-app]');
  if (!app) return;

  const productId = Number(root.dataset.productId || 0);
  if (!productId) return;

  const loginUrl = root.dataset.loginUrl || '/login';
  const isAuthenticated = root.dataset.isAuthenticated === '1';
  const shouldOpenComposer = root.dataset.writeReview === '1';
  const selectedOrderItemId = Number(root.dataset.selectedOrderItemId || 0);

  const state = {
    data: null,
    loading: true,
    submitting: false,
    sort: 'newest',
    page: 1,
    alert: null,
    autoOpened: false,
  };

  await load();

  async function load(page = 1) {
    state.loading = true;
    state.page = page;
    render();

    try {
      const params = new URLSearchParams({
        sort: state.sort,
        page: String(page),
        per_page: '6',
      });

      state.data = await apiJson(`/api/products/${productId}/reviews?${params.toString()}`);
      state.loading = false;
      render();

      if (shouldOpenComposer && !state.autoOpened) {
        const composer = app.querySelector('[data-review-composer]');
        const orderSelect = app.querySelector('[name="order_item_id"]');

        if (composer) {
          composer.scrollIntoView({ behavior: 'smooth', block: 'start' });
          composer.querySelector('input[name="rating"]')?.focus();
          state.autoOpened = true;
        }

        if (selectedOrderItemId && orderSelect) {
          orderSelect.value = String(selectedOrderItemId);
        }
      }
    } catch (error) {
      state.loading = false;
      state.alert = { type: 'danger', message: error.message || 'Could not load product reviews.' };
      render();
    }
  }

  function render() {
    const data = state.data;
    const summary = data?.summary ?? {
      average_rating: 0,
      review_count: 0,
      verified_review_count: 0,
      star_breakdown: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 },
    };
    const reviewPage = data?.reviews ?? { items: [], current_page: 1, last_page: 1, total: 0 };
    const currentUserReview = data?.current_user_review ?? null;
    const eligibleOrderItems = data?.eligible_order_items ?? [];

    app.innerHTML = `
      <div class="review-shell">
        <div id="productReviewAlert" class="d-none" role="alert"></div>
        <div class="review-summary-grid">
          <div class="review-summary-card">
            <div class="review-summary-score">${formatNumber(summary.average_rating)}</div>
            <div class="review-stars" aria-label="${escapeHtml(formatNumber(summary.average_rating))} out of 5 stars">
              ${renderStaticStars(summary.average_rating)}
            </div>
            <div class="review-summary-meta">
              <strong>${summary.review_count}</strong> review${summary.review_count === 1 ? '' : 's'}
            </div>
            <div class="review-summary-submeta">
              ${summary.verified_review_count} verified purchase${summary.verified_review_count === 1 ? '' : 's'}
            </div>
          </div>
          <div class="review-breakdown-card">
            ${[5, 4, 3, 2, 1].map(star => renderBreakdownRow(star, summary.star_breakdown?.[star] ?? 0, summary.review_count)).join('')}
          </div>
        </div>

        <div class="review-toolbar">
          <div class="review-toolbar-copy">
            <h3 class="h5 mb-1">Recent reviews</h3>
            <p class="text-muted mb-0">Browse feedback from customers who bought this product.</p>
          </div>
          <label class="review-sort-control">
            <span>Sort by</span>
            <select class="form-select" data-review-sort>
              <option value="newest" ${state.sort === 'newest' ? 'selected' : ''}>Newest</option>
              <option value="highest" ${state.sort === 'highest' ? 'selected' : ''}>Highest rated</option>
              <option value="lowest" ${state.sort === 'lowest' ? 'selected' : ''}>Lowest rated</option>
            </select>
          </label>
        </div>

        ${renderComposer({ isAuthenticated, loginUrl, currentUserReview, eligibleOrderItems, selectedOrderItemId, submitting: state.submitting })}

        <div class="review-list" data-review-list>
          ${state.loading ? renderLoadingCards() : renderReviewCards(reviewPage.items, currentUserReview)}
        </div>

        ${renderPagination(reviewPage)}
      </div>
    `;

    const alertBox = app.querySelector('#productReviewAlert');
    if (state.alert && alertBox) {
      setAlert(alertBox, state.alert.message, state.alert.type);
    } else if (alertBox) {
      clearAlert(alertBox);
    }

    app.querySelector('[data-review-sort]')?.addEventListener('change', event => {
      state.sort = event.target.value;
      load(1);
    });

    app.querySelectorAll('[data-review-page]').forEach(button => {
      button.addEventListener('click', () => {
        const nextPage = Number(button.dataset.reviewPage || 1);
        if (!nextPage || nextPage === reviewPage.current_page) return;
        load(nextPage);
      });
    });

    const composerForm = app.querySelector('[data-review-form]');
    if (composerForm) {
      composerForm.addEventListener('submit', async event => {
        event.preventDefault();

        const formData = new FormData(composerForm);
        const rating = Number(formData.get('rating') || 0);
        const orderItemId = Number(formData.get('order_item_id') || 0);

        if (rating < 1 || rating > 5) {
          state.alert = { type: 'danger', message: 'Please choose a star rating before submitting your review.' };
          render();
          return;
        }

        if (!orderItemId) {
          state.alert = { type: 'danger', message: 'Please choose the completed purchase you want to review.' };
          render();
          return;
        }

        state.submitting = true;
        state.alert = null;
        render();

        try {
          await apiJson(`/api/products/${productId}/reviews`, {
            method: 'POST',
            body: JSON.stringify({
              rating,
              title: String(formData.get('title') || '').trim(),
              body: String(formData.get('body') || '').trim(),
              order_item_id: orderItemId,
            }),
          });

          state.submitting = false;
          state.alert = { type: 'success', message: currentUserReview ? 'Your review has been updated.' : 'Your review has been published.' };
          await load(1);
        } catch (error) {
          state.submitting = false;
          state.alert = { type: 'danger', message: error.message || 'Could not save your review.' };
          render();
        }
      });
    }
  }
}

function renderBreakdownRow(star, count, totalCount) {
  const width = totalCount > 0 ? Math.round((count / totalCount) * 100) : 0;

  return `
    <div class="review-breakdown-row">
      <span class="review-breakdown-label">${star} star</span>
      <div class="review-breakdown-track">
        <span class="review-breakdown-fill" style="width:${width}%"></span>
      </div>
      <span class="review-breakdown-count">${count}</span>
    </div>
  `;
}

function renderComposer({ isAuthenticated, loginUrl, currentUserReview, eligibleOrderItems, selectedOrderItemId, submitting }) {
  if (!isAuthenticated) {
    return `
      <div class="review-composer-card review-state-card">
        <h3 class="h5 mb-2">Write a review</h3>
        <p class="text-muted mb-3">Sign in to leave a product review once your order has been completed.</p>
        <a href="${escapeHtml(loginUrl)}" class="btn btn-primary">Sign in to review</a>
      </div>
    `;
  }

  if (eligibleOrderItems.length === 0) {
    return `
      <div class="review-composer-card review-state-card">
        <h3 class="h5 mb-2">Write a review</h3>
        <p class="text-muted mb-0">Reviews unlock after you have a completed order for this product.</p>
      </div>
    `;
  }

  const selectedEligibleOrderItemId = currentUserReview?.order_item_id
    || eligibleOrderItems.find(item => item.order_item_id === selectedOrderItemId)?.order_item_id
    || eligibleOrderItems[0]?.order_item_id
    || '';

  return `
    <div class="review-composer-card" data-review-composer>
      <div class="review-composer-head">
        <div>
          <h3 class="h5 mb-1">${currentUserReview ? 'Edit your review' : 'Write a review'}</h3>
          <p class="text-muted mb-0">Choose the completed purchase you are reviewing and share practical feedback for future buyers.</p>
        </div>
        ${currentUserReview ? '<span class="review-chip review-chip-accent">Your review</span>' : '<span class="review-chip">Verified buyers only</span>'}
      </div>

      <form data-review-form class="review-form-grid">
        <div class="review-form-field review-form-stars-field">
          <label class="form-label">Your rating</label>
          <div class="review-rating-input" role="radiogroup" aria-label="Choose a rating">
            ${[5, 4, 3, 2, 1].map(star => `
              <label class="review-rating-option">
                <input type="radio" name="rating" value="${star}" ${Number(currentUserReview?.rating || 0) === star ? 'checked' : ''}>
                <span>${'&#9733;'.repeat(star)}</span>
              </label>
            `).join('')}
          </div>
        </div>

        <div class="review-form-field">
          <label class="form-label" for="reviewOrderItem">Completed purchase</label>
          <select id="reviewOrderItem" name="order_item_id" class="form-select">
            ${eligibleOrderItems.map(item => `
              <option value="${item.order_item_id}" ${String(selectedEligibleOrderItemId) === String(item.order_item_id) ? 'selected' : ''}>
                ${escapeHtml(item.label)} - ${escapeHtml(formatMoney(item.unit_price))}
              </option>
            `).join('')}
          </select>
        </div>

        <div class="review-form-field">
          <label class="form-label" for="reviewTitle">Headline</label>
          <input id="reviewTitle" name="title" class="form-control" maxlength="255" value="${escapeHtml(currentUserReview?.title || '')}" placeholder="Sum up your experience in a sentence">
        </div>

        <div class="review-form-field review-form-field-full">
          <label class="form-label" for="reviewBody">Your review</label>
          <textarea id="reviewBody" name="body" class="form-control review-body-input" rows="5" maxlength="5000" placeholder="Talk about performance, build quality, setup, value, and anything customers should know.">${escapeHtml(currentUserReview?.body || '')}</textarea>
        </div>

        <div class="review-form-actions">
          <button type="submit" class="btn btn-primary" ${submitting ? 'disabled' : ''}>
            ${submitting ? 'Saving...' : (currentUserReview ? 'Update review' : 'Publish review')}
          </button>
        </div>
      </form>
    </div>
  `;
}

function renderReviewCards(reviews, currentUserReview) {
  if (!Array.isArray(reviews) || reviews.length === 0) {
    return `
      <div class="review-empty-state">
        <h3 class="h5 mb-2">No reviews yet</h3>
        <p class="text-muted mb-0">Be the first verified buyer to share feedback on this product.</p>
      </div>
    `;
  }

  return reviews.map(review => `
    <article class="review-card">
      <div class="review-card-head">
        <div>
          <div class="review-stars review-stars-inline" aria-label="${escapeHtml(String(review.rating))} out of 5 stars">
            ${renderStaticStars(review.rating)}
          </div>
          <h4 class="review-card-title">${escapeHtml(review.title || 'Customer review')}</h4>
        </div>
        <div class="review-card-meta">
          ${review.verified_purchase ? '<span class="review-chip review-chip-verified">Verified purchase</span>' : ''}
          ${currentUserReview && review.id === currentUserReview.id ? '<span class="review-chip review-chip-accent">Your review</span>' : ''}
        </div>
      </div>
      <div class="review-card-byline">
        <span>${escapeHtml(review.user?.name || 'Customer')}</span>
        <span>${escapeHtml(review.created_at_label || '')}</span>
      </div>
      <p class="review-card-body">${escapeHtml(review.body || 'No written feedback provided.')}</p>
    </article>
  `).join('');
}

function renderLoadingCards() {
  return `
    <div class="review-loading-state">
      <div class="spinner-border text-danger" role="status" aria-hidden="true"></div>
      <span>Loading reviews...</span>
    </div>
  `;
}

function renderPagination(page) {
  if (!page || page.last_page <= 1) return '';

  const pages = [];
  for (let current = 1; current <= page.last_page; current += 1) {
    pages.push(`
      <button type="button" class="btn btn-sm ${current === page.current_page ? 'btn-primary' : 'btn-outline-secondary'}" data-review-page="${current}">
        ${current}
      </button>
    `);
  }

  return `
    <div class="review-pagination">
      <button type="button" class="btn btn-sm btn-outline-secondary" data-review-page="${Math.max(1, page.current_page - 1)}" ${page.current_page === 1 ? 'disabled' : ''}>
        Previous
      </button>
      <div class="review-pagination-pages">${pages.join('')}</div>
      <button type="button" class="btn btn-sm btn-outline-secondary" data-review-page="${Math.min(page.last_page, page.current_page + 1)}" ${page.current_page === page.last_page ? 'disabled' : ''}>
        Next
      </button>
    </div>
  `;
}

function renderStaticStars(rating) {
  const rounded = Math.round(Number(rating) || 0);
  return new Array(5).fill(0).map((_, index) => `
    <span class="review-star ${index < rounded ? 'is-filled' : ''}">&#9733;</span>
  `).join('');
}

function formatNumber(value) {
  return Number(value || 0).toFixed(1);
}
