<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

requireRole('restaurateur');

function lireCommandes()
{
    $fichier = __DIR__ . '/data/commandes.json';
    if (!file_exists($fichier))
    {
        return [];
    }
    return json_decode(file_get_contents($fichier), true) ?? [];
}

function lirePlats()
{
    $fichier = __DIR__ . '/data/plats.json';
    if (!file_exists($fichier))
    {
        return [];
    }
    return json_decode(file_get_contents($fichier), true) ?? [];
}

function lireUsers()
{
    $fichier = __DIR__ . '/data/users.json';
    if (!file_exists($fichier))
    {
        return [];
    }
    return json_decode(file_get_contents($fichier), true) ?? [];
}

$commandes = lireCommandes();
$users     = lireUsers();

// Indexer les users par id
$usersById = [];
foreach ($users as $u)
{
    $usersById[$u['id']] = $u;
}

// Séparer les commandes par statut
$aPrepar   = array_filter($commandes, fn($c) => $c['statut'] === 'en_preparation');
$enAttente = array_filter($commandes, fn($c) => $c['statut'] === 'en_attente');
$enLivr    = array_filter($commandes, fn($c) => $c['statut'] === 'en_livraison');
$livrees   = array_filter($commandes, fn($c) => $c['statut'] === 'livree');

