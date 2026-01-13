<?php

/**
 * Directus API Queries - Phase 3
 * 
 * This script demonstrates the 7 required queries using the Directus REST API.
 */

$baseUrl = 'http://localhost:8055';

// 1. Loading credentials
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            putenv(trim($parts[0]) . "=" . trim($parts[1]));
        }
    }
}

loadEnv(__DIR__ . '/.env');

$adminEmail = getenv('ADMIN_EMAIL');
$adminPassword = getenv('ADMIN_PASSWORD');

// 2. Authentication
function login($baseUrl, $email, $password) {
    $ch = curl_init("$baseUrl/auth/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email, 'password' => $password]));
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response['data']['access_token'] ?? null;
}

$token = login($baseUrl, $adminEmail, $adminPassword);
if (!$token) die("Login failed. Check your .env credentials.\n");

// 3. Query Runner
function runQuery($label, $url, $token) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "QUERY: $label\n";
    echo "URL: $url\n";
    echo str_repeat("-", 50) . "\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status !== 200) {
        echo "Error: HTTP $status\n";
        echo $response . "\n";
    } else {
        $data = json_decode($response, true);
        echo json_encode($data['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}

// ---------------------------------------------------------
// EXECUTION DES REQUÊTES
// ---------------------------------------------------------

// 1. Liste des praticiens (limité à 3 pour la lisibilité)
runQuery("1. Liste des praticiens", "$baseUrl/items/praticien?limit=3", $token);

// 2. La spécialité d'ID 2
runQuery("2. La spécialité d'ID 2", "$baseUrl/items/specialite/2", $token);

// 3. La spécialité d'ID 2, avec uniquement son libellé
runQuery("3. La spécialité d'ID 2 (libellé uniquement)", "$baseUrl/items/specialite/2?fields=libelle", $token);

// 4. Un praticien avec sa spécialité (libellé)
runQuery("4. Un praticien avec sa spécialité (libellé)", "$baseUrl/items/praticien?fields=nom,prenom,specialite_id.libelle&limit=1", $token);

// 5. Une structure (nom, ville) et la liste des praticiens rattachés (nom, prenom)
runQuery("5. Une structure et ses praticiens", "$baseUrl/items/structure?fields=nom,ville,praticiens.nom,praticiens.prenom&limit=1", $token);

// 6. Idem en ajoutant le libellé de la spécialité des praticiens
runQuery("6. Structure, praticiens et leurs spécialités", "$baseUrl/items/structure?fields=nom,ville,praticiens.nom,praticiens.prenom,praticiens.specialite_id.libelle&limit=1", $token);

// 7. Les structures dont le nom de la ville contient 'sur' avec la liste des praticiens
$filter = urlencode(json_encode(['ville' => ['_contains' => 'sur']]));
runQuery("7. Structures avec 'sur' dans la ville", "$baseUrl/items/structure?filter=$filter&fields=nom,ville,praticiens.nom,praticiens.prenom,praticiens.specialite_id.libelle", $token);
