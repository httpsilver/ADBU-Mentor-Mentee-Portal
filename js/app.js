
(function() {
  const isMobile = window.innerWidth <= 768 ||
                   ('ontouchstart' in window && window.innerWidth <= 1024);
  if (!isMobile) return;
  const depth = (window.location.pathname.match(/\//g) || []).length;
  const base  = depth >= 2 ? '../css/' : 'css/';
  const link  = document.createElement('link');
  link.rel  = 'stylesheet';
  link.id   = 'mobileCssLink';
  link.href = base + 'styles-mobile.css';
  document.head.appendChild(link);
  document.documentElement.setAttribute('data-layout', 'mobile');
})();

// ── Theme 
const ThemeManager = {
  KEY: 'mp_theme',
  init() {
    const saved = localStorage.getItem(this.KEY) || 'light';
    this.set(saved, false);
  },
  toggle() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    this.set(current === 'light' ? 'dark' : 'light');
  },
  set(theme, animate = true) {
    if (animate) document.body.style.transition = 'background-color 0.3s, color 0.3s';
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(this.KEY, theme);
    
    document.querySelectorAll('.theme-icon-btn').forEach(btn => {
      btn.innerHTML = theme === 'dark'
        ? '<i class="bi bi-sun-fill"></i>'
        : '<i class="bi bi-moon-fill"></i>';
      btn.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    });
    
    document.querySelectorAll('.theme-toggle').forEach(btn => {
      btn.classList.toggle('on', theme === 'dark');
      const icon = btn.querySelector('i');
      if (icon) { icon.className = theme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill'; }
    });
    document.querySelectorAll('[data-theme-label]').forEach(el => {
      el.textContent = theme === 'dark' ? 'Dark Mode' : 'Light Mode';
    });
  }
};

// ── Sidebar 
const Sidebar = {
  collapsed: false,
  init() {
    this.collapsed = localStorage.getItem('mp_sidebar') === 'collapsed';
    if (this.collapsed) this.apply();
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) toggleBtn.addEventListener('click', () => this.toggle());
    const backdrop = document.getElementById('sidebarBackdrop');
    if (backdrop) backdrop.addEventListener('click', () => this.closeMobile());
  },
  toggle() {
    if (window.innerWidth < 992) {
      this.toggleMobile();
    } else {
      this.collapsed = !this.collapsed;
      localStorage.setItem('mp_sidebar', this.collapsed ? 'collapsed' : 'expanded');
      this.apply();
    }
  },
  apply() {
    const sidebar = document.getElementById('mainSidebar');
    const wrapper = document.getElementById('mainWrapper');
    if (sidebar) sidebar.classList.toggle('collapsed', this.collapsed);
    if (wrapper) wrapper.classList.toggle('expanded', this.collapsed);
  },
  toggleMobile() {
    const sidebar = document.getElementById('mainSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.toggle('mobile-open');
    if (backdrop) backdrop.classList.toggle('show');
  },
  closeMobile() {
    const sidebar = document.getElementById('mainSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.remove('mobile-open');
    if (backdrop) backdrop.classList.remove('show');
  }
};

// ── API Helper 
const API = {
  async call(url, options = {}) {
    try {
      const res = await fetch(url, {
        headers: { 'Content-Type': 'application/json', ...options.headers },
        ...options,
        body: options.body ? (typeof options.body === 'string' ? options.body : JSON.stringify(options.body)) : undefined
      });
      const data = await res.json();
      return data;
    } catch (err) {
      console.error('API error:', err);
      return { success: false, message: 'Network error. Please try again.' };
    }
  },
  get(url)       { return this.call(url); },
  post(url, body){ return this.call(url, { method: 'POST', body }); },
};

// ── Toast Notifications 
const Toast = {
  container: null,
  init() {
    this.container = document.createElement('div');
    this.container.id = 'toast-container';
    Object.assign(this.container.style, {
      position: 'fixed', bottom: '24px', right: '24px',
      display: 'flex', flexDirection: 'column', gap: '10px',
      zIndex: '9999', maxWidth: '340px'
    });
    document.body.appendChild(this.container);
  },
  show(message, type = 'success', duration = 3500) {
    if (!this.container) this.init();
    const colors = {
      success: { bg: '#D1FAE5', color: '#065F46', border: '#6EE7B7', icon: 'bi-check-circle-fill' },
      error:   { bg: '#FEE2E2', color: '#991B1B', border: '#FECACA', icon: 'bi-x-circle-fill' },
      info:    { bg: '#EEF2FF', color: '#3730A3', border: '#C7D2FE', icon: 'bi-info-circle-fill' },
      warning: { bg: '#FEF3C7', color: '#92400E', border: '#FDE68A', icon: 'bi-exclamation-triangle-fill' },
    };
    const c = colors[type] || colors.success;
    const toast = document.createElement('div');
    toast.style.cssText = `background:${c.bg};color:${c.color};border:1px solid ${c.border};border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;font-size:14px;font-family:Inter,sans-serif;box-shadow:0 4px 16px rgba(0,0,0,0.1);animation:fadeIn 0.3s ease;`;
    toast.innerHTML = `<i class="bi ${c.icon}" style="font-size:16px;flex-shrink:0"></i><span style="flex:1">${message}</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;font-size:16px;cursor:pointer;padding:0;opacity:0.6">×</button>`;
    this.container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'fadeInDown 0.3s ease reverse'; setTimeout(() => toast.remove(), 300); }, duration);
  },
  success(msg) { this.show(msg, 'success'); },
  error(msg)   { this.show(msg, 'error'); },
  info(msg)    { this.show(msg, 'info'); },
};

// ── Modal 
const Modal = {
  open(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('show'); document.body.style.overflow = 'hidden'; }
  },
  close(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('show'); document.body.style.overflow = ''; }
  },
  init() {
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
      btn.addEventListener('click', () => this.open(btn.dataset.modalOpen));
    });
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
      btn.addEventListener('click', () => this.close(btn.dataset.modalClose));
    });
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', e => {
        if (e.target === overlay) this.close(overlay.id);
      });
    });
  }
};

