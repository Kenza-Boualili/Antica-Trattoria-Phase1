<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

function lirePlats()
{
    $fichier = __DIR__ . '/data/plats.json';
    
    if (!file_exists($fichier))
    {
        return [];
    }
    
    return json_decode(file_get_contents($fichier), true) ?? [];
}

$plats = lirePlats();

// Plats mis en avant (plat du jour + populaires)
$platDuJour = null;
$populaires = [];

foreach ($plats as $plat)
{
    if ($plat['id'] === 13)
    {
        $platDuJour = $plat; // Osso Buco
    }
    
    if (in_array($plat['id'], [15, 10]))
    {
        $populaires[] = $plat; // Margherita, Carbonara
    }
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
    
    <?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre'): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>
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
            <?php if (estConnecte()): ?>
                <button class="btn-gold" onclick="window.location.href='profil.php'">
                    MON PROFIL
                </button>
                
                <?php if (!empty($_SESSION['panier'])): ?>
                    <button class="btn-gold" onclick="window.location.href='panier.php'">
                        🛒 PANIER (<?php echo array_sum(array_column($_SESSION['panier'], 'quantite')); ?>)
                    </button>
                <?php endif; ?>
                
                <button class="btn-gold" onclick="window.location.href='deconnexion.php'">
                    DÉCONNEXION
                </button>
            <?php else: ?>
                <button class="btn-gold" onclick="window.location.href='connexion.php'">SE CONNECTER</button>
                <button class="btn-gold" onclick="window.location.href='inscription.php'">S'INSCRIRE</button>
            <?php endif; ?>

            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold" aria-label="Changer le thème d'affichage">
                <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre'; ?>
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

            <?php if ($platDuJour): ?>
                <div class="suggestion-card">
                    <div class="dish-tag">Plat du jour</div>
                    <img src="<?php echo htmlspecialchars($platDuJour['image']); ?>"
                         alt="Plat du jour : <?php echo htmlspecialchars($platDuJour['nom']); ?>">
                    <h3><?php echo htmlspecialchars($platDuJour['nom']); ?></h3>
                    <p><?php echo htmlspecialchars($platDuJour['description']); ?></p>
                    <p style="color:var(--color-bordeaux); font-weight:600; margin-top:8px;">
                        <?php echo number_format($platDuJour['prix'], 2, ',', ''); ?> €
                    </p>
                </div>
            <?php endif; ?>

            <?php foreach ($populaires as $plat): ?>
                <div class="suggestion-card">
                    <div class="dish-tag">Populaire</div>
                    <img src="<?php echo htmlspecialchars($plat['image']); ?>"
                         alt="Plat populaire : <?php echo htmlspecialchars($plat['nom']); ?>">
                    <h3><?php echo htmlspecialchars($plat['nom']); ?></h3>
                    <p><?php echo htmlspecialchars($plat['description']); ?></p>
                    <p style="color:var(--color-bordeaux); font-weight:600; margin-top:8px;">
                        <?php echo number_format($plat['prix'], 2, ',', ''); ?> €
                    </p>
                </div>
            <?php endforeach; ?>

        </div>
    </section>

    <section class="about-section">
        <div class="about-text">
            <h2>D'OÙ VIENT LA MAGIE DE L'ANTICA TRATTORIA ?</h2>
            <p>Né d'un rêve familial et d'une passion inébranlable pour la gastronomie italienne, L'Antica Trattoria est plus qu'un restaurant : c'est une invitation au voyage au cœur de l'Italie.</p>
            <p>Depuis notre ouverture, nous avons eu un seul but : partager l'amour et le respect de la cuisine italienne authentique avec nos convives.</p>
        </div>
        <div class="about-images">
            <img src="photo/pizza1.jpeg" alt="Photographie d'une authentique pizza italienne cuite au feu de bois" class="pizza-img">
            <img src="photo/burratta1.jpeg" alt="Photographie d'une burrata crémeuse et tomates fraîches" class="burrata-img">
        </div>
    </section>

    <section class="menu-section">
        <h2>L'essence de la cuisine italienne</h2>
        <div class="menu-grid">
            <div class="menu-item" onclick="window.location.href='carte.php#antipasti'">
                <span class="menu-item-title">Antipasti</span>
                <img src="photo/burratta2.jpeg" alt="Assiette d'Antipasti italiens">
            </div>
            <div class="menu-item" onclick="window.location.href='carte.php#carne'">
                <span class="menu-item-title">Carne</span>
                <img src="photo/viande1.jpeg" alt="Plat de viande italienne préparée">
            </div>
            <div class="menu-item" onclick="window.location.href='carte.php#pasta'">
                <span class="menu-item-title">Pasta</span>
                <img src="photo/pates1.jpeg" alt="Assiette de pâtes fraîches traditionnelles">
            </div>
            <div class="menu-item" onclick="window.location.href='carte.php#pizze'">
                <span class="menu-item-title">Pizze</span>
                <img src="photo/pizza2.jpeg" alt="Pizza artisanale sortie du four">
            </div>
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
                
                <?php if (!estConnecte()): ?>
                    <a href="inscription.php" style="display:inline-block; margin-top:20px;
                       background:var(--color-gold); color:#fff; padding:12px 30px;
                       text-decoration:none; text-transform:uppercase; font-size:13px;
                       letter-spacing:2px;">
                        CRÉER UN COMPTE
                    </a>
                <?php else: ?>
                    <a href="carte.php" style="display:inline-block; margin-top:20px;
                       background:var(--color-gold); color:#fff; padding:12px 30px;
                       text-decoration:none; text-transform:uppercase; font-size:13px;
                       letter-spacing:2px;">
                        COMMANDER EN LIGNE
                    </a>
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
            <?php if (estConnecte()): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php">Déconnexion</a>
            <?php else: ?>
                <a href="inscription.php">S'inscrire</a>
                <a href="connexion.php">Se connecter</a>
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
<script src="js/theme.js"></script>
</body>
</html>
