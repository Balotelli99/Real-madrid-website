<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Speler toevoegen | Real Madrid</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body class="beheer-page">
    <header class="hero hero--small">
      <div class="container">
        <div class="brand-row">
          <a class="brand-mark" href="index.php" aria-label="Terug naar de selectie">RM</a>
          <div>
            <div class="club-name">Real Madrid C.F.</div>
            <div class="club-season">Speler toevoegen</div>
          </div>
        </div>
      </div>
    </header>

    <main class="container main-content">
      <a class="detail-back" href="beheer.php">Terug naar spelers beheren</a>

      <section class="player-management" aria-labelledby="pageTitle">
        <div class="management-heading">
          <div>
            <span class="detail-kicker">Selectiebeheer</span>
            <h1 id="pageTitle">Nieuwe speler</h1>
          </div>
        </div>

        <p class="management-message" id="message" role="status" aria-live="polite"></p>

        <form class="player-form" id="addPlayerForm">
          <label>Naam<input name="name" type="text" maxlength="100" required /></label>
          <label>Positie
            <select name="position" required>
              <option value="FWD">Aanvaller</option>
              <option value="MID">Middenvelder</option>
              <option value="DEF">Verdediger</option>
              <option value="GK">Keeper</option>
            </select>
          </label>
          <label>Land<input name="country" type="text" maxlength="50" /></label>
          <label>Rugnummer<input name="shirt" type="number" min="0" step="1" value="0" required /></label>
          <label>Goals<input name="goals" type="number" min="0" step="1" value="0" required /></label>
          <label>Assists<input name="assists" type="number" min="0" step="1" value="0" required /></label>
          <label>Kilometers<input name="km" type="number" min="0" step="1" value="0" required /></label>
          <label class="form-wide">Afbeeldings-URL
            <input id="imageUrl" name="image" type="url" maxlength="255" placeholder="https://..." />
          </label>
          <input name="matches" type="hidden" value="0" />
          <button class="management-submit" type="submit">Speler toevoegen</button>
        </form>

        <img id="imagePreview" alt="Voorbeeld van de spelersafbeelding" hidden style="max-width: 180px; margin-top: 16px;" />
      </section>
    </main>

    <footer class="footer">Real Madrid · Hala Madrid</footer>

    <script>
      const form = document.getElementById('addPlayerForm');
      const message = document.getElementById('message');
      const imageUrl = document.getElementById('imageUrl');
      const imagePreview = document.getElementById('imagePreview');

      imageUrl.addEventListener('input', () => {
        imagePreview.src = imageUrl.value;
        imagePreview.hidden = imageUrl.value === '';
      });

      imagePreview.addEventListener('error', () => {
        imagePreview.hidden = true;
      });

      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        message.textContent = 'Bezig met opslaan...';
        message.classList.remove('is-error');

        const player = Object.fromEntries(new FormData(form));
        player.action = 'add';

        try {
          const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(player),
          });
          const result = await response.json();

          if (!response.ok) {
            throw new Error(result.error || 'Opslaan is niet gelukt.');
          }

          form.reset();
          imagePreview.hidden = true;
          message.textContent = 'Speler is toegevoegd.';
        } catch (error) {
          message.textContent = error.message || 'Opslaan is niet gelukt.';
          message.classList.add('is-error');
        }
      });
    </script>
  </body>
</html>