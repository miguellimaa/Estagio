(() => {
  const numberFormatter = new Intl.NumberFormat('pt-BR');

  const counters = document.querySelectorAll('[data-counter]');
  counters.forEach((counter) => {
    const target = parseInt(counter.dataset.counter || '0', 10);
    if (Number.isNaN(target)) {
      counter.textContent = '0';
      return;
    }

    const duration = 900;
    const startTime = performance.now();

    const animateCounter = (now) => {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.round(target * eased);
      counter.textContent = numberFormatter.format(current);

      if (progress < 1) {
        requestAnimationFrame(animateCounter);
      }
    };

    requestAnimationFrame(animateCounter);
  });

  const chartBaseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
      duration: 1100,
      easing: 'easeOutQuart'
    },
    layout: {
      padding: {
        top: 6,
        right: 8,
        bottom: 6,
        left: 8
      }
    },
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          boxWidth: 10,
          boxHeight: 10,
          usePointStyle: true,
          pointStyle: 'circle',
          padding: 16,
          color: '#334155',
          font: {
            size: 12,
            weight: '600'
          }
        }
      },
      tooltip: {
        backgroundColor: '#0f172a',
        borderColor: '#1e293b',
        borderWidth: 1,
        titleColor: '#f8fafc',
        bodyColor: '#e2e8f0',
        padding: 12,
        displayColors: true,
        callbacks: {
          label(context) {
            const value = Number(context.parsed ?? context.raw ?? 0);
            return `${context.label}: ${numberFormatter.format(value)}`;
          }
        }
      }
    }
  };

  const pieCanvas = document.getElementById('grafico');
  if (pieCanvas && window.Chart && window.dashboardPieData) {
    const pieCtx = pieCanvas.getContext('2d');
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: window.dashboardPieData.labels,
        datasets: [{
          data: window.dashboardPieData.values,
          backgroundColor: ['#2F6DB3', '#0F766E', '#D97706', '#B42318', '#475467', '#2563EB', '#6366F1', '#64748B'],
          borderColor: '#ffffff',
          borderWidth: 2,
          hoverOffset: 6,
        }]
      },
      options: {
        ...chartBaseOptions,
        cutout: '62%',
        plugins: {
          ...chartBaseOptions.plugins,
          legend: {
            ...chartBaseOptions.plugins.legend,
            position: 'bottom',
            labels: {
              ...chartBaseOptions.plugins.legend.labels,
              boxWidth: 10,
              padding: 14
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
          borderRadius: 8,
          borderSkipped: false,
          backgroundColor: '#2F6DB3',
          maxBarThickness: 44
        }]
      },
      options: {
        ...chartBaseOptions,
        plugins: {
          ...chartBaseOptions.plugins,
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: '#64748b',
              font: { size: 11, weight: '600' }
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
              color: '#64748b',
              font: { size: 11 },
              callback(value) {
                return numberFormatter.format(Number(value));
              }
            },
            grid: {
              color: '#e2e8f0',
              drawBorder: false
            }
          }
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
