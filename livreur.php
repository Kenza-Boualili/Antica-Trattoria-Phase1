<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'lib/auth.php';
demarrerSession();

requireRole('livreur');

function lireCommandes() {
    $fichier = __DIR__ . '/data/commandes.json';
    if (!file_exists($fichier)) return [];
    return json_decode(file_get_contents($fichier), true) ?? [];
}

function ecrireCommandes($commandes) {
    $fichier = __DIR__ . '/data/commandes.json';
    file_put_contents($fichier, json_encode($commandes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function lireUsers() {
    $fichier = __DIR__ . '/data/users.json';
    if (!file_exists($fichier)) return [];
    return json_decode(file_get_contents($fichier), true) ?? [];
}

$livreurId = $_SESSION['user_id'];
$commandes = lireCommandes();

// Traitement du bouton livraison terminée ou abandonnée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['commande_id'])) {
    $action     = $_POST['action'];
    $commandeId = $_POST['commande_id'];

    foreach ($commandes as &$cmd) {
        if ($cmd['id'] === $commandeId && $cmd['livreur_id'] == $livreurId) {
            if ($action === 'livree') {
                $cmd['statut'] = 'livree';
            } elseif ($action === 'abandonnee') {
                $cmd['statut'] = 'abandonnee';
            }
            break;
        }
    }
    ecrireCommandes($commandes);
    header('Location: livreur.php');
    exit;
}

// Trouver la commande attribuée au livreur connecté
$maCommande = null;
foreach ($commandes as $cmd) {
    if ($cmd['livreur_id'] == $livreurId && $cmd['statut'] === 'en_livraison') {
        $maCommande = $cmd;
        break;
    }
}

// Récupérer les infos du client
$client = null;
if ($maCommande) {
    $users = lireUsers();
    foreach ($users as $u) {
        if ($u['id'] == $maCommande['client_id']) {
            $client = $u;
            break;
        }
    }
}

// Historique des livraisons du livreur
$historique = array_filter($commandes, fn($c) =>
    $c['livreur_id'] == $livreurId && in_array($c['statut'], ['livree', 'abandonnee'])
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Livreur - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <link rel="stylesheet" href="style-livreur.css">
    <style>
        .livreur-container {
            max-width: 500px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .no-commande {
            background: #fff;
            padding: 40px;
            text-align: center;
            border-left: 4px solid var(--color-gold);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .no-commande h3 {
            font-family: var(--font-title);
            color: var(--color-bordeaux);
            margin-bottom: 10px;
        }
        .articles-list {
            background: var(--color-beige);
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .articles-list h4 {
            font-size: 13px;
            text-transform: uppercase;
            color: var(--color-gold);
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .articles-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .articles-list li {
            font-size: 14px;
            padding: 5px 0;
            border-bottom: 1px solid #e0e0e0;
            color: var(--color-dark);
        }
        .articles-list li:last-child { border-bottom: none; }
        .prix-total {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-bordeaux);
            text-align: right;
            margin: 10px 0;
        }
        .btn-abandonnee {
            display: block;
            width: 100%;
            text-align: center;
            padding: 15px 0;
            background-color: #e74c3c;
            color: #fff;
            border: none;
            margin-top: 15px;
            font-weight: 600;
            font-size: 13px;
            border-radius: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
        }
        .historique-block {
            background: #fff;
            padding: 25px;
            margin-top: 20px;
            border-left: 4px solid var(--color-gold);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .historique-block h3 {
            font-family: var(--font-title);
            color: var(--color-bordeaux);
            margin-bottom: 15px;
            font-size: 20px;
        }
        .historique-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .historique-table th {
            text-align: left;
            padding: 8px;
            background: var(--color-beige);
            color: var(--color-bordeaux);
        }
        .historique-table td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--color-beige);
        }
        .status-livree    { color: #27ae60; font-weight: 600; }
        .status-abandonnee { color: #e74c3c; font-weight: 600; }
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
                <?php echo htmlspecialchars($_SESSION['user_prenom']); ?>
            </span>
            <button class="btn-gold" onclick="window.location.href='deconnexion.php'">DÉCONNEXION</button>
        </div>
    </nav>
</header>

<main>
    <div class="livreur-hero">
        <h1>Espace Livreur</h1>
    </div>

    <div class="livreur-container">

        <?php if (!$maCommande): ?>
            <!-- Pas de commande en cours -->
            <div class="no-commande">
                <h3>Aucune course en cours</h3>
                <p style="color:#5a5a5a;">Vous n'avez pas de commande à livrer pour le moment. Le restaurateur vous en attribuera une bientôt.</p>
            </div>

        <?php else: ?>
            <!-- Commande en cours -->
            <div class="delivery-info-card">
                <div class="info-group">
                    <label>COMMANDE</label>
                    <p class="address-text"><?php echo htmlspecialchars($maCommande['id']); ?></p>
                </div>

                <!-- Articles -->
                <div class="articles-list">
                    <h4>Articles à livrer</h4>
                    <ul>
                        <?php foreach ($maCommande['articles'] as $art): ?>
                            <li><?php echo $art['quantite']; ?>x <?php echo htmlspecialchars($art['nom']); ?>
                                — <?php echo number_format($art['prix_unitaire'], 2); ?>€
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="prix-total">Total : <?php echo number_format($maCommande['prix_total'], 2); ?>€</p>
                </div>

                <!-- Adresse -->
                <?php if ($maCommande['adresse_livraison']): $adr = $maCommande['adresse_livraison']; ?>
                <div class="info-group">
                    <label>ADRESSE DE LIVRAISON</label>
                    <p class="address-text">
                        <?php echo htmlspecialchars($adr['adresse'] . ', ' . $adr['code_postal'] . ' ' . $adr['ville']); ?>
                    </p>
                    <?php
                    $adresseEncoded = urlencode($adr['adresse'] . ' ' . $adr['code_postal'] . ' ' . $adr['ville']);
                    ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $adresseEncoded; ?>"
                       target="_blank" class="btn-maps">OUVRIR DANS MAPS</a>
                </div>

                <div class="info-grid">
                    <?php if (!empty($adr['etage'])): ?>
                    <div class="info-item">
                        <label>ÉTAGE</label>
                        <p><?php echo htmlspecialchars($adr['etage']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($adr['interphone'])): ?>
                    <div class="info-item">
                        <label>INTERPHONE</label>
                        <p><?php echo htmlspecialchars($adr['interphone']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($adr['commentaire'])): ?>
                <div class="info-group">
                    <label>COMMENTAIRES</label>
                    <p class="comment-text"><?php echo htmlspecialchars($adr['commentaire']); ?></p>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Téléphone client -->
                <?php if ($client && !empty($client['telephone'])): ?>
                <div class="info-group">
                    <label>TÉLÉPHONE CLIENT</label>
                    <a href="tel:<?php echo preg_replace('/\s/', '', $client['telephone']); ?>"
                       class="btn-phone">APPELER LE CLIENT</a>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <form method="POST" action="livreur.php">
                    <input type="hidden" name="commande_id" value="<?php echo htmlspecialchars($maCommande['id']); ?>">
                    <input type="hidden" name="action" value="livree">
                    <button type="submit" class="btn-complete">INDIQUER LIVRAISON TERMINÉE</button>
                </form>

                <form method="POST" action="livreur.php"
                      onsubmit="return confirm('Confirmer l\'abandon de cette livraison ?')">
                    <input type="hidden" name="commande_id" value="<?php echo htmlspecialchars($maCommande['id']); ?>">
                    <input type="hidden" name="action" value="abandonnee">
                    <button type="submit" class="btn-abandonnee">ADRESSE INTROUVABLE / ABANDONNER</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Historique -->
        <?php if (!empty($historique)): ?>
        <div class="historique-block">
            <h3>Mes dernières livraisons</h3>
            <table class="historique-table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historique as $h): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($h['id']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($h['date_commande'])); ?></td>
                        <td><?php echo number_format($h['prix_total'], 2); ?>€</td>
                        <td>
                            <span class="status-<?php echo $h['statut']; ?>">
                                <?php echo $h['statut'] === 'livree' ? 'Livrée' : 'Abandonnée'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
            <h3>Compte</h3>
            <a href="deconnexion.php">Déconnexion</a>
        </div>
    </div>
    <div class="footer-middle">
        <div class="footer-column">
            <h3>Administration</h3>
            <a href="admin.php">Espace Admin</a>
        </div>
        <div class="footer-column">
            <h3>Restaurateur</h3>
            <a href="restaurateur.php">Tableau de Bord</a>
        </div>
        <div class="footer-column">
            <h3>Livreur</h3>
            <a href="livreur.php">Interface Livreur</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 L'Antica Trattoria - Site réalisé par Boualili Kenza et Eish Shahd</p>
    </div>
</footer>

</body>
</html>