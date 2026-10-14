<?php
require __DIR__ . '/db.php';
require __DIR__ . '/players.php';

$players = loadPlayers($pdo ?? null);
$selectedPlayerName = trim((string) ($_GET['speler'] ?? ''));
$selectedPlayer = findPlayerByName($players, $selectedPlayerName);

if (!$selectedPlayer) {
    http_response_code(404);
}

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
    <header class="hero hero--small">
      <div class="container">
        <div class="brand-row">
          <span class="brand-mark" aria-hidden="true">RM</span>
          <div>
            <div class="club-name">Real Madrid C.F.</div>
            <div class="club-season">Seizoen 2025/26</div>
          </div>
        </div>
      </div>
    </header>

    <main class="container main-content">
      <?php if ($selectedPlayer): ?>
        <section class="player-detail">
          <a class="detail-back" href="index.php">&larr; Terug naar selectie</a>

          <div class="detail-layout">
            <div class="detail-photo-wrap">
              <img class="detail-photo" src="<?= $escape($selectedPlayer['image']) ?>" alt="<?= $escape($selectedPlayer['name']) ?>" />
            </div>

            <div class="detail-card">
              <span class="detail-kicker">Speler</span>
              <h1 class="detail-name"><?= $escape($selectedPlayer['name']) ?></h1>

              <div class="detail-meta">
                <span><?= $escape($selectedPlayer['position']) ?></span>
                <span><?= $escape($selectedPlayer['country']) ?></span>
                <span>#<?= $escape($selectedPlayer['shirt']) ?></span>
              </div>

              <div class="detail-stats">
                <div class="detail-stat">
                  <span>Wedstrijden</span>
                  <strong><?= $escape($selectedPlayer['matches']) ?></strong>
                </div>
                <div class="detail-stat">
                  <span>Goals</span>
                  <strong><?= $escape($selectedPlayer['goals']) ?></strong>
                </div>
                <div class="detail-stat">
                  <span>Assists</span>
                  <strong><?= $escape($selectedPlayer['assists']) ?></strong>
                </div>
                <div class="detail-stat">
                  <span>KM</span>
                  <strong><?= $escape((float) $selectedPlayer['km']) ?></strong>
                </div>
              </div>
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

    <footer class="footer">Real Madrid · Hala Madrid</footer>
  </body>
</html>
