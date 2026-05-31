<?php

require_once '../lib/auth.php';
demarrerSession();

// 1. Charger les données
function lirePlats() {
    $fichier = __DIR__ . '/../data/plats.json';
    return file_exists($fichier) ? json_decode(file_get_contents($fichier), true) : [];
}

$plats = lirePlats();

// 2. Récupérer les paramètres du fetch
$recherche = trim($_GET['search'] ?? '');
$saveur = $_GET['saveur'] ?? '';
$allergene = $_GET['allergene'] ?? '';

// 3. Mapping des synonymes 
$mappingRecherche = [
    'pizza' => 'pizze', 'pizzas' => 'pizze',
    'pate' => 'pasta', 'pates' => 'pasta',
    'viande' => 'carne', 'viandes' => 'carne',
    'entree' => 'antipasti', 'dessert' => 'dolce',
    'boisson' => 'cocktail', 'vegetarien' => 'vegetarien'
];

// 4. APPLICATION DES FILTRES
// Filtre recherche
if (!empty($recherche)) {
    $rechercheNorm = strtolower($recherche);
    $categorieAlias = $mappingRecherche[$rechercheNorm] ?? $rechercheNorm;

    $plats = array_filter($plats, function($p) use ($rechercheNorm, $categorieAlias) {
        return stripos($p['nom'], $rechercheNorm) !== false ||
               stripos($p['description'], $rechercheNorm) !== false ||
               stripos($p['categorie'], $rechercheNorm) !== false ||
               stripos($p['categorie'], $categorieAlias) !== false;
    });
}

// Filtre saveur
if ($saveur === 'pimente') {
    $plats = array_filter($plats, fn($p) => isset($p['pimente']) && $p['pimente'] === true);
} elseif ($saveur === 'vegetarien') {
    $plats = array_filter($plats, fn($p) => isset($p['vegetarien']) && $p['vegetarien'] === true);
}

// Filtre allergène
if ($allergene === 'sans_gluten') {
    $plats = array_filter($plats, fn($p) => !in_array('gluten', $p['allergenes']));
} elseif ($allergene === 'sans_lactose') {
    $plats = array_filter($plats, fn($p) => !in_array('lactose', $p['allergenes']));
}

// 5. RE-GROUPER PAR CATÉGORIE (pour que render_carte.php puisse afficher les titres)
$categories = [
    'antipasti' => [], 'pasta' => [], 'carne' => [],
    'pizze' => [], 'dolce' => [], 'cocktail' => []
];

foreach ($plats as $plat) {
    $cat = $plat['categorie'];
    if (isset($categories[$cat])) {
        $categories[$cat][] = $plat;
    }
}

// 6. DÉFINIR LES NOMS ( pour éviter que les titres disparaissent)
$nomCategories = [
    'antipasti' => 'ANTIPASTI',
    'pasta'     => 'PASTA',
    'carne'     => 'CARNE',
    'pizze'     => 'PIZZE',
    'dolce'     => 'DOLCE',
    'cocktail'  => 'COCKTAIL'
];

// 7. GÉNÉRER LE HTML
include 'render_carte.php';
