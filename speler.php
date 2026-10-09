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

$selectedPlayerName = $_GET['speler'] ?? '';
$selectedPlayer = null;

foreach ($players as $player) {
  if (($player['name'] ?? '') === $selectedPlayerName) {
    $selectedPlayer = $player;
    break;
  }
}

if (!$selectedPlayer) {
  http_response_code(404);
}

$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $selectedPlayer ? $escape($selectedPlayer['name']) . ' | Real Madrid' : 'Speler niet gevonden | Real Madrid' ?></title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body class="detail-page">
    <header class="hero">
      <div class="hero-decor decor-one"></div>
      <div class="hero-decor decor-two"></div>
      <div class="container">
        <div class="brand-row">
          <span class="detail-club-mark" aria-hidden="true">RM</span>
          <div>
            <div class="club-name">Real Madrid C.F.</div>
            <div class="club-season">SEIZOEN 2024–25 · LA LIGA &amp; CHAMPIONS LEAGUE</div>
          </div>
        </div>
        <h1 class="hero-title">SELECTIE<br /><span>STATISTIEKEN</span></h1>
      </div>
    </header>

    <main class="container main-content">
      <?php if ($selectedPlayer): ?>
        <section class="player-detail">
          <a class="detail-back" href="index.php"><span aria-hidden="true">&larr;</span> Terug naar selectie</a>
          <div class="detail-layout">
            <div class="detail-photo-wrap">
              <img class="detail-photo" src="<?= $escape($selectedPlayer['image']) ?>" alt="<?= $escape($selectedPlayer['name']) ?>" />
              <div class="detail-photo-shade"></div>
              <div class="detail-photo-topline">
                <span class="detail-photo-season">SELECTIE · 2024/25</span>
                <span class="detail-shirt"><span>RUGNUMMER</span><?= $escape($selectedPlayer['shirt']) ?></span>
              </div>
              <div class="detail-photo-caption">
                <span>REAL MADRID C.F.</span>
                <strong><?= $escape($selectedPlayer['firstName']) ?></strong>
              </div>
            </div>
            <div class="detail-copy">
              <div class="detail-copy-top">
                <span class="detail-kicker">SPELERSPROFIEL <span> / </span> 2024–25</span>
                <span class="detail-position"><?= $escape($selectedPlayer['position']) ?><span aria-hidden="true">·</span><?= $escape($selectedPlayer['country']) ?></span>
              </div>
              <h1 class="detail-name"><?= $escape($selectedPlayer['name']) ?></h1>
              <p class="detail-intro">Seizoensstatistieken <span>·</span> La Liga &amp; Champions League</p>
              <div class="detail-stats">
                <div class="detail-stat"><span class="detail-stat-label">WEDSTRIJDEN</span><strong><?= $escape($selectedPlayer['matches']) ?></strong><span class="detail-stat-unit">GESPEELD</span></div>
                <div class="detail-stat"><span class="detail-stat-label">DOELPUNTEN</span><strong><?= $escape($selectedPlayer['goals']) ?></strong><span class="detail-stat-unit">GESCOORD</span></div>
                <div class="detail-stat"><span class="detail-stat-label">ASSISTS</span><strong><?= $escape($selectedPlayer['assists']) ?></strong><span class="detail-stat-unit">GEGEVEN</span></div>
                <div class="detail-stat"><span class="detail-stat-label">AFSTAND</span><strong><?= $escape($selectedPlayer['km']) ?><small>KM</small></strong><span class="detail-stat-unit">GELOPEN</span></div>
              </div>
              <div class="detail-copy-footer"><span class="detail-footer-line"></span><span>HALA MADRID Y NADA MÁS</span></div>
            </div>
          </div>
        </section>
      <?php else: ?>
        <section class="player-detail">
          <a class="detail-back" href="index.php">&larr; Terug naar selectie</a>
          <h2>Speler niet gevonden</h2>
        </section>
      <?php endif; ?>
    </main>

    <footer class="footer">HALA MADRID Y NADA MÁS · ESTADIO SANTIAGO BERNABÉU</footer>
  </body>
</html>
