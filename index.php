<?php
$errorMessage = '';
$players = [];
$leaders = ['goals' => null, 'assists' => null, 'km' => null];

try {
  require __DIR__ . '/db.php';
  require __DIR__ . '/players.php';
  $players = loadPlayers($mysqli);

  $stats = ['goals', 'assists', 'km'];
  foreach ($stats as $stat) {
    foreach ($players as $speler) {
      if ($leaders[$stat] === null || $speler[$stat] > $leaders[$stat][$stat]) {
        $leaders[$stat] = $speler;
      }
    }
  }
} catch (mysqli_sql_exception $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $errorMessage = 'Databasefout. Controleer of MySQL draait en de database bestaat.';
} catch (Throwable $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $errorMessage = 'Er ging iets mis bij het laden van de spelers.';
}

$none = ['name' => 'Geen speler'];
$topscorer   = $leaders['goals']   ?? $none + ['goals' => 0];
$topAssister = $leaders['assists'] ?? $none + ['assists' => 0];
$topDistance = $leaders['km']      ?? $none + ['km' => 0];
$playersJson = json_encode($players, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
?>
<!DOCTYPE html>
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

        <?php if ($errorMessage === ''): ?>
        <div class="summary-row">
          <div class="summary-card">
            <span>Topscorer</span>
            <strong><?= htmlspecialchars($topscorer['name'] ?? 'Geen speler', ENT_QUOTES, 'UTF-8') ?></strong>
            <small><?= (int) ($topscorer['goals'] ?? 0) ?> goals</small>
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
        <?php endif; ?>
      </div>
    </header>

    <?php if ($errorMessage !== ''): ?>
    <main class="container main-content">
      <p class="error-message" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
    </main>
    <?php else: ?>
    <div class="toolbar">
      <div class="container toolbar-inner">
        <div class="filter-group" id="filterGroup"></div>
        <a class="manage-link" href="beheer.php">Spelers beheren</a>
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
    <?php endif; ?>

    <footer class="footer">Real Madrid · Hala Madrid</footer>

    <script id="players-data" type="application/json"><?= $playersJson ?></script>
    <script src="players.js" defer></script>
  </body>
</html>
