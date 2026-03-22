const searchInput = document.getElementById('productsSearchInput');

if (searchInput) {
  initSearchExamples(searchInput);
  initSearchBuilder(searchInput);
  initPriceFilter();
}

function initSearchExamples(searchField) {
  const exampleButtons = [...document.querySelectorAll('[data-search-example]')];
  const form = searchField.closest('form');

  for (const button of exampleButtons) {
    button.addEventListener('click', () => {
      searchField.value = button.dataset.searchExample || '';
      form?.requestSubmit();
    });
  }
}

function initSearchBuilder(searchField) {
  const builderRoot = document.querySelector('[data-search-builder]');
  const preview = document.querySelector('[data-builder-preview]');
  const applyButton = document.querySelector('[data-builder-apply]');
  const clearButton = document.querySelector('[data-builder-clear]');
  const copyButton = document.querySelector('[data-builder-copy-preview]');
  const modalElement = document.getElementById('advancedSearchBuilderModal');
  const form = searchField.closest('form');

  if (!builderRoot || !preview || !applyButton || !clearButton || !modalElement || !form) {
    return;
  }

  const fields = {
    include: builderRoot.querySelector('[data-builder-field="include"]'),
    any: builderRoot.querySelector('[data-builder-field="any"]'),
    phrase: builderRoot.querySelector('[data-builder-field="phrase"]'),
    exclude: builderRoot.querySelector('[data-builder-field="exclude"]'),
  };

  const updatePreview = () => {
    const query = buildQuery({
      include: splitCommaList(fields.include?.value),
      any: splitCommaList(fields.any?.value),
      phrase: sanitizeToken(fields.phrase?.value || ''),
      exclude: splitCommaList(fields.exclude?.value),
    });

    preview.value = query;
    applyButton.disabled = query.length === 0;
  };

  for (const input of Object.values(fields)) {
    input?.addEventListener('input', updatePreview);
  }

  modalElement.addEventListener('shown.bs.modal', () => {
    updatePreview();
    fields.include?.focus();
  });

  clearButton.addEventListener('click', () => {
    for (const input of Object.values(fields)) {
      if (input) input.value = '';
    }
    updatePreview();
    fields.include?.focus();
  });

  applyButton.addEventListener('click', () => {
    const query = preview.value.trim();
    searchField.value = query;
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    modal.hide();
    form.requestSubmit();
  });

  copyButton?.addEventListener('click', async () => {
    const query = preview.value.trim();
    if (!query) return;

    try {
      await navigator.clipboard.writeText(query);
      copyButton.textContent = 'Copied';
      window.setTimeout(() => {
        copyButton.textContent = 'Copy preview';
      }, 1400);
    } catch (_error) {
      preview.focus();
      preview.select();
    }
  });

  updatePreview();
}

