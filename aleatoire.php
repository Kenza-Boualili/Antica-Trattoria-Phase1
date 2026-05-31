<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

// Sécurité : Seul un client connecté peut générer un menu
requireConnexion();
requireRole('client');

function lirePlats()
{
    $fichier = __DIR__ . '/data/plats.json';
    if (!file_exists($fichier)) {
        return [];
    }
    return json_decode(file_get_contents($fichier), true) ?? [];
}

$plats = lirePlats();

// On sépare les plats par type de catégorie
$entrées  = array_filter($plats, fn($p) => $p['categorie'] === 'antipasti');
$principaux = array_filter($plats, fn($p) => in_array($p['categorie'], ['pasta', 'carne', 'pizze']));
$desserts = array_filter($plats, fn($p) => $p['categorie'] === 'dolce');

// Sécurité : si la base est vide, on redirige
if (empty($plats)) {
    header('Location: carte.php');
    exit;
}

// On réinitialise le panier pour y mettre le menu surprise
$_SESSION['panier'] = [];

// 1. On pioche une entrée au hasard (si dispo)
if (!empty($entrées)) {
    $e = $entrées[array_rand($entrées)];
    $_SESSION['panier'][$e['id']] = [
        'id'       => $e['id'],
        'nom'      => $e['nom'],
        'prix'     => $e['prix'],
        'quantite' => 1
    ];
}

// 2. On pioche un plat principal au hasard
if (!empty($principaux)) {
    $p = $principaux[array_rand($principaux)];
    $_SESSION['panier'][$p['id']] = [
        'id'       => $p['id'],
        'nom'      => $p['nom'],
        'prix'     => $p['prix'],
        'quantite' => 1
    ];
}

// 3. On pioche un dessert au hasard (si dispo)
if (!empty($desserts)) {
    $d = $desserts[array_rand($desserts)];
    $_SESSION['panier'][$d['id']] = [
        'id'       => $d['id'],
        'nom'      => $d['nom'],
        'prix'     => $d['prix'],
        'quantite' => 1
    ];
}

// Une fois le panier surprise rempli, on redirige vers le panier 
header('Location: panier.php');
exit;
