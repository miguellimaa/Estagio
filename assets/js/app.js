(() => {
  const sidebar = document.querySelector('[data-sidebar]');
  const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
  const loadingScreen = document.querySelector('[data-loading-screen]');
  const toastHost = document.querySelector('[data-toast-host]');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }

  if (loadingScreen) {
    window.addEventListener('load', () => {
      loadingScreen.classList.add('hidden');
      setTimeout(() => loadingScreen.remove(), 350);
    });
  }

  const breadcrumbCurrent = document.querySelector('[data-breadcrumb-current]');
  if (breadcrumbCurrent) {
    const parts = window.location.pathname.split('/').filter(Boolean);
    const current = parts[parts.length - 1] || 'dashboard';
    const clean = current.replace('.php', '').replace(/[-_]/g, ' ');
    breadcrumbCurrent.textContent = clean.charAt(0).toUpperCase() + clean.slice(1);
  }

  window.irtradeToast = (message, type = 'info') => {
    if (!toastHost || !message) return;
    const toast = document.createElement('div');
    toast.className = `app-toast ${type}`;
    toast.textContent = message;
    toastHost.appendChild(toast);
    setTimeout(() => toast.remove(), 3600);
  };

  const flashElement = document.querySelector('[data-flash]');
  if (flashElement?.dataset.message) {
    window.irtradeToast(flashElement.dataset.message, flashElement.dataset.type || 'info');
  }

  document.querySelectorAll('.btn-ripple').forEach((button) => {
    button.classList.add('ripple-host');
    button.addEventListener('click', (event) => {
      const ripple = document.createElement('span');
      const rect = button.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      ripple.className = 'ripple';
      ripple.style.width = `${size}px`;
      ripple.style.height = `${size}px`;
      ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
      ripple.style.top = `${event.clientY - rect.top - size / 2}px`;
      button.appendChild(ripple);
      ripple.addEventListener('animationend', () => ripple.remove());
    });
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, { threshold: 0.15 });

  document.querySelectorAll('.reveal-on-scroll').forEach((element) => observer.observe(element));
})();
