// Minimal, robust resolver
(function () {
    const API_BASE = document.documentElement.dataset.kbApiBase || '/api';
    const MAILTO_URL = `${API_BASE}/mailto`;
  
    if (window.__kbMailtoBound) return;
    window.__kbMailtoBound = true;
  
    const cache = new Map();
    const busy = new WeakSet();
  
    async function resolveToken(token) {
      if (!token) return null;
      if (cache.has(token)) return cache.get(token);
  
      const res = await fetch(`${MAILTO_URL}?token=${encodeURIComponent(token)}`, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      }).catch(() => null);
  
      if (!res) return null;
      let data = null;
      try { data = await res.json(); } catch (e) {}
      const email = res.ok && data && data.email ? data.email : null;
      if (email) cache.set(token, email);
      return email;
    }
  
    document.addEventListener('click', async (e) => {
      const a = e.target.closest('a.js-mailto');
      if (!a) return;
  
      // respect new-tab/modifier clicks and right-click
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
  
      // already resolved?
      if (a.href && a.href.startsWith('mailto:')) return;
  
      if (busy.has(a)) { e.preventDefault(); return; }
      busy.add(a);
  
      e.preventDefault();
      try {
        const token = a.dataset.mailtoToken;
        const email = await resolveToken(token);
        if (!email) throw new Error('resolve failed');
        a.href = `mailto:${email}`;
        window.location.href = a.href; // avoid synthetic a.click()
      } catch (err) {
        console.error('Email resolving failed:', err);
        alert('Sorry, could not open email.');
      } finally {
        busy.delete(a);
      }
    }, { capture: true });
  })();