// ── Profile Dropdown 
function initProfileDropdown() {
  const btn = document.getElementById('profileDropBtn');
  const menu = document.getElementById('profileDropMenu');
  if (!btn || !menu) return;
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    menu.classList.toggle('show');
  });
  document.addEventListener('click', () => menu.classList.remove('show'));
}

// ── Format date/time
function formatTime(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const now  = new Date();
  const diff = now - date;
  if (diff < 60000) return 'Just now';
  if (diff < 3600000) return Math.floor(diff/60000) + 'm ago';
  if (diff < 86400000) return date.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
  if (diff < 604800000) return date.toLocaleDateString([], {weekday:'short'});
  return date.toLocaleDateString([], {month:'short', day:'numeric'});
}

function formatDateTime(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString([], { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
}

// ── Avatar initials fallback
function getInitials(name) {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

// ── Confirm dialog 
function confirmAction(message, callback) {
  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(4px);z-index:9998;display:flex;align-items:center;justify-content:center;';
  overlay.innerHTML = `
    <div style="background:var(--card);border-radius:16px;padding:32px;max-width:400px;width:90%;box-shadow:var(--shadow-lg);text-align:center;font-family:Inter,sans-serif;">
      <div style="font-size:36px;margin-bottom:12px">⚠️</div>
      <h3 style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:8px">Are you sure?</h3>
      <p style="font-size:14px;color:var(--text-muted);margin-bottom:24px">${message}</p>
      <div style="display:flex;gap:12px;justify-content:center">
        <button id="confirmNo" style="padding:10px 24px;border-radius:8px;border:1px solid var(--border);background:none;color:var(--text);font-size:14px;cursor:pointer;font-family:Inter,sans-serif">Cancel</button>
        <button id="confirmYes" style="padding:10px 24px;border-radius:8px;border:none;background:#EF4444;color:white;font-size:14px;font-weight:600;cursor:pointer;font-family:Inter,sans-serif">Confirm</button>
      </div>
    </div>`;
  document.body.appendChild(overlay);
  document.getElementById('confirmYes').onclick = () => { document.body.removeChild(overlay); callback(); };
  document.getElementById('confirmNo').onclick = () => document.body.removeChild(overlay);
}

// ── Button loading state
function setButtonLoading(btn, loading, label = '') {
  if (loading) {
    btn.dataset.origText = btn.innerHTML;
    btn.innerHTML = `<span class="loading-spinner"></span> ${label || 'Loading...'}`;
    btn.disabled = true;
  } else {
    btn.innerHTML = btn.dataset.origText || label;
    btn.disabled = false;
  }
}

// ── CSS Switcher: mobile vs desktop 
const CSSManager = {
  MOBILE_BREAKPOINT: 768,
  linkEl: null,
  currentIsMobile: null,

  // Detect true mobile: narrow screen OR touch device
  isMobile() {
    return window.innerWidth <= this.MOBILE_BREAKPOINT ||
           ('ontouchstart' in window && window.innerWidth <= 1024);
  },

  
  cssPath() {
    const depth = (window.location.pathname.match(/\//g) || []).length;
    
    return depth >= 2 ? '../css/' : 'css/';
  },

  init() {
    this.linkEl = document.getElementById('mobileCssLink');
    if (!this.linkEl) {
      this.linkEl = document.createElement('link');
      this.linkEl.rel  = 'stylesheet';
      this.linkEl.id   = 'mobileCssLink';
      document.head.appendChild(this.linkEl);
    }
    this.apply();

  
    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => this.apply(), 150);
    });
    
    window.addEventListener('orientationchange', () => {
      setTimeout(() => this.apply(), 300);
    });
  },

  apply() {
    const mobile = this.isMobile();
    if (mobile === this.currentIsMobile) return; 
    this.currentIsMobile = mobile;

    if (mobile) {
      this.linkEl.href = this.cssPath() + 'styles-mobile.css';
      document.documentElement.setAttribute('data-layout', 'mobile');
    } else {
      this.linkEl.href = '';   
      document.documentElement.removeAttribute('data-layout');
    }
  }
};
document.addEventListener('DOMContentLoaded', () => {
  CSSManager.init();
  ThemeManager.init();
  Sidebar.init();
  Modal.init();
  initProfileDropdown();
  Toast.init();
  SmartFooter.init();

  // Theme toggle buttons 
  document.querySelectorAll('.theme-toggle').forEach(btn => {
    btn.addEventListener('click', () => ThemeManager.toggle());
  });
  // Icon-style theme buttons (navbar)
  document.querySelectorAll('.theme-icon-btn').forEach(btn => {
    btn.addEventListener('click', () => ThemeManager.toggle());
  });
});


const SmartFooter = {
  el: null,
  visible: false,
  lastScrollY: 0,
  touchStartY: 0,

  init() {
    this.el = document.getElementById('smartFooter');
    if (!this.el) return;
    this.lastScrollY = window.scrollY;

    window.addEventListener('scroll',     this._onScroll.bind(this),     { passive: true });
    window.addEventListener('wheel',      this._onWheel.bind(this),      { passive: true });
    window.addEventListener('touchstart', this._onTouchStart.bind(this), { passive: true });
    window.addEventListener('touchmove',  this._onTouchMove.bind(this),  { passive: true });
  },

  _isAtBottom() {
    return Math.ceil(window.scrollY + window.innerHeight) >= document.documentElement.scrollHeight - 2;
  },

  _isShortPage() {
    return document.documentElement.scrollHeight <= window.innerHeight + 2;
  },

  _onScroll() {
    const currentY = window.scrollY;
    const goingDown = currentY > this.lastScrollY;
    this.lastScrollY = currentY;

    if (!goingDown) { this.hide(); return; }
    if (this._isAtBottom()) this.show();
  },

  _onWheel(e) {
    if (e.deltaY <= 0) { this.hide(); return; }
    if (this._isShortPage() || this._isAtBottom()) this.show();
  },

  _onTouchStart(e) {
    this.touchStartY = e.touches[0].clientY;
  },

  _onTouchMove(e) {
    const delta = this.touchStartY - e.touches[0].clientY;
    if (delta < 8) { this.hide(); return; }
    if (this._isShortPage() || this._isAtBottom()) this.show();
  },

  show() {
    if (this.visible) return;
    this.visible = true;
    this.el.classList.add('is-visible');
    document.getElementById('mainWrapper')?.classList.add('footer-open');
    document.querySelector('.app-layout')?.classList.add('footer-open');
  },

  hide() {
    if (!this.visible) return;
    this.visible = false;
    this.el.classList.remove('is-visible');
    document.getElementById('mainWrapper')?.classList.remove('footer-open');
    document.querySelector('.app-layout')?.classList.remove('footer-open');
  }
};
