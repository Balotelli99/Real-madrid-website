<?php
// ---------------------------
// PHP: data en logica
// ---------------------------
require __DIR__ . '/db.php';
require __DIR__ . '/players.php';

$players = loadPlayers($pdo ?? null);

$topScorer = $players[0] ?? ['name' => 'Geen speler', 'goals' => 0];
$topAssister = $players[0] ?? ['name' => 'Geen speler', 'assists' => 0];
$topDistance = $players[0] ?? ['name' => 'Geen speler', 'km' => 0];

if (!empty($players)) {
    usort($players, static fn (array $a, array $b) => $b['goals'] <=> $a['goals']);
    $topScorer = $players[0];

    usort($players, static fn (array $a, array $b) => $b['assists'] <=> $a['assists']);
    $topAssister = $players[0];

    usort($players, static fn (array $a, array $b) => $b['km'] <=> $a['km']);
    $topDistance = $players[0];
}

$playersJson = json_encode($players, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<!--
  HTML: alleen template / weergave
-->
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Real Madrid | Selectie</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <header class="hero">
      <div class="container">
        <div class="brand-row">
          <img class="club-mark" src="https://upload.wikimedia.org/wikipedia/en/5/5c/Real_Madrid_CF_logo.svg" alt="Real Madrid logo" />
          <div>
            <div class="club-name">Real Madrid C.F.</div>
            <div class="club-season">Seizoen 2025/26</div>
          </div>
        </div>

        <h1 class="hero-title">Selectie<br /><span>statistieken</span></h1>

        <div class="summary-row">
          <div class="summary-card">
            <span>Topscorer</span>
            <strong><?= htmlspecialchars($topScorer['name'] ?? 'Geen speler', ENT_QUOTES, 'UTF-8') ?></strong>
            <small><?= (int) ($topScorer['goals'] ?? 0) ?> goals</small>
          </div>
          <div class="summary-card">
            <span>Meeste assists</span>
            <strong><?= htmlspecialchars($topAssister['name'] ?? 'Geen speler', ENT_QUOTES, 'UTF-8') ?></strong>
            <small><?= (int) ($topAssister['assists'] ?? 0) ?> assists</small>
          </div>
          <div class="summary-card">
            <span>Meeste km</span>
            <strong><?= htmlspecialchars($topDistance['name'] ?? 'Geen speler', ENT_QUOTES, 'UTF-8') ?></strong>
            <small><?= number_format((float) ($topDistance['km'] ?? 0), 1, ',', '.') ?> km</small>
          </div>
        </div>
      </div>
    </header>

    <div class="toolbar">
      <div class="container toolbar-inner">
        <div class="filter-group" id="filterGroup"></div>
        <div class="sort-group">
          <span class="sort-label">Sorteer</span>
          <button class="sort-button active" type="button" data-sort="goals">Goals</button>
          <button class="sort-button" type="button" data-sort="assists">Assists</button>
          <button class="sort-button" type="button" data-sort="km">KM</button>
          <button class="sort-button" type="button" data-sort="matches">Wedstr.</button>
        </div>
      </div>
    </div>

    <main class="container main-content">
      <section class="player-grid" id="playerGrid"></section>

      <section class="totals-grid">
        <div class="total-box">
          <span>Totaal goals</span>
          <strong id="totalGoals">0</strong>
        </div>
        <div class="total-box">
          <span>Totaal assists</span>
          <strong id="totalAssists">0</strong>
        </div>
        <div class="total-box">
          <span>KM gelopen</span>
          <strong id="totalKm">0 km</strong>
        </div>
        <div class="total-box">
          <span>Spelers</span>
          <strong id="totalPlayers">0</strong>
        </div>
      </section>
    </main>

    <footer class="footer">Real Madrid · Hala Madrid</footer>

    <script>
      const players = <?php echo $playersJson; ?>;
      const flagMap = {
        FRANKRIJK: '🇫🇷',
        BRAZILIË: '🇧🇷',
        ENGELAND: '🏴',
        BELGIË: '🇧🇪',
        SPANJE: '🇪🇸',
        PORTUGAL: '🇵🇹',
        URUGUAY: '🇺🇾'
      };

      const filterButtons = [
        { key: 'all', label: 'Alle', count: players.length },
        { key: 'GK', label: 'Keeper', count: players.filter((player) => player.position === 'GK').length },
        { key: 'DEF', label: 'Verdediger', count: players.filter((player) => player.position === 'DEF').length },
        { key: 'MID', label: 'Middenvelder', count: players.filter((player) => player.position === 'MID').length },
        { key: 'FWD', label: 'Aanvaller', count: players.filter((player) => player.position === 'FWD').length }
      ];

      const state = { filter: 'all', sort: 'goals' };
      const playerGrid = document.getElementById('playerGrid');
      const filterGroup = document.getElementById('filterGroup');

      function getVisiblePlayers() {
        const filtered = state.filter === 'all'
          ? [...players]
          : players.filter((player) => player.position === state.filter);

        return filtered.sort((a, b) => {
          if (state.sort === 'goals') return b.goals - a.goals;
          if (state.sort === 'assists') return b.assists - a.assists;
          if (state.sort === 'km') return b.km - a.km;
          return b.matches - a.matches;
        });
      }

      function renderFilters() {
        filterGroup.innerHTML = filterButtons.map((button) => `
          <button type="button" class="filter-button ${state.filter === button.key ? 'active' : ''}" data-filter="${button.key}">
            ${button.label}<span class="count">${button.count}</span>
          </button>
        `).join('');
      }

      function renderCards() {
        const visiblePlayers = getVisiblePlayers();

        playerGrid.innerHTML = visiblePlayers.map((player) => {
          const stats = [
            { key: 'goals', value: player.goals, label: 'Goals' },
            { key: 'assists', value: player.assists, label: 'Assists' },
            { key: 'km', value: player.km, label: 'KM' }
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
                  <span>${flagMap[player.country] || '🌍'}</span>
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

      function updateTotals() {
        const visiblePlayers = getVisiblePlayers();
        document.getElementById('totalGoals').textContent = visiblePlayers.reduce((sum, player) => sum + player.goals, 0);
        document.getElementById('totalAssists').textContent = visiblePlayers.reduce((sum, player) => sum + player.assists, 0);
        document.getElementById('totalKm').textContent = `${visiblePlayers.reduce((sum, player) => sum + player.km, 0).toLocaleString('nl-NL')} km`;
        document.getElementById('totalPlayers').textContent = visiblePlayers.length;
      }

      function render() {
        renderFilters();
        renderCards();
        updateTotals();

        document.querySelectorAll('.filter-button').forEach((button) => {
          button.addEventListener('click', () => {
            state.filter = button.dataset.filter;
            render();
          });
        });

        document.querySelectorAll('.sort-button').forEach((button) => {
          button.addEventListener('click', () => {
            state.sort = button.dataset.sort;
            document.querySelectorAll('.sort-button').forEach((btn) => btn.classList.toggle('active', btn === button));
            render();
          });
        });
      }

      render();
    </script>
  </body>
</html>
