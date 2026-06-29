// IRTRADE custom JS: theme toggle, micro animations
(function(){
  const btn = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');
  const saved = localStorage.getItem('irtrade_theme');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const enableDark = saved ? saved === 'dark' : prefersDark;

  function applyState(isDark){
    if(isDark) document.documentElement.classList.add('dark-mode');
    else document.documentElement.classList.remove('dark-mode');
    if(icon) icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    if(btn) btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
  }

  applyState(enableDark);

  if(btn) btn.addEventListener('click', () => {
    const isDark = !document.documentElement.classList.contains('dark-mode');
    applyState(isDark);
    localStorage.setItem('irtrade_theme', isDark ? 'dark' : 'light');
    btn.animate([
      { transform: 'scale(1)' },
      { transform: 'scale(1.08)' },
      { transform: 'scale(1)' }
    ], { duration: 260, easing: 'ease-out' });
  });

  // Add subtle hover animations to actionable elements
  document.querySelectorAll('.btn, .nav-link').forEach(el => {
    el.addEventListener('mouseenter', () => el.classList.add('hovering'));
    el.addEventListener('mouseleave', () => el.classList.remove('hovering'));
  });

})();
