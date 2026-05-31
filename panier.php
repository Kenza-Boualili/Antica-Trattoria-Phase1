<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

requireConnexion();
requireRole('client');

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

// Indexer les plats par id
$platsById = [];
foreach ($plats as $p)
{
    $platsById[$p['id']] = $p;
}

// Initialiser le panier en session
if (!isset($_SESSION['panier']))
{
    $_SESSION['panier'] = [];
}

if (isset($_GET['modifier_commande']))
{
    $cmdIdModif = trim($_GET['modifier_commande']);
    $fichierCommandes = __DIR__ . '/data/commandes.json';
    
    if (file_exists($fichierCommandes))
    {
        $commandesExistantes = json_decode(file_get_contents($fichierCommandes), true) ?? [];
        foreach ($commandesExistantes as $c)
        {
            //on vérifie l'ID, le propriétaire et que le statut est bien 'en_attente'
            if ($c['id'] === $cmdIdModif && $c['client_id'] == $_SESSION['user_id'] && $c['statut'] === 'en_attente')
            {
                $_SESSION['modifier_commande_id'] = $cmdIdModif;
                $_SESSION['panier'] = []; // On réinitialise le panier courant
                
                // On charge les articles de la commande payée dans le panier de session
                foreach ($c['articles'] as $art)
                {
                    $_SESSION['panier'][$art['id']] = [
                        'id'       => $art['id'],
                        'nom'      => $art['nom'],
                        'prix'     => $art['prix_unitaire'],
                        'quantite' => $art['quantite']
                    ];
                }
                
                // Redirection propre pour vider les paramètres d'URL
                header('Location: panier.php');
                exit;
            }
        }
    }
}

// Actions sur le panier
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Ajouter un plat
if (isset($_GET['ajouter']))
{
    $platId = intval($_GET['ajouter']);
    if (isset($platsById[$platId]))
    {
        if (isset($_SESSION['panier'][$platId]))
        {
            $_SESSION['panier'][$platId]['quantite']++;
        }
        else
        {
            $_SESSION['panier'][$platId] = [
                'id'       => $platId,
                'nom'      => $platsById[$platId]['nom'],
                'prix'     => $platsById[$platId]['prix'],
                'quantite' => 1
            ];
        }
    }
    header('Location: panier.php');
    exit;
}

// Modifier la quantité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantite']))
{
    foreach ($_POST['quantite'] as $platId => $qte)
    {
        $platId = intval($platId);
        $qte    = intval($qte);
        if ($qte <= 0)
        {
            unset($_SESSION['panier'][$platId]);
        }
        else
        {
            if (isset($_SESSION['panier'][$platId]))
            {
                $_SESSION['panier'][$platId]['quantite'] = $qte;
            }
        }
    }
    header('Location: panier.php');
    exit;
}

// Supprimer un article
if (isset($_GET['supprimer']))
{
    $platId = intval($_GET['supprimer']);
    unset($_SESSION['panier'][$platId]);
    header('Location: panier.php');
    exit;
}

// Vider le panier
if (isset($_GET['vider']))
{
    $_SESSION['panier'] = [];
    unset($_SESSION['modifier_commande_id']); 
    header('Location: panier.php');
    exit;
}

// Calculer le total
$total = 0;
foreach ($_SESSION['panier'] as $item)
{
    $total += $item['prix'] * $item['quantite'];
}

$user = getUtilisateurConnecte();

