<?php
header('Content-Type: application/json; charset=utf-8');

function sendJson($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    sendJson(['error' => 'Gebruik een GET-verzoek.'], 405);
}

try {
    require __DIR__ . '/db.php';
    require __DIR__ . '/players.php';

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
