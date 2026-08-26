(() => {
  const table = document.querySelector('[data-table]');
  const searchInput = document.querySelector('[data-table-search]');
  const pageInfo = document.querySelector('[data-page-info]');
  const prevBtn = document.querySelector('[data-page-prev]');
  const nextBtn = document.querySelector('[data-page-next]');

  if (!table) return;

  const rows = Array.from(table.querySelectorAll('tbody tr'));
  let filteredRows = [...rows];
  let page = 1;
  const perPageFromData = Number.parseInt(table.dataset.perPage || '', 10);
  const perPage = Number.isFinite(perPageFromData) && perPageFromData > 0 ? perPageFromData : 8;

  const render = () => {
    const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));
    page = Math.min(page, totalPages);

    rows.forEach((row) => {
      row.style.display = 'none';
    });

    const start = (page - 1) * perPage;
    filteredRows.slice(start, start + perPage).forEach((row) => {
      row.style.display = '';
    });

    if (pageInfo) {
      pageInfo.textContent = `Pagina ${page} de ${totalPages} • ${filteredRows.length} itens`;
    }

    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = page >= totalPages;
  };

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const term = searchInput.value.toLowerCase().trim();
      filteredRows = rows.filter((row) => row.innerText.toLowerCase().includes(term));
      page = 1;
      render();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      page -= 1;
      render();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      page += 1;
      render();
    });
  }

  render();
})();
