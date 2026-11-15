<?php

// Haalt alle spelers uit de database
function loadPlayers($database)
{
    $result = $database->query('SELECT * FROM players ORDER BY name ASC');
    $players = [];

    while ($row = $result->fetch_assoc()) {
        $name = trim($row['name']);

        // Naam in twee stukken: "Kylian Mbappe" wordt "Kylian" en "Mbappe"
        $nameParts = explode(' ', $name, 2);

        $players[] = [
            'id' => (int) $row['id'],
            'name' => $name,
            'firstName' => $row['firstName'] ?? $nameParts[0],
            'lastName' => $row['lastName'] ?? ($nameParts[1] ?? ''),
            'position' => $row['position'],
            'country' => $row['country'] ?? 'NEDERLAND',
            'matches' => (int) $row['matches'],
            'goals' => (int) $row['goals'],
            'assists' => (int) $row['assists'],
            'km' => (int) $row['km'],
            'shirt' => (int) $row['shirt'],
            'image' => $row['image'] ?? '',
        ];
    }

    return $players;
}

// Zoekt een speler op naam, bijvoorbeeld voor de detailpagina
function findPlayerByName($players, $name)
{
    foreach ($players as $player) {
        if (strtolower($player['name']) === strtolower(trim($name))) {
            return $player;
        }
    }

    return null;
}