function initPriceFilter() {
  const panel = document.querySelector('[data-price-filter]');
  if (!panel) return;

  const toggle = panel.querySelector('[data-price-mode-toggle]');
  const modeInput = panel.querySelector('[data-price-mode-input]');
  const valuesPanel = panel.querySelector('[data-price-values-panel]');
  const sliderPanel = panel.querySelector('[data-price-slider-panel]');
  const minInput = panel.querySelector('[data-price-input-min]');
  const maxInput = panel.querySelector('[data-price-input-max]');
  const minSlider = panel.querySelector('[data-price-slider-min]');
  const maxSlider = panel.querySelector('[data-price-slider-max]');
  const summary = panel.querySelector('[data-price-slider-summary]');
  const track = panel.querySelector('[data-price-slider-track]');

  if (!toggle || !modeInput || !valuesPanel || !sliderPanel || !minInput || !maxInput || !minSlider || !maxSlider || !summary || !track) {
    return;
  }

  const floor = Number(panel.dataset.priceFloor || 0);
  const ceiling = Number(panel.dataset.priceCeiling || 1000);

  const currencyFormatter = new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  const clamp = (value) => Math.min(ceiling, Math.max(floor, Number(value)));

  const setMode = (mode) => {
    const sliderMode = mode === 'slider';
    modeInput.value = sliderMode ? 'slider' : 'values';
    toggle.checked = sliderMode;
    valuesPanel.classList.toggle('d-none', sliderMode);
    sliderPanel.classList.toggle('d-none', !sliderMode);

    if (sliderMode) {
      syncFromSliders();
    }
  };

  const renderTrack = (minValue, maxValue) => {
    const total = Math.max(1, ceiling - floor);
    const start = ((minValue - floor) / total) * 100;
    const end = ((maxValue - floor) / total) * 100;
    track.style.setProperty('--range-start', `${start}%`);
    track.style.setProperty('--range-end', `${end}%`);
    summary.textContent = `${currencyFormatter.format(minValue)} - ${currencyFormatter.format(maxValue)}`;
  };

  const syncFromInputs = (lastChanged = 'min') => {
    const minBlank = minInput.value.trim() === '';
    const maxBlank = maxInput.value.trim() === '';

    let minValue = minBlank ? floor : clamp(minInput.value);
    let maxValue = maxBlank ? ceiling : clamp(maxInput.value);

    if (minValue > maxValue) {
      if (lastChanged === 'max') {
        minValue = maxValue;
        if (!minBlank) minInput.value = formatInputValue(minValue);
      } else {
        maxValue = minValue;
        if (!maxBlank) maxInput.value = formatInputValue(maxValue);
      }
    }

    minSlider.value = String(Math.round(minValue));
    maxSlider.value = String(Math.round(maxValue));
    renderTrack(minValue, maxValue);
  };

  const syncFromSliders = (lastChanged = 'min') => {
    let minValue = clamp(minSlider.value);
    let maxValue = clamp(maxSlider.value);

    if (minValue > maxValue) {
      if (lastChanged === 'max') {
        minValue = maxValue;
        minSlider.value = String(Math.round(minValue));
      } else {
        maxValue = minValue;
        maxSlider.value = String(Math.round(maxValue));
      }
    }

    minInput.value = formatInputValue(minValue);
    maxInput.value = formatInputValue(maxValue);
    renderTrack(minValue, maxValue);
  };

  toggle.addEventListener('change', () => {
    setMode(toggle.checked ? 'slider' : 'values');
  });

  minInput.addEventListener('input', () => syncFromInputs('min'));
  maxInput.addEventListener('input', () => syncFromInputs('max'));
  minSlider.addEventListener('input', () => syncFromSliders('min'));
  maxSlider.addEventListener('input', () => syncFromSliders('max'));

  syncFromInputs();
  setMode(modeInput.value);
}

function buildQuery({ include, any, phrase, exclude }) {
  const parts = [];

  for (const term of include) {
    parts.push(formatQueryToken(term));
  }

  if (phrase) {
    parts.push(`"${phrase}"`);
  }

  if (any.length === 1) {
    parts.push(formatQueryToken(any[0]));
  } else if (any.length > 1) {
    parts.push(`(${any.map(formatQueryToken).join(' OR ')})`);
  }

  for (const term of exclude) {
    parts.push(`-${formatQueryToken(term)}`);
  }

  return parts.join(' ').trim();
}

function splitCommaList(rawValue) {
  return String(rawValue || '')
    .split(',')
    .map(value => sanitizeToken(value))
    .filter(Boolean);
}

function sanitizeToken(value) {
  return String(value || '').replaceAll('"', '').trim();
}

function formatQueryToken(value) {
  const token = sanitizeToken(value);
  if (!token) return '';
  return /\s/.test(token) ? `"${token}"` : token;
}

function formatInputValue(value) {
  const normalized = Number(value);
  if (!Number.isFinite(normalized)) return '';
  return Number.isInteger(normalized) ? String(normalized) : normalized.toFixed(2);
}
