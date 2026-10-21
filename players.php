<?php

function normalizePlayerName(string $name): string
{
    $name = html_entity_decode($name, ENT_QUOTES, 'UTF-8');
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

    return strtolower(preg_replace('/[^a-z0-9]/i', '', $name ?: ''));
}

function findPlayerByName(array $players, string $selectedName): ?array
{
    $needle = normalizePlayerName($selectedName);

    foreach ($players as $player) {
        $candidate = normalizePlayerName((string) ($player['name'] ?? ''));

        if ($candidate === $needle) {
            return $player;
        }
    }

    return null;
}

function normalizePlayers(array $rows): array
{
    $positions = [
        'Aanvaller' => 'FWD',
        'Middenvelder' => 'MID',
        'Verdediger' => 'DEF',
        'Keeper' => 'GK',
    ];

    return array_map(static function (array $player) use ($positions): array {
        $name = trim((string) ($player['name'] ?? ''));
        $nameParts = preg_split('/\s+/', $name, 2);
        $position = $player['position'] ?? 'FWD';

        return [
            'name' => $name,
            'firstName' => $player['firstName'] ?? ($nameParts[0] ?? ''),
            'lastName' => $player['lastName'] ?? ($nameParts[1] ?? ''),
            'position' => $positions[$position] ?? $position,
            'country' => $player['country'] ?? 'NEDERLAND',
            'matches' => (int) ($player['matches'] ?? 0),
            'goals' => (int) ($player['goals'] ?? 0),
            'assists' => (int) ($player['assists'] ?? 0),
            'km' => (float) ($player['kilometers'] ?? $player['km'] ?? 0),
            'shirt' => (int) ($player['shirt'] ?? 0),
            'image' => $player['image'] ?? '',
        ];
    }, $rows);
}

function loadPlayers(mysqli $database): array
{
    $result = $database->query('SELECT * FROM players ORDER BY name ASC');
    $rows = [];

    while ($player = $result->fetch_assoc()) {
        $rows[] = $player;
    }

    return normalizePlayers($rows);
}
