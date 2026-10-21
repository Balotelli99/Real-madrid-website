const playerGrid = document.getElementById('playerGrid');
const filterGroup = document.getElementById('filterGroup');
let players = [];

function showError() {
  playerGrid.innerHTML = '<p class="error-message" role="alert">Er ging iets mis bij het tonen van de spelers.</p>';
}

window.addEventListener('error', showError);

try {
  players = JSON.parse(document.getElementById('players-data').textContent);
} catch (error) {
  showError();
  throw error;
}

const state = {
  filter: 'all',
  sort: 'goals',
};

const filters = [
  { key: 'all', label: 'Alle' },
  { key: 'GK', label: 'Keeper' },
  { key: 'DEF', label: 'Verdediger' },
  { key: 'MID', label: 'Middenvelder' },
  { key: 'FWD', label: 'Aanvaller' },
];

const flags = {
  FRANKRIJK: '🇫🇷',
  BRAZILIË: '🇧🇷',
  ENGELAND: '🏴',
  BELGIË: '🇧🇪',
  SPANJE: '🇪🇸',
  PORTUGAL: '🇵🇹',
  URUGUAY: '🇺🇾',
};

function getVisiblePlayers() {
  const visiblePlayers = state.filter === 'all'
    ? [...players]
    : players.filter((player) => player.position === state.filter);

  return visiblePlayers.sort((first, second) => second[state.sort] - first[state.sort]);
}

function renderFilters() {
  filterGroup.innerHTML = filters.map((filter) => {
    const count = filter.key === 'all'
      ? players.length
      : players.filter((player) => player.position === filter.key).length;

    return `
      <button type="button" class="filter-button ${state.filter === filter.key ? 'active' : ''}" data-filter="${filter.key}">
        ${filter.label}<span class="count">${count}</span>
      </button>
    `;
  }).join('');
}

function renderCards(visiblePlayers) {
  playerGrid.innerHTML = visiblePlayers.map((player) => {
    const stats = [
      { key: 'goals', value: player.goals, label: 'Goals' },
      { key: 'assists', value: player.assists, label: 'Assists' },
      { key: 'km', value: player.km, label: 'KM' },
    ];
    const maxValue = Math.max(...stats.map((stat) => stat.value || 1));

    return `
      <a class="player-card" href="playerdetail.php?speler=${encodeURIComponent(player.name)}" aria-label="Bekijk speler ${player.name}">
        <img class="card-image" src="${player.image}" alt="${player.name}" />
        <div class="card-overlay"></div>
        <div class="card-badges">
          <span class="badge">#${player.shirt}</span>
          <span class="badge badge--soft">${player.position}</span>
        </div>
        <div class="card-content">
          <div class="card-meta">
            <span>${flags[player.country] || '🌍'}</span>
            <span>${player.country} · ${player.matches} wedstrijden</span>
          </div>
          <div class="card-name">
            <span>${player.firstName}</span>
            <span>${player.lastName}</span>
          </div>
          <div class="stats-grid">
            ${stats.map((stat) => `
              <div class="stat-box ${state.sort === stat.key ? 'active' : ''}">
                <div class="stat-value">${stat.value}</div>
                <div class="stat-label">${stat.label}</div>
                <div class="stat-bar"><span style="width: ${(stat.value / maxValue) * 100}%"></span></div>
              </div>
            `).join('')}
          </div>
        </div>
      </a>
    `;
  }).join('');
}

function updateTotals(visiblePlayers) {
  const goals = visiblePlayers.reduce((total, player) => total + player.goals, 0);
  const assists = visiblePlayers.reduce((total, player) => total + player.assists, 0);
  const kilometers = visiblePlayers.reduce((total, player) => total + player.km, 0);

  document.getElementById('totalGoals').textContent = goals;
  document.getElementById('totalAssists').textContent = assists;
  document.getElementById('totalKm').textContent = `${kilometers.toLocaleString('nl-NL')} km`;
  document.getElementById('totalPlayers').textContent = visiblePlayers.length;
}

function render() {
  const visiblePlayers = getVisiblePlayers();

  renderFilters();
  renderCards(visiblePlayers);
  updateTotals(visiblePlayers);

  document.querySelectorAll('.sort-button').forEach((button) => {
    button.classList.toggle('active', button.dataset.sort === state.sort);
  });
}

document.addEventListener('click', (event) => {
  const filterButton = event.target.closest('.filter-button');
  const sortButton = event.target.closest('.sort-button');

  if (filterButton) {
    state.filter = filterButton.dataset.filter;
    render();
  }

  if (sortButton) {
    state.sort = sortButton.dataset.sort;
    render();
  }
});

render();