<?php
require __DIR__ . '/db.php';

$players = [];

$normalizePlayers = static function (array $rows): array {
  $map = [
    'Aanvaller' => 'FWD',
    'Middenvelder' => 'MID',
    'Verdediger' => 'DEF',
    'Keeper' => 'GK'
  ];

  return array_map(static function (array $player) use ($map): array {
    $name = trim((string) ($player['name'] ?? ''));
    $parts = preg_split('/\s+/', $name, 2);
    $firstName = $player['firstName'] ?? ($parts[0] ?? '');
    $lastName = $player['lastName'] ?? ($parts[1] ?? '');

    return [
      'name' => $name,
      'firstName' => $firstName,
      'lastName' => $lastName,
      'position' => $map[$player['position'] ?? ''] ?? ($player['position'] ?? 'FWD'),
      'country' => $player['country'] ?? 'NEDERLAND',
      'matches' => (int) ($player['matches'] ?? 0),
      'goals' => (int) ($player['goals'] ?? 0),
      'assists' => (int) ($player['assists'] ?? 0),
      'km' => (float) ($player['kilometers'] ?? $player['km'] ?? 0),
      'shirt' => (int) ($player['shirt'] ?? 0),
      'image' => $player['image'] ?? 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?w=600&h=800&fit=crop&auto=format'
    ];
  }, $rows);
};

try {
  $stmt = $pdo->query('SELECT * FROM players ORDER BY name ASC');
  $players = $normalizePlayers($stmt->fetchAll(PDO::FETCH_ASSOC));

  if (empty($players)) {
    $players = $normalizePlayers(require __DIR__ . '/players.php');
  }
} catch (Throwable $e) {
  $players = $normalizePlayers(require __DIR__ . '/players.php');
}

$topScorer = $players[0] ?? ['name' => 'Geen speler', 'goals' => 0];
$topAssister = $players[0] ?? ['name' => 'Geen speler', 'assists' => 0];
$topDistance = $players[0] ?? ['name' => 'Geen speler', 'km' => 0];