// Livreurs disponibles (pour le menu déroulant)
$livreurs = array_filter($users, fn($u) => $u['role'] === 'livreur' && $u['actif']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Restaurateur - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <link rel="stylesheet" href="style-restaurateur.css">
    
    <?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre'): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>

    <style>
        .resto-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-left: 4px solid var(--color-gold); text-align: center; }
        .stat-number { font-family: var(--font-title); font-size: 36px; color: var(--color-bordeaux); display: block; }
        .stat-label { font-size: 12px; color: #5a5a5a; text-transform: uppercase; letter-spacing: 1px; }
        .order-time { font-size: 12px; color: #999; margin-bottom: 8px; }
        .order-type { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; margin-bottom: 8px; }
        .type-livraison { background: #e8f4fd; color: #2980b9; }
        .type-sur_place { background: #e8f8f5; color: #27ae60; }
        .type-emporter { background: #fef9e7; color: #d35400; }
        .client-name { font-weight: 600; color: var(--color-dark); margin-bottom: 5px; }
        .adresse-livraison { font-size: 12px; color: #5a5a5a; margin: 5px 0; font-style: italic; }
        .select-livreur { width: 100%; padding: 8px; border: 1px solid var(--color-gold); font-family: var(--font-main); font-size: 13px; margin: 8px 0; background: #fff; }
        .section-title-resto { font-family: var(--font-title); font-size: 20px; color: var(--color-bordeaux); margin: 0; }
        .empty-state { text-align: center; padding: 30px; color: #999; font-style: italic; }
        .order-card { transition: all 0.4s ease; }
    </style>
</head>
<body>

<header>
    <nav id="navbar" class="nav-scrolled">
        <div class="logo">L'Antica Trattoria</div>
        <ul class="nav-links">
            <li><a href="index.php">ACCUEIL</a></li>
        </ul>
        <div class="nav-buttons">
            <span style="color:#fff; font-size:13px; margin-right:10px;">
                Chef : <?php echo htmlspecialchars($_SESSION['user_prenom']); ?>
            </span>
            <button class="btn-gold" onclick="window.location.href='deconnexion.php'">DÉCONNEXION</button>
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold" aria-label="Changer le thème d'affichage">
                <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre'; ?>
            </button>
        </div>
    </nav>
</header>

<main>
    <div class="resto-hero">
        <div class="resto-hero-overlay">
            <h1>Gestion Cuisine</h1>
            <div class="separator"></div>
        </div>
    </div>

    <section class="resto-section">
        <header class="resto-intro">
            <h2>TABLEAU DE BORD</h2>
            <p>Gérez le flux de vos commandes en temps réel.</p>
        </header>

        <div class="resto-container">

            <div class="resto-stats">
                <div class="stat-card">
                    <span class="stat-number" id="count-prepar"><?php echo count($aPrepar); ?></span>
                    <span class="stat-label">À préparer</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($enAttente); ?></span>
                    <span class="stat-label">En attente</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($enLivr); ?></span>
                    <span class="stat-label">En livraison</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($livrees); ?></span>
                    <span class="stat-label">Livrées</span>
                </div>
            </div>

            <div class="resto-block">
                <div class="block-header">
                    <h3 class="section-title-resto">Commandes à préparer</h3>
                    <span class="badge"><?php echo count($aPrepar); ?> en cours</span>
                </div>

                <?php if (empty($aPrepar)): ?>
                    <p class="empty-state">Aucune commande à préparer pour le moment.</p>
                <?php else: ?>
                    <div class="orders-grid">
                        <?php foreach ($aPrepar as $cmd):
                            $client = $usersById[$cmd['client_id']] ?? null;
                        ?>
                            <div class="order-card" id="card-<?php echo $cmd['id']; ?>">
                                <div class="order-id"><?php echo htmlspecialchars($cmd['id']); ?></div>
                                <p class="order-time"><?php echo date('H:i', strtotime($cmd['date_commande'])); ?></p>
                                
                                <span class="order-type type-<?php echo $cmd['type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $cmd['type'])); ?>
                                </span>

                                <div class="order-details">
                                    <p class="client-name"><?php echo $client ? htmlspecialchars($client['prenom'] . ' ' . $client['nom']) : 'Client inconnu'; ?></p>
                                    <ul class="items-list">
                                        <?php foreach ($cmd['articles'] as $art): ?>
                                            <li><strong><?php echo $art['quantite']; ?>x</strong> <?php echo htmlspecialchars($art['nom']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <?php if ($cmd['type'] === 'livraison' && $cmd['adresse_livraison']): ?>
                                        <p class="adresse-livraison">📍 <?php echo htmlspecialchars($cmd['adresse_livraison']['adresse']); ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($cmd['type'] === 'livraison'): ?>
                                    <select class="select-livreur" id="livreur-<?php echo $cmd['id']; ?>">
                                        <option value="">-- Choisir un livreur --</option>
                                        <?php foreach ($livreurs as $liv): ?>
                                            <option value="<?php echo $liv['id']; ?>">
                                                <?php echo htmlspecialchars($liv['prenom'] . ' ' . $liv['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn-action" onclick="changerStatutResto('<?php echo $cmd['id']; ?>', 'en_livraison')">
                                        PASSER EN LIVRAISON
                                    </button>
                                <?php else: ?>
                                    <button class="btn-action" onclick="changerStatutResto('<?php echo $cmd['id']; ?>', 'livree')">
                                        MARQUER COMME PRÊT
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($enAttente)): ?>
                <div class="resto-block">
                    <div class="block-header">
                        <h3 class="section-title-resto">Commandes planifiées</h3>
                    </div>
                    <div class="orders-grid">
                        <?php foreach ($enAttente as $cmd): ?>
                            <div class="order-card" id="card-<?php echo $cmd['id']; ?>">
                                <div class="order-id"><?php echo htmlspecialchars($cmd['id']); ?></div>
                                <p class="order-time">Pour : <?php echo date('H:i', strtotime($cmd['date_preparation_souhaitee'])); ?></p>
                                <button class="btn-action" onclick="changerStatutResto('<?php echo $cmd['id']; ?>', 'en_preparation')">
                                    LANCER LA PRÉPARATION
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>
</main>

<footer>
    <div class="footer-bottom">
        <p>© 2026 L'Antica Trattoria - Site réalisé par Boualili Kenza et Eish Shahd</p>
    </div>
</footer>

<script src="js/theme.js"></script>
<script src="js/restaurateur.js"></script>
</body>
</html>
