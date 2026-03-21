import { apiJson, clearAlert, escapeHtml, setAlert } from './shared/api.js';

const serviceReviewsRoot = document.querySelector('[data-service-reviews-root]');

if (serviceReviewsRoot) {
  initServiceReviews(serviceReviewsRoot);
}

async function initServiceReviews(root) {
  const app = root.querySelector('[data-service-reviews-app]');
  if (!app) return;

  const loginUrl = root.dataset.loginUrl || '/login';
  const isAuthenticated = root.dataset.isAuthenticated === '1';

  const state = {
    data: null,
    page: 1,
    loading: true,
    submitting: false,
    alert: null,
  };

  await load();

  async function load(page = 1) {
    state.loading = true;
    state.page = page;
    render();

    try {
      state.data = await apiJson(`/api/service-reviews?page=${page}&per_page=6`);
      state.loading = false;
      render();
    } catch (error) {
      state.loading = false;
      state.alert = { type: 'danger', message: error.message || 'Could not load service reviews.' };
      render();
    }
  }

  function render() {
    const data = state.data;
    const summary = data?.summary ?? {
      average_rating: 0,
      review_count: 0,
      star_breakdown: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 },
    };
    const reviewPage = data?.reviews ?? { items: [], current_page: 1, last_page: 1 };
    const currentUserReview = data?.current_user_review ?? null;

    app.innerHTML = `
      <div class="service-reviews-shell">
        <div id="serviceReviewAlert" class="d-none" role="alert"></div>

        <div class="service-reviews-summary">
          <div class="service-reviews-score">${Number(summary.average_rating || 0).toFixed(1)}</div>
          <div class="service-reviews-stars">${renderStars(summary.average_rating)}</div>
          <div class="service-reviews-meta">${summary.review_count} customer testimonial${summary.review_count === 1 ? '' : 's'}</div>
        </div>

        <div class="service-reviews-grid">
          <div class="service-reviews-list">
            <h3>Latest service feedback</h3>
            ${state.loading ? renderLoadingState() : renderServiceCards(reviewPage.items)}
            ${renderPagination(reviewPage)}
          </div>
          <div class="service-reviews-composer">
            ${renderComposer({ isAuthenticated, loginUrl, currentUserReview, submitting: state.submitting })}
          </div>
        </div>
      </div>
    `;

    const alertBox = app.querySelector('#serviceReviewAlert');
    if (state.alert && alertBox) {
      setAlert(alertBox, state.alert.message, state.alert.type);
    } else if (alertBox) {
      clearAlert(alertBox);
    }

    app.querySelectorAll('[data-service-review-page]').forEach(button => {
      button.addEventListener('click', () => {
        const nextPage = Number(button.dataset.serviceReviewPage || 1);
        if (!nextPage || nextPage === reviewPage.current_page) return;
        load(nextPage);
      });
    });

    const form = app.querySelector('[data-service-review-form]');
    if (form) {
      form.addEventListener('submit', async event => {
        event.preventDefault();

        const formData = new FormData(form);
        const rating = Number(formData.get('rating') || 0);

        if (rating < 1 || rating > 5) {
          state.alert = { type: 'danger', message: 'Choose a star rating before submitting your service review.' };
          render();
          return;
        }

        state.submitting = true;
        state.alert = null;
        render();

        try {
          await apiJson('/api/service-review', {
            method: 'POST',
            body: JSON.stringify({
              rating,
              comment: String(formData.get('comment') || '').trim(),
            }),
          });

          state.submitting = false;
          state.alert = { type: 'success', message: currentUserReview ? 'Your testimonial has been updated.' : 'Your testimonial has been published.' };
          await load(1);
        } catch (error) {
          state.submitting = false;
          state.alert = { type: 'danger', message: error.message || 'Could not save your service review.' };
          render();
        }
      });
    }
  }
}

function renderComposer({ isAuthenticated, loginUrl, currentUserReview, submitting }) {
  if (!isAuthenticated) {
    return `
      <div class="service-review-card">
        <h3>Leave a service review</h3>
        <p>Sign in to share how your overall Tech Axis experience felt, from ordering through support.</p>
        <a href="${escapeHtml(loginUrl)}" class="ta-service-review-link">Sign in to review</a>
      </div>
    `;
  }

  return `
    <div class="service-review-card">
      <h3>${currentUserReview ? 'Edit your testimonial' : 'Leave a service review'}</h3>
      <p>Tell other customers what the overall shopping experience was like.</p>
      <form data-service-review-form class="service-review-form">
        <label class="form-label">Your rating</label>
        <div class="review-rating-input" role="radiogroup" aria-label="Choose a rating">
          ${[5, 4, 3, 2, 1].map(star => `
            <label class="review-rating-option">
              <input type="radio" name="rating" value="${star}" ${Number(currentUserReview?.rating || 0) === star ? 'checked' : ''}>
              <span>${'&#9733;'.repeat(star)}</span>
            </label>
          `).join('')}
        </div>

        <label class="form-label" for="serviceReviewComment">Comment</label>
        <textarea id="serviceReviewComment" name="comment" rows="6" maxlength="5000" class="form-control" placeholder="Talk about delivery, communication, packaging, and the overall experience.">${escapeHtml(currentUserReview?.comment || '')}</textarea>

        <button type="submit" class="ta-service-review-link ta-service-review-link-primary" ${submitting ? 'disabled' : ''}>
          ${submitting ? 'Saving...' : (currentUserReview ? 'Update testimonial' : 'Publish testimonial')}
        </button>
      </form>
    </div>
  `;
}

function renderServiceCards(reviews) {
  if (!Array.isArray(reviews) || reviews.length === 0) {
    return `
      <div class="service-review-empty">
        <p>No service reviews yet. Be the first customer to share your experience.</p>
      </div>
    `;
  }

  return reviews.map(review => `
    <article class="service-review-entry">
      <div class="service-review-entry-head">
        <div>
          <strong>${escapeHtml(review.user?.name || 'Customer')}</strong>
          <div class="service-review-entry-date">${escapeHtml(review.created_at_label || '')}</div>
        </div>
        <div class="service-review-entry-stars">${renderStars(review.rating)}</div>
      </div>
      <p>${escapeHtml(review.comment || 'No written feedback provided.')}</p>
    </article>
  `).join('');
}

function renderLoadingState() {
  return `
    <div class="review-loading-state">
      <span>Loading testimonials...</span>
    </div>
  `;
}

function renderPagination(page) {
  if (!page || page.last_page <= 1) return '';

  return `
    <div class="service-review-pagination">
      <button type="button" class="ta-service-review-link" data-service-review-page="${Math.max(1, page.current_page - 1)}" ${page.current_page === 1 ? 'disabled' : ''}>Previous</button>
      <span>Page ${page.current_page} of ${page.last_page}</span>
      <button type="button" class="ta-service-review-link" data-service-review-page="${Math.min(page.last_page, page.current_page + 1)}" ${page.current_page === page.last_page ? 'disabled' : ''}>Next</button>
    </div>
  `;
}

function renderStars(rating) {
  const rounded = Math.round(Number(rating) || 0);
  return new Array(5).fill(0).map((_, index) => `
    <span class="review-star ${index < rounded ? 'is-filled' : ''}">&#9733;</span>
  `).join('');
}
