// =============================================
// 1. Elementen van de pagina ophalen
// =============================================
const playerForm = document.getElementById('playerForm');
const managementList = document.getElementById('managementList');
const managementMessage = document.getElementById('managementMessage');
const managementTitle = document.getElementById('managementTitle');
const submitPlayer = document.getElementById('submitPlayer');
const cancelEdit = document.getElementById('cancelEdit');

// De spelers die PHP in de pagina heeft gezet
let players = JSON.parse(document.getElementById('players-data').textContent);


// =============================================
// 2. Hulpfuncties
// =============================================

// Maakt tekst veilig voor HTML, zodat bijvoorbeeld <b> niet als code werkt
function escapeHtml(text) {
  text = String(text);
  text = text.replaceAll('&', '&amp;');
  text = text.replaceAll('<', '&lt;');
  text = text.replaceAll('>', '&gt;');
  text = text.replaceAll('"', '&quot;');
  text = text.replaceAll("'", '&#039;');
  return text;
}

// Laat een melding zien. Bij een fout wordt de melding rood.
function showMessage(message, isError) {
  managementMessage.textContent = message;

  if (isError) {
    managementMessage.classList.add('is-error');
  } else {
    managementMessage.classList.remove('is-error');
  }
}

// Zoekt een speler in de lijst met zijn id
function findPlayer(id) {
  for (const player of players) {
    if (Number(player.id) === Number(id)) {
      return player;
    }
  }
  return null;
}

// Stuurt gegevens naar api.php en geeft het antwoord terug
async function sendToApi(data, errorMessage) {
  const response = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  const result = await response.json();

  // Ging het mis? Dan stoppen we met een foutmelding.
  if (!response.ok) {
    throw new Error(result.error || errorMessage);
  }

  return result;
}


// =============================================
// 3. De lijst met spelers tonen
// =============================================
function showPlayerList() {
  let html = '';

  for (const player of players) {
    const name = escapeHtml(player.name);
    const position = escapeHtml(player.position);
    const shirt = escapeHtml(player.shirt);

    html += `
      <div class="management-row">
        <div class="management-player">
          <strong>${name}</strong>
          <span>${position} · #${shirt}</span>
        </div>
        <div class="management-actions">
          <button type="button" class="management-edit" onclick="editPlayer(${player.id})" aria-label="Bewerk ${name}">Bewerken</button>
          <button type="button" class="management-delete" onclick="deletePlayer(${player.id})" aria-label="Verwijder ${name}">Verwijderen</button>
        </div>
      </div>
    `;
  }

  managementList.innerHTML = html;
}

// Haalt de nieuwste spelers op uit de database en toont ze opnieuw
async function reloadPlayers() {
  const response = await fetch('api.php?resource=players');
  const result = await response.json();

  if (!response.ok) {
    throw new Error(result.error || 'De spelers konden niet worden geladen.');
  }

  players = result.data;
  showPlayerList();
}


// =============================================
// 4. Het formulier
// =============================================

// Maakt het formulier leeg en zet het terug op "toevoegen"
function resetForm() {
  playerForm.reset();
  playerForm.elements.id.value = '';
  managementTitle.textContent = 'Speler toevoegen';
  submitPlayer.textContent = 'Speler toevoegen';
  cancelEdit.hidden = true;
}

// Zet de gegevens van een speler in het formulier, zodat je hem kunt aanpassen
function editPlayer(id) {
  const player = findPlayer(id);

  if (player === null) {
    return;
  }

  playerForm.elements.id.value = player.id;
  playerForm.elements.name.value = player.name;
  playerForm.elements.position.value = player.position;
  playerForm.elements.country.value = player.country;
  playerForm.elements.shirt.value = player.shirt;
  playerForm.elements.matches.value = player.matches;
  playerForm.elements.goals.value = player.goals;
  playerForm.elements.assists.value = player.assists;
  playerForm.elements.km.value = player.km;
  playerForm.elements.image.value = player.image;

  managementTitle.textContent = 'Bewerk ' + player.name;
  submitPlayer.textContent = 'Wijzigingen opslaan';
  cancelEdit.hidden = false;
  playerForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Wordt uitgevoerd als je op de opslaan-knop drukt
async function savePlayer(event) {
  event.preventDefault(); // voorkomt dat de pagina herlaadt

  const form = playerForm.elements;

  // Heeft het formulier een id? Dan passen we een speler aan, anders voegen we er een toe.
  let action = 'add';
  if (form.id.value !== '') {
    action = 'update';
  }

  const data = {
    action: action,
    id: form.id.value,
    name: form.name.value,
    position: form.position.value,
    country: form.country.value,
    shirt: form.shirt.value,
    matches: form.matches.value,
    goals: form.goals.value,
    assists: form.assists.value,
    km: form.km.value,
    image: form.image.value,
  };

  try {
    const result = await sendToApi(data, 'Opslaan is niet gelukt.');
    resetForm();
    await reloadPlayers();
    showMessage(result.message, false);
  } catch (error) {
    showMessage(error.message || 'Opslaan is niet gelukt.', true);
  }
}


// =============================================
// 5. Een speler verwijderen
// =============================================
async function deletePlayer(id) {
  const player = findPlayer(id);

  if (player === null) {
    return;
  }

  // Eerst vragen of je het zeker weet
  const sure = window.confirm('Weet je zeker dat je ' + player.name + ' wilt verwijderen?');
  if (!sure) {
    return;
  }

  try {
    const result = await sendToApi({ action: 'delete', id: player.id }, 'Verwijderen is niet gelukt.');

    // Was je deze speler net aan het bewerken? Dan het formulier leegmaken.
    if (Number(playerForm.elements.id.value) === Number(player.id)) {
      resetForm();
    }

    await reloadPlayers();
    showMessage(result.message, false);
  } catch (error) {
    showMessage(error.message || 'Verwijderen is niet gelukt.', true);
  }
}


// =============================================
// 6. Starten
// =============================================
playerForm.addEventListener('submit', savePlayer);
cancelEdit.addEventListener('click', resetForm);

showPlayerList();
