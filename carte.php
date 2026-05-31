<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

// Lire les plats depuis plats.json
function lirePlats() {
    $fichier = __DIR__ . '/data/plats.json';
    
    if (!file_exists($fichier)) {
        return [];
    }
    
    return json_decode(file_get_contents($fichier), true) ?? [];
}

$plats = lirePlats();

// On récupère les filtres initiaux (pour le premier chargement ou recherche depuis l'accueil)
$recherche = trim($_GET['search'] ?? '');
$filtreSaveur = $_GET['saveur'] ?? '';
$filtreAllergen = $_GET['allergene'] ?? '';

// Définitions de synonymes pour la recherche
$mappingRecherche = [
    'pizza'      => 'pizze',
    'pizzas'     => 'pizze',
    'pate'       => 'pasta',
    'pates'      => 'pasta',
    'pâte'       => 'pasta',
    'pâtes'      => 'pasta',
    'viande'     => 'carne',
    'viandes'    => 'carne',
    'entree'     => 'antipasti',
    'entrees'    => 'antipasti',
    'entrée'     => 'antipasti',
    'entrées'    => 'antipasti',
    'dessert'    => 'dolce',
    'desserts'   => 'dolce',
    'boisson'    => 'cocktail',
    'boissons'   => 'cocktail',
    'cocktail'   => 'cocktail',
    'fromage'    => 'burrata',
    'vegetarien' => 'vegetarien',
    'végétarien' => 'vegetarien',
];

// LA LOGIQUE DE FILTRAGE (Pour le premier affichage PHP)
if (!empty($recherche)) {
    $rechercheNorm  = strtolower(trim($recherche));
    $categorieAlias = $mappingRecherche[$rechercheNorm] ?? $rechercheNorm;

    $plats = array_filter($plats, function($p) use ($rechercheNorm, $categorieAlias) {
        return stripos($p['nom'], $rechercheNorm) !== false ||
               stripos($p['description'], $rechercheNorm) !== false ||
               stripos($p['categorie'], $rechercheNorm) !== false ||
               stripos($p['categorie'], $categorieAlias) !== false ||
               stripos(implode(' ', $p['allergenes']), $rechercheNorm) !== false;
    });
}

if ($filtreSaveur === 'pimente') {
    $plats = array_filter($plats, fn($p) => $p['pimente'] === true);
} elseif ($filtreSaveur === 'vegetarien') {
    $plats = array_filter($plats, fn($p) => $p['vegetarien'] === true);
}

if ($filtreAllergen === 'sans_gluten') {
    $plats = array_filter($plats, fn($p) => !in_array('gluten', $p['allergenes']));
} elseif ($filtreAllergen === 'sans_lactose') {
    $plats = array_filter($plats, fn($p) => !in_array('lactose', $p['allergenes']));
}

// Grouper par catégorie
$categories = [
    'antipasti' => [],
    'pasta'     => [],
    'carne'     => [],
    'pizze'     => [],
    'dolce'     => [],
    'cocktail'  => []
];

foreach ($plats as $plat) {
    $cat = $plat['categorie'];
    if (isset($categories[$cat])) {
        $categories[$cat][] = $plat;
    }
}

