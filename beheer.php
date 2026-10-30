<?php
$errorMessage = '';
$players = [];

try {
  require __DIR__ . '/db.php';
  require __DIR__ . '/players.php';
  $players = loadPlayers($mysqli);
} catch (Throwable $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $errorMessage = 'De spelers konden niet worden geladen. Controleer de database.';
}

$playersJson = json_encode($players, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Spelers beheren | Real Madrid</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body class="beheer-page">
    <header class="hero hero--small">
      <div class="container">
        <div class="brand-row">
          <a class="brand-mark" href="index.php" aria-label="Terug naar de selectie">RM</a>
          <div>
            <div class="club-name">Real Madrid C.F.</div>
            <div class="club-season">Spelers beheren</div>
          </div>
        </div>
      </div>
    </header>

    <main class="container main-content">
      <a class="detail-back" href="index.php">← Terug naar selectie</a>
      <a class="detail-back" href="toevoegen.php">Eenvoudig speler toevoegen</a>

      <?php if ($errorMessage !== ''): ?>
        <p class="error-message" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
      <?php else: ?>
        <section class="player-management" aria-labelledby="managementTitle">
          <div class="management-heading">
            <div>
              <span class="detail-kicker">Selectiebeheer</span>
              <h1 id="managementTitle">Speler toevoegen</h1>
            </div>
            <button class="management-cancel" id="cancelEdit" type="button" hidden>Annuleren</button>
          </div>
          <p class="management-message" id="managementMessage" role="status" aria-live="polite"></p>
          <form class="player-form" id="playerForm">
            <input id="playerId" name="id" type="hidden" />
            <label>Naam<input name="name" type="text" maxlength="100" required /></label>
            <label>Positie<select name="position" required>
              <option value="FWD">Aanvaller</option>
              <option value="MID">Middenvelder</option>
              <option value="DEF">Verdediger</option>
              <option value="GK">Keeper</option>
            </select></label>
            <label>Land<input name="country" type="text" maxlength="50" /></label>
            <label>Shirtnummer<input name="shirt" type="number" min="0" step="1" value="0" required /></label>
            <label>Wedstrijden<input name="matches" type="number" min="0" step="1" value="0" required /></label>
            <label>Goals<input name="goals" type="number" min="0" step="1" value="0" required /></label>
            <label>Assists<input name="assists" type="number" min="0" step="1" value="0" required /></label>
            <label>Kilometers<input name="km" type="number" min="0" step="1" value="0" required /></label>
            <label class="form-wide">Afbeeldings-URL<input name="image" type="url" maxlength="255" /></label>
            <button class="management-submit" id="submitPlayer" type="submit">Speler toevoegen</button>
          </form>

          <div class="management-list-wrap">
            <h2>Spelers beheren</h2>
            <div class="management-list" id="managementList"></div>
          </div>
        </section>
      <?php endif; ?>
    </main>

    <footer class="footer">Real Madrid · Hala Madrid</footer>

    <script id="players-data" type="application/json"><?= $playersJson ?></script>
    <script src="beheer.js" defer></script>
  </body>
</html>