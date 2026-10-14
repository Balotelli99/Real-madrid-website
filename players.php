<?php

function normalizePlayerName(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/', ' ', trim($value));
    $value = strtolower($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);

    if ($value === false) {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $value ?? ''));
    }

    return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
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
    $map = [
        'Aanvaller' => 'FWD',
        'Middenvelder' => 'MID',
        'Verdediger' => 'DEF',
        'Keeper' => 'GK',
    ];

    return array_map(static function (array $player) use ($map): array {
        $name = trim((string) ($player['name'] ?? ''));
        $parts = preg_split('/\s+/', $name, 2);

        return [
            'name' => $name,
            'firstName' => $player['firstName'] ?? ($parts[0] ?? ''),
            'lastName' => $player['lastName'] ?? ($parts[1] ?? ''),
            'position' => $map[$player['position'] ?? ''] ?? ($player['position'] ?? 'FWD'),
            'country' => $player['country'] ?? 'NEDERLAND',
            'matches' => (int) ($player['matches'] ?? 0),
            'goals' => (int) ($player['goals'] ?? 0),
            'assists' => (int) ($player['assists'] ?? 0),
            'km' => (float) ($player['kilometers'] ?? $player['km'] ?? 0),
            'shirt' => (int) ($player['shirt'] ?? 0),
            'image' => $player['image'] ?? 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?w=600&h=800&fit=crop&auto=format',
        ];
    }, $rows);
}

function loadPlayers(?PDO $pdo = null): array
{
    $fallback = [
        ['name' => 'Kylian Mbappé', 'position' => 'Aanvaller', 'country' => 'FRANKRIJK', 'matches' => 34, 'goals' => 31, 'assists' => 5, 'km' => 162.4, 'shirt' => 10, 'image' => 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?w=900&q=80'],
        ['name' => 'Vinícius Júnior', 'position' => 'Aanvaller', 'country' => 'BRAZILIË', 'matches' => 36, 'goals' => 24, 'assists' => 18, 'km' => 185.7, 'shirt' => 7, 'image' => 'https://images.unsplash.com/photo-1547347298-4074fc3086f0?w=900&q=80'],
        ['name' => 'Jude Bellingham', 'position' => 'Middenvelder', 'country' => 'ENGELAND', 'matches' => 39, 'goals' => 23, 'assists' => 13, 'km' => 176.9, 'shirt' => 5, 'image' => 'https://images.unsplash.com/photo-1521412644187-c49fa049e84d?w=900&q=80'],
        ['name' => 'Rodrygo', 'position' => 'Aanvaller', 'country' => 'BRAZILIË', 'matches' => 33, 'goals' => 17, 'assists' => 8, 'km' => 170.5, 'shirt' => 11, 'image' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=900&q=80'],
        ['name' => 'Thibaut Courtois', 'position' => 'Keeper', 'country' => 'BELGIË', 'matches' => 32, 'goals' => 0, 'assists' => 0, 'km' => 111.2, 'shirt' => 1, 'image' => 'https://images.unsplash.com/photo-1556056504-5c7696c4c28d?w=900&q=80'],
        ['name' => 'Rúben Dias', 'position' => 'Verdediger', 'country' => 'PORTUGAL', 'matches' => 28, 'goals' => 2, 'assists' => 1, 'km' => 158.8, 'shirt' => 4, 'image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80'],
    ];

    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->query('SELECT * FROM players ORDER BY name ASC');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                return normalizePlayers($rows);
            }
        } catch (Throwable $e) {
            // Fallback naar statische data
        }
    }

    return normalizePlayers($fallback);
}