$nomCategories = [
    'antipasti' => 'ANTIPASTI',
    'pasta'     => 'PASTA',
    'carne'     => 'CARNE',
    'pizze'     => 'PIZZE',
    'dolce'     => 'DOLCE',
    'cocktail'  => 'COCKTAIL'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notre Carte - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <link rel="stylesheet" href="style carte.css">
    
    <?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre'): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>
    
    <style>
        .carte-item-prix { font-size: 16px; font-weight: 600; color: var(--color-bordeaux); padding: 0 15px 10px 15px; }
        .badge-veg { display: inline-block; background: #27ae60; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 10px; margin: 0 15px 5px 15px; }
        .badge-piment { display: inline-block; background: #e74c3c; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 10px; margin: 0 5px 5px 0; }
        .carte-item-allergenes { font-size: 11px; color: #999; padding: 0 15px 15px 15px; }
        .btn-panier { display: block; width: calc(100% - 30px); margin: 0 15px 15px 15px; background: var(--color-bordeaux); color: #fff; border: none; padding: 10px; cursor: pointer; font-family: var(--font-main); font-size: 12px; letter-spacing: 1px; text-transform: uppercase; transition: background 0.3s; }
        .btn-panier:hover { background: var(--color-gold); }
        .aucun-resultat { text-align: center; padding: 60px; font-size: 18px; color: #999; grid-column: 1 / -1; }
        #menu-container { transition: opacity 0.3s ease; }
    </style>
</head>
<body>

<header>
    <nav id="navbar" class="nav-scrolled">
        <div class="logo">L'Antica Trattoria</div>
        <ul class="nav-links">
            <li><a href="index.php">ACCUEIL</a></li>
            <li><a href="carte.php">NOTRE CARTE</a></li>
        </ul>
        <div class="nav-buttons">
            <?php if (estConnecte()): ?>
                <button class="btn-gold" onclick="window.location.href='profil.php'">MON PROFIL</button>
                <button class="btn-gold" onclick="window.location.href='deconnexion.php'">DÉCONNEXION</button>
            <?php else: ?>
                <button class="btn-gold" onclick="window.location.href='connexion.php'">SE CONNECTER</button>
                <button class="btn-gold" onclick="window.location.href='inscription.php'">S'INSCRIRE</button>
            <?php endif; ?>
            
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold" aria-label="Changer le thème d'affichage">
                <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre'; ?>
            </button>
        </div>
    </nav>
</header>

<main>
    <section class="carte-filter-section">
        <div class="filter-container">
            <form id="searchForm" class="search-box" onsubmit="filtrerLaCarte(event)">
                <input type="text" id="input-search" name="search" placeholder="Rechercher un plat..."
                       value="<?php echo htmlspecialchars($recherche); ?>">
                <button type="submit" class="btn-search-carte">RECHERCHER</button>
            </form>

            <div class="filter-menu">
                <div class="filter-group">
                    <label>SAVEURS</label>
                    <select id="select-saveur" name="saveur" onchange="filtrerLaCarte()">
                        <option value="">Toutes</option>
                        <option value="pimente" <?php echo $filtreSaveur === 'pimente' ? 'selected' : ''; ?>>Pimenté</option>
                        <option value="vegetarien" <?php echo $filtreSaveur === 'vegetarien' ? 'selected' : ''; ?>>Végétarien</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>ALLERGÈNES</label>
                    <select id="select-allergene" name="allergene" onchange="filtrerLaCarte()">
                        <option value="">Sans restriction</option>
                        <option value="sans_gluten" <?php echo $filtreAllergen === 'sans_gluten' ? 'selected' : ''; ?>>Sans Gluten</option>
                        <option value="sans_lactose" <?php echo $filtreAllergen === 'sans_lactose' ? 'selected' : ''; ?>>Sans Lactose</option>
                    </select>
                </div>
            </div>
            
            <?php if (estConnecte() && getRoleConnecte() === 'client'): ?>
                <div style="text-align: center; margin-top: 25px;">
                    <button type="button" class="btn-gold" onclick="window.location.href='aleatoire.php'" style="background: var(--color-bordeaux); color: #fff; border-color: var(--color-bordeaux); font-weight: 600;">
                        🎲 SURPRENEZ-MOI (MENU ALÉATOIRE)
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div id="menu-container">
        <?php 
        // Au premier chargement, on affiche les plats calculés en PHP au début du fichier
        // On inclut le fichier de rendu pour ne pas répéter le code HTML
        include 'api/render_carte.php'; 
        ?>
    </div>
</main>

<footer>
    <div class="footer-top">
        <div class="footer-column"><h3>Notre Adresse</h3><p>Avenue du Parc<br>95000 Cergy</p></div>
        <div class="footer-column"><h3>Horaires</h3><p>Lun - Dim : 12:00 - 23:45</p></div>
        <div class="footer-column">
            <h3>Mon Compte</h3>
            <?php if (estConnecte()): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Se connecter</a>
                <a href="inscription.php">S'INSCRIRE</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-bottom"><p>© 2026 L'Antica Trattoria - Kenza et Shahd</p></div>
</footer>

<script src="js/theme.js"></script>
<script src="js/carte.js"></script>
</body>
</html>
