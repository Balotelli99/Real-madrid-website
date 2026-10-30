const playerForm = document.getElementById('playerForm');
const managementList = document.getElementById('managementList');
const managementMessage = document.getElementById('managementMessage');
const managementTitle = document.getElementById('managementTitle');
const submitPlayer = document.getElementById('submitPlayer');
const cancelEdit = document.getElementById('cancelEdit');
let players = JSON.parse(document.getElementById('players-data').textContent);

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);
}

function showManagementMessage(message, isError = false) {
  managementMessage.textContent = message;
  managementMessage.classList.toggle('is-error', isError);
}

function renderManagementList() {
  managementList.innerHTML = players.map((player) => `
    <div class="management-row">
      <div class="management-player">
        <strong>${escapeHtml(player.name)}</strong>
        <span>${escapeHtml(player.position)} · #${escapeHtml(player.shirt)}</span>
      </div>
      <div class="management-actions">
        <button type="button" class="management-edit" data-edit="${player.id}" aria-label="Bewerk ${escapeHtml(player.name)}">Bewerken</button>
        <button type="button" class="management-delete" data-delete="${player.id}" aria-label="Verwijder ${escapeHtml(player.name)}">Verwijderen</button>
      </div>
    </div>
  `).join('');
}

async function refreshPlayers() {
  const response = await fetch('api.php?resource=players');
  const result = await response.json();

  if (!response.ok) {
    throw new Error(result.error || 'De spelers konden niet worden geladen.');
  }

  players = result.data;
  renderManagementList();
}

function resetPlayerForm() {
  playerForm.reset();
  document.getElementById('playerId').value = '';
  managementTitle.textContent = 'Speler toevoegen';
  submitPlayer.textContent = 'Speler toevoegen';
  cancelEdit.hidden = true;
}

function editPlayer(playerId) {
  const player = players.find((item) => Number(item.id) === Number(playerId));

  if (!player) return;

  for (const [field, value] of Object.entries({
    id: player.id,
    name: player.name,
    position: player.position,
    country: player.country,
    shirt: player.shirt,
    matches: player.matches,
    goals: player.goals,
    assists: player.assists,
    km: player.km,
    image: player.image,
  })) {
    playerForm.elements[field].value = value;
  }

  managementTitle.textContent = `Bewerk ${player.name}`;
  submitPlayer.textContent = 'Wijzigingen opslaan';
  cancelEdit.hidden = false;
  playerForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

playerForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const data = Object.fromEntries(new FormData(playerForm).entries());
  data.action = data.id ? 'update' : 'add';

  try {
    const response = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.error || 'Opslaan is niet gelukt.');
    }

    resetPlayerForm();
    await refreshPlayers();
    showManagementMessage(result.message);
  } catch (error) {
    showManagementMessage(error.message || 'Opslaan is niet gelukt.', true);
  }
});

managementList.addEventListener('click', async (event) => {
  const editButton = event.target.closest('[data-edit]');
  const deleteButton = event.target.closest('[data-delete]');

  if (editButton) {
    editPlayer(editButton.dataset.edit);
    return;
  }

  if (!deleteButton) return;

  const player = players.find((item) => Number(item.id) === Number(deleteButton.dataset.delete));

  if (!player || !window.confirm(`Weet je zeker dat je ${player.name} wilt verwijderen?`)) return;

  try {
    const response = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: player.id }),
    });
    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.error || 'Verwijderen is niet gelukt.');
    }

    if (Number(document.getElementById('playerId').value) === Number(player.id)) {
      resetPlayerForm();
    }

    await refreshPlayers();
    showManagementMessage(result.message);
  } catch (error) {
    showManagementMessage(error.message || 'Verwijderen is niet gelukt.', true);
  }
});

cancelEdit.addEventListener('click', resetPlayerForm);

renderManagementList();