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

// On filtre par recherche
$recherche = trim($_GET['search'] ?? '');
$filtreSaveur = $_GET['saveur'] ?? '';
$filtreAllergen = $_GET['allergene'] ?? '';

// Definitions de synonymes pour la recherche
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

// FILTRE PAR RECHERCHE TEXTE
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

// FILTRE PAR SAVEUR
if ($filtreSaveur === 'pimente') {
    $plats = array_filter($plats, fn($p) => $p['pimente'] === true);
} elseif ($filtreSaveur === 'vegetarien') {
    $plats = array_filter($plats, fn($p) => $p['vegetarien'] === true);
}

// FILTRE PAR ALLERGÈNES
if ($filtreSaveur === 'sans_gluten') {
    $plats = array_filter($plats, fn($p) => !in_array('gluten', $p['allergenes']));
} elseif ($filtreSaveur === 'sans_lactose') {
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

// On range chaque plat dans sa catégorie
foreach ($plats as $plat) {
    $cat = $plat['categorie'];
    if (isset($categories[$cat])) {
        $categories[$cat][] = $plat;
    }
}

// Noms affichés des catégories
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
    <style>
        /* Style du prix */
        .carte-item-prix {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-bordeaux);
            padding: 0 15px 10px 15px;
        }

        /* Badge végétarien */
        .badge-veg {
            display: inline-block;
            background: #27ae60;
            color: #fff;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            margin: 0 15px 5px 15px;
        }

        /* Badge pimenté */
        .badge-piment {
            display: inline-block;
            background: #e74c3c;
            color: #fff;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            margin: 0 5px 5px 0;
        }

        /* Allergènes */
        .carte-item-allergenes {
            font-size: 11px;
            color: #999;
            padding: 0 15px 15px 15px;
        }

        /* Bouton panier */
        .btn-panier {
            display: block;
            width: calc(100% - 30px);
            margin: 0 15px 15px 15px;
            background: var(--color-bordeaux);
            color: #fff;
            border: none;
            padding: 10px;
            cursor: pointer;
            font-family: var(--font-main);
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: background 0.3s;
        }

        .btn-panier:hover {
            background: var(--color-gold);
        }

        /* Message aucun résultat */
        .aucun-resultat {
            text-align: center;
            padding: 60px;
            font-size: 18px;
            color: #999;
            grid-column: 1 / -1;
        }
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
        </div>
    </nav>
</header>

<main>
    <section class="carte-filter-section">
        <div class="filter-container">
            <form method="GET" action="carte.php" class="search-box">
                <input type="text" name="search" placeholder="Rechercher un plat..."
                       value="<?php echo htmlspecialchars($recherche); ?>">
                <button type="submit" class="btn-search-carte">RECHERCHER</button>
            </form>

            <div class="filter-menu">
                <div class="filter-group">
                    <label>SAVEURS</label>
                    <select name="saveur" onchange="this.form.submit()" form="filtreForm">
                        <option value="">Toutes</option>
                        <option value="pimente" <?php echo $filtreSaveur === 'pimente' ? 'selected' : ''; ?>>Pimenté</option>
                        <option value="vegetarien" <?php echo $filtreSaveur === 'vegetarien' ? 'selected' : ''; ?>>Végétarien</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>ALLERGÈNES</label>
                    <select name="allergene" onchange="this.form.submit()" form="filtreForm">
                        <option value="">Sans restriction</option>
                        <option value="sans_gluten" <?php echo $filtreAllergen === 'sans_gluten' ? 'selected' : ''; ?>>Sans Gluten</option>
                        <option value="sans_lactose" <?php echo $filtreAllergen === 'sans_lactose' ? 'selected' : ''; ?>>Sans Lactose</option>
                    </select>
                </div>
            </div>

            <form id="filtreForm" method="GET" action="carte.php">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($recherche); ?>">
            </form>
        </div>
    </section>

    <?php foreach ($categories as $catKey => $platsCat): ?>
        <?php if (!empty($platsCat)): ?>
            <section class="carte-section" id="<?php echo $catKey; ?>">
                <h1 class="carte-title"><?php echo $nomCategories[$catKey]; ?></h1>
                
                <div class="carte-grid">
                    <?php foreach ($platsCat as $plat): ?>
                        <div class="carte-item">
                            <img src="<?php echo htmlspecialchars($plat['image']); ?>"
                                 alt="<?php echo htmlspecialchars($plat['nom']); ?>"
                                 class="carte-image"
                                 onerror="this.src='photo/placeholder.jpg'">

                            <h3 class="carte-item-name"><?php echo htmlspecialchars($plat['nom']); ?></h3>

                            <p class="carte-item-description"><?php echo htmlspecialchars($plat['description']); ?></p>

                            <p class="carte-item-prix"><?php echo number_format($plat['prix'], 2, ',', ''); ?> €</p>

                            <?php if ($plat['vegetarien']): ?>
                                <span class="badge-veg">🌿 Végétarien</span>
                            <?php endif; ?>

                            <?php if ($plat['pimente']): ?>
                                <span class="badge-piment">🌶 Pimenté</span>
                            <?php endif; ?>

                            <?php if (!empty($plat['allergenes'])): ?>
                                <p class="carte-item-allergenes">
                                    Allergènes : <?php echo implode(', ', $plat['allergenes']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (estConnecte() && getRoleConnecte() === 'client'): ?>
                                <button class="btn-panier"
                                        onclick="window.location.href='panier.php?ajouter=<?php echo $plat['id']; ?>'">
                                    AJOUTER AU PANIER
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php
    $totalPlats = array_sum(array_map('count', $categories));
    if ($totalPlats === 0):
    ?>
        <section class="carte-section">
            <div class="carte-grid">
                <p class="aucun-resultat">Aucun plat trouvé pour "<?php echo htmlspecialchars($recherche); ?>"</p>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer>
    <div class="footer-top">
        <div class="footer-column">
            <h3>Notre Adresse</h3>
            <p>Avenue du Parc<br>95000 Cergy</p>
        </div>
        <div class="footer-column">
            <h3>Horaires</h3>
            <p>Lundi - Jeudi : 12:00 - 22:45</p>
            <p>Vendredi - Dimanche : 12:00 - 23:45</p>
        </div>
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

    <div class="footer-middle">
        <div class="footer-column">
            <h3>Administration</h3>
            <a href="admin.php">Interface Administrateur</a>
        </div>
        <div class="footer-column">
            <h3>Restaurateur</h3>
            <a href="restaurateur.php">Gestion Cuisine (Tablette)</a>
        </div>
        <div class="footer-column">
            <h3>Livreur</h3>
            <a href="livreur.php">Interface Livraison (Mobile)</a>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2026 L'Antica Trattoria - Site réalisé par Boualili Kenza et Eish Shahd</p>
    </div>
</footer>

</body>
</html>
