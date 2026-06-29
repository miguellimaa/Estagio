(() => {
  const counters = document.querySelectorAll('[data-counter]');
  counters.forEach((counter) => {
    const target = parseInt(counter.dataset.counter || '0', 10);
    let current = 0;
    const increment = Math.max(1, Math.ceil(target / 35));
    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        counter.textContent = String(target);
        clearInterval(timer);
        return;
      }
      counter.textContent = String(current);
    }, 22);
  });

  const pieCanvas = document.getElementById('grafico');
  if (pieCanvas && window.Chart && window.dashboardPieData) {
    const pieCtx = pieCanvas.getContext('2d');
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: window.dashboardPieData.labels,
        datasets: [{
          data: window.dashboardPieData.values,
          backgroundColor: ['#2563EB', '#10B981', '#F59E0B', '#EF4444', '#4F46E5', '#0EA5E9'],
          borderWidth: 0,
          hoverOffset: 12,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        animation: {
          duration: 1200,
          easing: 'easeOutQuart'
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 10,
              usePointStyle: true,
              padding: 16
            }
          }
        }
      }
    });
  }

  const barCanvas = document.getElementById('graficoBarras');
  if (barCanvas && window.Chart && window.dashboardBarData) {
    const barCtx = barCanvas.getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: window.dashboardBarData.labels,
        datasets: [{
          label: 'Quantidade',
          data: window.dashboardBarData.values,
          borderRadius: 10,
          backgroundColor: '#2563EB'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 1100,
          easing: 'easeOutQuart'
        },
        scales: {
          x: {
            grid: { display: false }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#e5e7eb' }
          }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });
  }

  const globalSearchInput = document.querySelector('[data-global-search]');
  const searchItems = Array.isArray(window.dashboardSearchItems) ? window.dashboardSearchItems : [];

  if (globalSearchInput && searchItems.length > 0) {
    const resultCard = document.getElementById('dashboard-search-result');
    const nomeEl = document.getElementById('search-result-nome');
    const tipoEl = document.getElementById('search-result-tipo');
    const qtdEl = document.getElementById('search-result-quantidade');
    const serieEl = document.getElementById('search-result-serie');
    const licencaEl = document.getElementById('search-result-licenca');
    const idEl = document.getElementById('search-result-id');

    const suggestionBox = document.createElement('div');
    suggestionBox.className = 'search-suggestions';
    suggestionBox.style.position = 'absolute';
    suggestionBox.style.top = '100%';
    suggestionBox.style.left = '0';
    suggestionBox.style.right = '0';
    suggestionBox.style.marginTop = '6px';
    suggestionBox.style.background = '#fff';
    suggestionBox.style.border = '1px solid #e5e7eb';
    suggestionBox.style.borderRadius = '12px';
    suggestionBox.style.boxShadow = '0 8px 24px rgba(0,0,0,.08)';
    suggestionBox.style.zIndex = '1200';
    suggestionBox.style.maxHeight = '260px';
    suggestionBox.style.overflowY = 'auto';
    suggestionBox.style.display = 'none';

    globalSearchInput.parentElement.appendChild(suggestionBox);

    const fillDetails = (item) => {
      if (!item || !resultCard) return;
      nomeEl.textContent = item.nome_ativo || '-';
      tipoEl.textContent = item.tipo || '-';
      qtdEl.textContent = item.quantidade ?? '-';
      serieEl.textContent = item.numero_serie || '-';
      licencaEl.textContent = item.chave_licenca || '-';
      idEl.textContent = item.id ?? '-';
      resultCard.style.display = 'block';
      resultCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const renderSuggestions = (term) => {
      const search = term.trim().toLowerCase();
      suggestionBox.innerHTML = '';

      if (!search) {
        suggestionBox.style.display = 'none';
        return;
      }

      const matches = searchItems.filter((item) => {
        const nome = String(item?.nome_ativo ?? '').toLowerCase();
        const tipo = String(item?.tipo ?? '').toLowerCase();
        const serie = String(item?.numero_serie ?? '').toLowerCase();
        const licenca = String(item?.chave_licenca ?? '').toLowerCase();
        return nome.includes(search) || tipo.includes(search) || serie.includes(search) || licenca.includes(search);
      }).slice(0, 8);

      if (matches.length === 0) {
        suggestionBox.style.display = 'none';
        return;
      }

      matches.forEach((item) => {
        const option = document.createElement('button');
        option.type = 'button';
        option.style.width = '100%';
        option.style.border = 'none';
        option.style.background = '#fff';
        option.style.padding = '10px 12px';
        option.style.textAlign = 'left';
        option.style.cursor = 'pointer';
        option.innerHTML = `<strong>${String(item?.nome_ativo ?? '-')}</strong><div style="font-size:.78rem;color:#6b7280;">${String(item?.tipo ?? '-')} · Série: ${String(item?.numero_serie ?? '-')}</div>`;

        option.addEventListener('mouseenter', () => {
          option.style.background = '#f8fafc';
        });
        option.addEventListener('mouseleave', () => {
          option.style.background = '#fff';
        });

        option.addEventListener('click', () => {
          globalSearchInput.value = String(item?.nome_ativo ?? '');
          suggestionBox.style.display = 'none';
          fillDetails(item);
        });

        suggestionBox.appendChild(option);
      });

      suggestionBox.style.display = 'block';
    };

    globalSearchInput.addEventListener('input', (event) => {
      renderSuggestions(event.target.value);
    });

    globalSearchInput.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      const search = globalSearchInput.value.trim().toLowerCase();
      if (!search) return;

      const exact = searchItems.find((item) => String(item?.nome_ativo ?? '').toLowerCase() === search);
      if (exact) {
        suggestionBox.style.display = 'none';
        fillDetails(exact);
        return;
      }

      const partial = searchItems.find((item) => {
        const nome = String(item?.nome_ativo ?? '').toLowerCase();
        const tipo = String(item?.tipo ?? '').toLowerCase();
        return nome.includes(search) || tipo.includes(search);
      });

      if (partial) {
        suggestionBox.style.display = 'none';
        fillDetails(partial);
      }
    });

    document.addEventListener('click', (event) => {
      if (!globalSearchInput.parentElement.contains(event.target)) {
        suggestionBox.style.display = 'none';
      }
    });
  }
})();