// Appliquer la remise
$remise      = $user['remise'] ?? 0;
$totalRemise = $total * (1 - $remise / 100);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    
    <?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre'): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>

    <style>
        .panier-hero {
            height: 25vh;
            background-image: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url("photo/restophoto.jpeg");
            background-position: center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 70px;
        }

        .panier-hero h1 {
            font-family: var(--font-title);
            font-size: 52px;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 4px;
        }

        .modification-banner {
            background-color: #e67e22;
            color: #ffffff;
            padding: 15px 30px;
            text-align: center;
            font-weight: 400;
            font-size: 15px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .modification-banner strong { font-weight: 600; }
        .modification-banner a { color: #fff; text-decoration: underline; margin-left: 15px; font-weight: 600; }

        .panier-section {
            padding: 60px 80px 100px 80px;
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        .panier-items { flex: 2; }
        .panier-resume { flex: 1; }

        .panier-block {
            background: #fff;
            padding: 30px;
            border-left: 4px solid var(--color-gold);
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .panier-block h2 {
            font-family: var(--font-title);
            font-size: 24px;
            color: var(--color-bordeaux);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--color-beige);
        }

        .panier-vide {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .panier-vide p { margin-bottom: 20px; font-size: 16px; }

        .panier-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .panier-table th {
            text-align: left;
            padding: 10px;
            background: var(--color-beige);
            color: var(--color-bordeaux);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .panier-table td {
            padding: 15px 10px;
            border-bottom: 1px solid var(--color-beige);
            vertical-align: middle;
        }

        .qte-input {
            width: 60px;
            padding: 5px;
            border: 1px solid var(--color-gold);
            text-align: center;
            font-family: var(--font-main);
            font-size: 14px;
        }

        .btn-supprimer {
            background: none;
            border: none;
            color: #c0392b;
            cursor: pointer;
            font-size: 18px;
            padding: 0 5px;
        }

        .btn-update {
            background: var(--color-bordeaux);
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-family: var(--font-main);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 15px;
            transition: background 0.3s;
        }

        .btn-update:hover { background: var(--color-gold); }

        .resume-ligne {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--color-beige);
            font-size: 14px;
        }

        .resume-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--color-bordeaux);
        }

        .remise-badge {
            display: inline-block;
            background: #27ae60;
            color: #fff;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .btn-commander {
            display: block;
            width: 100%;
            background: var(--color-bordeaux);
            color: #fff;
            border: none;
            padding: 15px;
            font-family: var(--font-main);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            margin-top: 20px;
            text-align: center;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn-commander:hover { background: var(--color-gold); }

        .btn-vider {
            display: block;
            text-align: center;
            color: #c0392b;
            font-size: 13px;
            margin-top: 10px;
            text-decoration: underline;
            cursor: pointer;
        }

        .btn-continuer {
            display: inline-block;
            margin-top: 15px;
            color: var(--color-gold);
            text-decoration: underline;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .panier-section { flex-direction: column; padding: 30px 20px; }
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
            <button class="btn-gold" onclick="window.location.href='profil.php'">MON PROFIL</button>
            <button class="btn-gold" onclick="window.location.href='deconnexion.php'">DÉCONNEXION</button>
            
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold" aria-label="Changer le thème d'affichage">
                <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre'; ?>
            </button>
        </div>
    </nav>
</header>

<main>
    <div class="panier-hero">
        <h1>Mon Panier</h1>
    </div>

    <?php if (isset($_SESSION['modifier_commande_id'])): ?>
        <div class="modification-banner">
            ⚠️ <strong>Mode Modification Actif :</strong> Vous ajustez la composition de la commande payée <strong>#<?php echo htmlspecialchars($_SESSION['modifier_commande_id']); ?></strong>. 
            <a href="panier.php?vider=1">Annuler les changements</a>
        </div>
    <?php endif; ?>

    <div class="panier-section">

        <div class="panier-items">
            <div class="panier-block">
                <h2>Mes Articles</h2>

                <?php if (empty($_SESSION['panier'])): ?>
                    <div class="panier-vide">
                        <p>Votre panier est vide.</p>
                        <a href="carte.php" class="btn-commander">VOIR LA CARTE</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="panier.php">
                        <input type="hidden" name="action" value="update">
                        <table class="panier-table">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                    <th>Sous-total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['panier'] as $item): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['nom']); ?></strong></td>
                                        <td><?php echo number_format($item['prix'], 2, ',', ''); ?> €</td>
                                        <td>
                                            <input type="number" class="qte-input"
                                                   name="quantite[<?php echo $item['id']; ?>]"
                                                   value="<?php echo $item['quantite']; ?>"
                                                   min="0" max="20">
                                        </td>
                                        <td><strong><?php echo number_format($item['prix'] * $item['quantite'], 2, ',', ''); ?> €</strong></td>
                                        <td>
                                            <a href="panier.php?supprimer=<?php echo $item['id']; ?>">
                                                <button type="button" class="btn-supprimer" title="Supprimer">✕</button>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn-update">Mettre à jour le panier</button>
                    </form>

                    <a href="carte.php" class="btn-continuer">← Continuer mes achats</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($_SESSION['panier'])): ?>
            <div class="panier-resume">
                <div class="panier-block">
                    <h2>Résumé</h2>

                    <?php foreach ($_SESSION['panier'] as $item): ?>
                        <div class="resume-ligne">
                            <span><?php echo $item['quantite']; ?>x <?php echo htmlspecialchars($item['nom']); ?></span>
                            <span><?php echo number_format($item['prix'] * $item['quantite'], 2, ',', ''); ?> €</span>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($remise > 0): ?>
                        <div class="resume-ligne">
                            <span>Sous-total</span>
                            <span><?php echo number_format($total, 2, ',', ''); ?> €</span>
                        </div>
                        <div class="resume-ligne" style="color:#27ae60;">
                            <span>Remise <span class="remise-badge">-<?php echo $remise; ?>%</span></span>
                            <span>-<?php echo number_format($total - $totalRemise, 2, ',', ''); ?> €</span>
                        </div>
                    <?php endif; ?>

                    <div class="resume-total">
                        <span>Total</span>
                        <span><?php echo number_format($totalRemise, 2, ',', ''); ?> €</span>
                    </div>

                    <a href="commande.php" class="btn-commander">
                        <?php echo isset($_SESSION['modifier_commande_id']) ? 'Enregistrer les modifications' : 'Passer la commande'; ?>
                    </a>
                    
                    <a href="panier.php?vider=1" class="btn-vider">
                        <?php echo isset($_SESSION['modifier_commande_id']) ? 'Annuler et vider' : 'Vider le panier'; ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
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
            <a href="profil.php">Mon Profil</a>
            <a href="deconnexion.php">Déconnexion</a>
        </div>
    </div>
    <div class="footer-middle">
        <div class="footer-column">
            <h3>Administration</h3>
            <a href="admin.php">Interface Administrateur</a>
        </div>
        <div class="footer-column">
            <h3>Restaurateur</h3>
            <a href="restaurateur.php">Gestion Cuisine</a>
        </div>
        <div class="footer-column">
            <h3>Livreur</h3>
            <a href="livreur.php">Interface Livraison</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 L'Antica Trattoria - Site réalisé par Boualili Kenza et Eish Shahd</p>
    </div>
</footer>
<script src="js/theme.js"></script>
</body>
</html>