if (!empty($players)) {
  usort($players, static fn(array $a, array $b) => $b['goals'] <=> $a['goals']);
  $topScorer = $players[0];

  usort($players, static fn(array $a, array $b) => $b['assists'] <=> $a['assists']);
  $topAssister = $players[0];

  usort($players, static fn(array $a, array $b) => $b['km'] <=> $a['km']);
  $topDistance = $players[0];
}
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Real Madrid Statistieken</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <header class="hero">
      <div class="hero-decor decor-one"></div>
      <div class="hero-decor decor-two"></div>

      <div class="container">
        <div class="brand-row">
          <img class="club-mark" src="https://upload.wikimedia.org/wikipedia/en/5/5c/Real_Madrid_CF_logo.svg" alt="Real Madrid wapen" />
          <div>
            <div class="club-name">Real Madrid C.F.</div>
            <div class="club-season">SEIZOEN 2026–27 · LA LIGA &amp; CHAMPIONS LEAGUE</div>
          </div>
        </div>

        <h1 class="hero-title">SELECTIE<br /><span>STATISTIEKEN</span></h1>

        <div class="top-stats">
          <div class="top-stat">
            <span class="top-label">TOPSCORER</span>
            <strong class="top-name"><?= htmlspecialchars($topScorer['name'] ?? 'Geen speler', ENT_QUOTES, 'UTF-8') ?></strong>
            <span class="top-value"><?= (int) ($topScorer['goals'] ?? 0) ?> goals</span>
          </div>
          <div class="top-stat">
            <span class="top-label">MEESTE ASSISTS</span>
            <strong class="top-name"><?= htmlspecialchars($topAssister['name'] ?? 'Geen speler', ENT_QUOTES, 'UTF-8') ?></strong>
            <span class="top-value"><?= (int) ($topAssister['assists'] ?? 0) ?> assists</span>
          </div>
          <div class="top-stat">
            <span class="top-label">MEESTE KM</span>
            <strong class="top-name"><?= htmlspecialchars($topDistance['name'] ?? 'Geen speler', ENT_QUOTES, 'UTF-8') ?></strong>
            <span class="top-value"><?= number_format((float) ($topDistance['km'] ?? 0), 1, ',', '.') ?> km</span>
          </div>
        </div>
      </div>
    </header>

    <div class="toolbar">
      <div class="container toolbar-inner">
        <div class="filter-group" id="filterGroup"></div>
        <div class="sort-group">
          <span class="sort-label">SORTEER</span>
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
          <span class="total-label">TOTAAL DOELPUNTEN</span>
          <strong id="totalGoals">0</strong>
        </div>
        <div class="total-box">
          <span class="total-label">TOTAAL ASSISTS</span>
          <strong id="totalAssists">0</strong>
        </div>
        <div class="total-box">
          <span class="total-label">KM GELOPEN</span>
          <strong id="totalKm">0 km</strong>
        </div>
        <div class="total-box">
          <span class="total-label">SPELERS</span>
          <strong id="totalPlayers">0</strong>
        </div>
      </section>
    </main>

    <footer class="footer">HALA MADRID Y NADA MÁS · ESTADIO SANTIAGO BERNABÉU</footer>

    <script>
      const players = <?= json_encode($players, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

      const filterButtons = [
        { key: 'all', label: 'Alle', count: players.length },
        { key: 'GK', label: 'Keeper', count: players.filter((player) => player.position === 'GK').length },
        { key: 'DEF', label: 'Verdediger', count: players.filter((player) => player.position === 'DEF').length },
        { key: 'MID', label: 'Middenvelder', count: players.filter((player) => player.position === 'MID').length },
        { key: 'FWD', label: 'Aanvaller', count: players.filter((player) => player.position === 'FWD').length }
      ];

      const state = {
        filter: 'all',
        sort: 'goals'
      };

      const filterGroup = document.getElementById('filterGroup');
      const playerGrid = document.getElementById('playerGrid');

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
          <button
            type="button"
            class="filter-button ${state.filter === button.key ? 'active' : ''}"
            data-filter="${button.key}"
          >
            ${button.label}<span class="count">${button.count}</span>
          </button>
        `).join('');
      }

      function renderCards() {
        const visiblePlayers = getVisiblePlayers();
        playerGrid.innerHTML = visiblePlayers.map((player) => {
          const statEntries = [
            { key: 'goals', value: player.goals, label: 'GOALS' },
            { key: 'assists', value: player.assists, label: 'ASSISTS' },
            { key: 'km', value: player.km, label: 'KM' }
          ];

          const maxValue = Math.max(...statEntries.map((stat) => stat.value || 1));

          return `
            <a class="player-card" data-position="${player.position}" href="speler.php?speler=${encodeURIComponent(player.name)}" aria-label="Bekijk details van ${player.name}">
              <img class="card-image" src="${player.image}" alt="${player.name}" />
              <div class="card-overlay"></div>
              <div class="card-badges">
                <span class="rank">#${player.shirt}</span>
                <span class="position-tag">${player.position}</span>
              </div>
              <div class="card-number">${player.shirt}</div>
              <div class="card-content">
                <div class="card-meta"><span>${player.country === 'FRANKRIJK' ? '🇫🇷' : player.country === 'BRAZILIË' ? '🇧🇷' : player.country === 'ENGELAND' ? '🏴' : player.country === 'URUGUAY' ? '🇺🇾' : player.country === 'SPANJE' ? '🇪🇸' : player.country === 'BELGIË' ? '🇧🇪' : '🌍'}</span><span>${player.country} · ${player.matches} WEDSTR.</span></div>
                <div class="card-name">
                  <span class="first-name">${player.firstName}</span>
                  <span class="last-name">${player.lastName}</span>
                </div>
                <div class="stats-grid">
                  ${statEntries.map((stat) => `
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
