<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

function lirePlats(): array {
    $fichier = __DIR__ . '/data/plats.json';
    return file_exists($fichier) ? (json_decode(file_get_contents($fichier), true) ?? []) : [];
}

// Indexation des plats mis en avant par ID
$platsParId = array_column(lirePlats(), null, 'id');
$platDuJour = $platsParId[13] ?? null;         // Osso Buco
$populaires  = array_filter($platsParId, fn($id) => in_array($id, [15, 10]), ARRAY_FILTER_USE_KEY);

$connecte    = estConnecte();
$themeSombre = (($_COOKIE['theme'] ?? '') === 'sombre');

// Helper : carte de suggestion
function cartesuggestion(array $plat, string $tag): string {
    $nom  = htmlspecialchars($plat['nom']);
    $desc = htmlspecialchars($plat['description']);
    $img  = htmlspecialchars($plat['image']);
    $prix = number_format($plat['prix'], 2, ',', '');
    return <<<HTML
        <div class="suggestion-card">
            <div class="dish-tag">$tag</div>
            <img src="$img" alt="$tag : $nom">
            <h3>$nom</h3>
            <p>$desc</p>
            <p class="prix">$prix €</p>
        </div>
    HTML;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Antica Trattoria - Accueil</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <link rel="stylesheet" href="style index.css">
    <?php if ($themeSombre): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>
    <style>
        .prix { color: var(--color-bordeaux); font-weight: 600; margin-top: 8px; }
        .btn-cta {
            display: inline-block; margin-top: 20px;
            background: var(--color-gold); color: #fff; padding: 12px 30px;
            text-decoration: none; text-transform: uppercase;
            font-size: 13px; letter-spacing: 2px;
        }
    </style>
</head>
<body>

<header class="hero">
    <nav id="navbar">
        <div class="logo">L'Antica Trattoria</div>
        <ul class="nav-links">
            <li><a href="index.php">ACCUEIL</a></li>
            <li><a href="carte.php">NOTRE CARTE</a></li>
        </ul>
        <div class="nav-buttons">
            <?php if ($connecte): ?>
                <button class="btn-gold" onclick="location.href='profil.php'">MON PROFIL</button>
                <?php if (!empty($_SESSION['panier'])): ?>
                    <button class="btn-gold" onclick="location.href='panier.php'">
                        🛒 PANIER (<?= array_sum(array_column($_SESSION['panier'], 'quantite')) ?>)
                    </button>
                <?php endif; ?>
                <button class="btn-gold" onclick="location.href='deconnexion.php'">DÉCONNEXION</button>
            <?php else: ?>
                <button class="btn-gold" onclick="location.href='connexion.php'">SE CONNECTER</button>
                <button class="btn-gold" onclick="location.href='inscription.php'">S'INSCRIRE</button>
            <?php endif; ?>
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold" aria-label="Changer le thème d'affichage">
                <?= $themeSombre ? '☀️ Mode clair' : '🌙 Mode sombre' ?>
            </button>
        </div>
    </nav>

    <div class="hero-content">
        <h1>Une expérience italienne authentique</h1>
        <p>Tradition, passion et saveurs d'Italie</p>
        <div class="search-container">
            <form action="carte.php" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Rechercher un plat..." required>
                <button type="submit" class="btn-search">RECHERCHER</button>
            </form>
        </div>
        <div class="separator"></div>
    </div>
</header>

<main>
    <section class="banner">
        <div class="banner-content">
            Carne • Pesce • Pasta • Dolci • Pizza • Antipasti • Carne • Pesce • Pasta • Dolci • Pizza • Antipasti • Carne • Pesce • Pasta • Dolci • Pizza • Antipasti
        </div>
    </section>

    <section class="suggestions-section">
        <h2 class="section-title">NOS INCONTOURNABLES</h2>
        <div class="suggestions-grid">
            <?php
            if ($platDuJour) echo cartesuggestion($platDuJour, 'Plat du jour');
            foreach ($populaires as $plat) echo cartesuggestion($plat, 'Populaire');
            ?>
        </div>
    </section>

    <section class="about-section">
        <div class="about-text">
            <h2>D'OÙ VIENT LA MAGIE DE L'ANTICA TRATTORIA ?</h2>
            <p>Né d'un rêve familial et d'une passion inébranlable pour la gastronomie italienne, L'Antica Trattoria est plus qu'un restaurant : c'est une invitation au voyage au cœur de l'Italie.</p>
            <p>Depuis notre ouverture, nous avons eu un seul but : partager l'amour et le respect de la cuisine italienne authentique avec nos convives.</p>
        </div>
        <div class="about-images">
            <img src="photo/pizza1.jpeg" alt="Authentique pizza italienne cuite au feu de bois" class="pizza-img">
            <img src="photo/burratta1.jpeg" alt="Burrata crémeuse et tomates fraîches" class="burrata-img">
        </div>
    </section>

    <section class="menu-section">
        <h2>L'essence de la cuisine italienne</h2>
        <div class="menu-grid">
            <?php
            $categories = [
                'Antipasti' => ['carte.php#antipasti', 'photo/burratta2.jpeg', 'Assiette d\'Antipasti italiens'],
                'Carne'     => ['carte.php#carne',     'photo/viande1.jpeg',   'Plat de viande italienne'],
                'Pasta'     => ['carte.php#pasta',     'photo/pates1.jpeg',    'Assiette de pâtes fraîches'],
                'Pizze'     => ['carte.php#pizze',     'photo/pizza2.jpeg',    'Pizza artisanale sortie du four'],
            ];
            foreach ($categories as $titre => [$href, $img, $alt]): ?>
                <div class="menu-item" onclick="location.href='<?= $href ?>'">
                    <span class="menu-item-title"><?= $titre ?></span>
                    <img src="<?= $img ?>" alt="<?= $alt ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="reserve-section">
        <div class="reserve-wrapper">
            <div class="reserve-image">
                <img src="photo/reserver.jpeg" alt="Vue intérieure de notre restaurant L'Antica Trattoria">
            </div>
            <div class="reserve-content">
                <h2>Une Expérience Unique</h2>
                <p>Chez L'Antica Trattoria, chaque repas est une fête qui célèbre la vraie cuisine italienne.</p>
                <p>Nous unissons tradition et créativité pour offrir des moments uniques.</p>
                <?php if (!$connecte): ?>
                    <a href="inscription.php" class="btn-cta">CRÉER UN COMPTE</a>
                <?php else: ?>
                    <a href="carte.php" class="btn-cta">COMMANDER EN LIGNE</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
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
            <h3>Compte Client</h3>
            <?php if ($connecte): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php">Déconnexion</a>
            <?php else: ?>
                <a href="inscription.php">S'inscrire</a>
                <a href="connexion.php">Se connecter</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer-middle">
        <?php
        $interfaces = [
            'Administration' => ['admin.php',       'Interface Administrateur'],
            'Restaurateur'   => ['restaurateur.php', 'Gestion Cuisine (Tablette)'],
            'Livreur'        => ['livreur.php',      'Interface Livraison (Mobile)'],
        ];
        foreach ($interfaces as $titre => [$href, $label]): ?>
            <div class="footer-column">
                <h3><?= $titre ?></h3>
                <a href="<?= $href ?>"><?= $label ?></a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="footer-bottom">
        <p>© 2026 L'Antica Trattoria - Site réalisé par Boualili Kenza et Eish Shahd</p>
    </div>
</footer>

<script src="js/theme.js"></script>
</body>
</html>
