(() => {
  const box = document.getElementById('messages');
  if (!box) return;

  const loader = document.getElementById('messagesLoader');
  const url = box.dataset.messagesUrl;

  let oldestId = box.dataset.oldestId ? Number(box.dataset.oldestId) : null;
  let hasMore = box.dataset.hasMore === '1';
  let loading = false;

  const escapeHtml = (s) =>
    String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');

  const renderMsg = (m) => {
    const div = document.createElement('div');
    div.className = `msg ${m.is_me ? 'me' : 'them'}`;
    div.dataset.id = m.id;

    const created = m.created_at ? new Date(m.created_at) : null;
    const when = created ? created.toLocaleString() : '';

    div.innerHTML = `
      <div class="msg-meta">${escapeHtml(m.sender_name)} · ${escapeHtml(when)}</div>
      <div class="msg-body">${escapeHtml(m.body).replaceAll('\n', '<br>')}</div>
    `;
    return div;
  };

  const setLoader = (show) => {
    if (!loader) return;
    loader.style.display = show ? 'block' : 'none';
  };

  const loadOlder = async () => {
    if (!hasMore || loading || !oldestId) return;

    loading = true;
    setLoader(true);

    // Keep scroll position stable when prepending
    const prevScrollHeight = box.scrollHeight;
    const prevScrollTop = box.scrollTop;

    try {
      const fetchUrl = `${url}?limit=25&before_id=${encodeURIComponent(oldestId)}`;
      const res = await fetch(fetchUrl, { headers: { Accept: 'application/json' } });
      if (!res.ok) throw new Error(`Failed to load messages (${res.status})`);

      const data = await res.json();
      const items = Array.isArray(data?.items) ? data.items : [];

      if (items.length === 0) {
        hasMore = false;
        setLoader(false);
        loading = false;
        return;
      }

      // Prepend in order
      const frag = document.createDocumentFragment();
      items.forEach((m) => frag.appendChild(renderMsg(m)));

      // Insert after loader
      box.insertBefore(frag, loader.nextSibling);

      oldestId = data.oldest_id ? Number(data.oldest_id) : oldestId;
      hasMore = !!data.has_more;

      // Restore scroll so content doesn't "jump"
      const newScrollHeight = box.scrollHeight;
      box.scrollTop = prevScrollTop + (newScrollHeight - prevScrollHeight);
    } catch (e) {
      // If it fails, stop spamming requests but let user keep using the page
      hasMore = false;
    } finally {
      setLoader(hasMore);
      loading = false;
    }
  };

  // Start at bottom (latest messages)
  const scrollToBottom = () => {
    box.scrollTop = box.scrollHeight;
  };

  // Only show loader if there are older messages
  setLoader(hasMore);

  // Scroll event: when user reaches the top, fetch older chunk
  box.addEventListener('scroll', () => {
    if (box.scrollTop <= 20) loadOlder();
  });

  // On initial load, go bottom
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scrollToBottom);
  } else {
    scrollToBottom();
  }
})();
