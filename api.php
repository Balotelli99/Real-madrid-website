<?php
header('Content-Type: application/json; charset=utf-8');

function sendJson($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($requestMethod, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    sendJson(['error' => 'Gebruik een GET- of POST-verzoek.'], 405);
}

if ($requestMethod === 'POST') {
    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
        sendJson(['error' => 'Spelers beheren kan alleen vanaf deze computer.'], 403);
    }
}

try {
    require __DIR__ . '/db.php';
    require __DIR__ . '/players.php';

    if ($requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            sendJson(['error' => 'Ongeldige invoer.'], 400);
        }

        $action = $input['action'] ?? '';

        if ($action === 'delete') {
            $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                sendJson(['error' => 'Ongeldig speler-ID.'], 400);
            }

            $statement = $mysqli->prepare('DELETE FROM players WHERE id = ?');
            $statement->bind_param('i', $id);
            $statement->execute();

            if ($statement->affected_rows === 0) {
                sendJson(['error' => 'Speler niet gevonden.'], 404);
            }

            sendJson(['message' => 'Speler verwijderd.']);
        }

        if (!in_array($action, ['add', 'update'], true)) {
            sendJson(['error' => 'Onbekende actie.'], 400);
        }

        $name = trim((string) ($input['name'] ?? ''));
        $position = (string) ($input['position'] ?? '');
        $country = trim((string) ($input['country'] ?? ''));
        $image = trim((string) ($input['image'] ?? ''));
        $positions = ['FWD', 'MID', 'DEF', 'GK'];
        $numbers = [];

        foreach (['matches', 'goals', 'assists', 'km', 'shirt'] as $field) {
            $numbers[$field] = filter_var($input[$field] ?? null, FILTER_VALIDATE_INT);

            if ($numbers[$field] === false || $numbers[$field] < 0) {
                sendJson(['error' => 'Vul geldige, positieve getallen in voor de statistieken.'], 400);
            }
        }

        if ($name === '' || strlen($name) > 100 || !in_array($position, $positions, true)) {
            sendJson(['error' => 'Vul een naam in en kies een geldige positie.'], 400);
        }

        $nameParts = preg_split('/\s+/', $name, 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        if ($action === 'add') {
            $statement = $mysqli->prepare('INSERT INTO players (name, firstName, lastName, position, country, matches, goals, assists, km, shirt, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->bind_param('sssssiiiiis', $name, $firstName, $lastName, $position, $country, $numbers['matches'], $numbers['goals'], $numbers['assists'], $numbers['km'], $numbers['shirt'], $image);
            $statement->execute();
            sendJson(['message' => 'Speler toegevoegd.', 'id' => $mysqli->insert_id], 201);
        }

        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            sendJson(['error' => 'Ongeldig speler-ID.'], 400);
        }

        $statement = $mysqli->prepare('UPDATE players SET name = ?, firstName = ?, lastName = ?, position = ?, country = ?, matches = ?, goals = ?, assists = ?, km = ?, shirt = ?, image = ? WHERE id = ?');
        $statement->bind_param('sssssiiiiisi', $name, $firstName, $lastName, $position, $country, $numbers['matches'], $numbers['goals'], $numbers['assists'], $numbers['km'], $numbers['shirt'], $image, $id);
        $statement->execute();

        if ($statement->affected_rows === 0) {
            $check = $mysqli->prepare('SELECT id FROM players WHERE id = ?');
            $check->bind_param('i', $id);
            $check->execute();

            if ($check->get_result()->num_rows === 0) {
                sendJson(['error' => 'Speler niet gevonden.'], 404);
            }
        }

        sendJson(['message' => 'Speler bijgewerkt.']);
    }

    $players = loadPlayers($mysqli);
    $resource = $_GET['resource'] ?? 'players';

    if ($resource === 'players') {
        $name = trim($_GET['speler'] ?? '');

        if ($name !== '') {
            $player = findPlayerByName($players, $name);

            if ($player === null) {
                sendJson(['error' => 'Speler niet gevonden.'], 404);
            }

            sendJson(['data' => $player]);
        }

        sendJson(['data' => $players]);
    }

    if ($resource === 'stats') {
        $totals = ['players' => count($players), 'goals' => 0, 'assists' => 0, 'km' => 0];
        $leaders = ['goals' => null, 'assists' => null, 'km' => null];

        foreach ($players as $player) {
            $totals['goals'] += $player['goals'];
            $totals['assists'] += $player['assists'];
            $totals['km'] += $player['km'];

            foreach (array_keys($leaders) as $stat) {
                if ($leaders[$stat] === null || $player[$stat] > $leaders[$stat][$stat]) {
                    $leaders[$stat] = $player;
                }
            }
        }

        sendJson(['data' => ['totals' => $totals, 'leaders' => $leaders]]);
    }

    sendJson(['error' => 'Onbekende keuze. Gebruik players of stats.'], 404);
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    sendJson(['error' => 'Databasefout. Controleer of MySQL draait en de database bestaat.'], 500);
} catch (Throwable $error) {
    error_log($error->getMessage());
    sendJson(['error' => 'Er ging iets mis bij het ophalen van de gegevens.'], 500);
}
