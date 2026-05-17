<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

requireRole('livreur');

function lireCommandes()
{
    $fichier = __DIR__ . '/data/commandes.json';
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

$livreurId = $_SESSION['user_id'];
$commandes = lireCommandes();

// Trouver la commande attribuée au livreur connecté
$maCommande = null;
foreach ($commandes as $cmd)
{
    if ($cmd['livreur_id'] == $livreurId && $cmd['statut'] === 'en_livraison')
    {
        $maCommande = $cmd;
        break;
    }
}

// Récupérer les infos du client
$client = null;
if ($maCommande)
{
    $users = lireUsers();
    foreach ($users as $u)
    {
        if ($u['id'] == $maCommande['client_id'])
        {
            $client = $u;
            break;
        }
    }
}

// Historique des livraisons 
$historique = array_filter($commandes, fn($c) =>
    $c['livreur_id'] == $livreurId && in_array($c['statut'], ['livree', 'abandonnee'])
);
usort($historique, fn($a, $b) => strcmp($b['date_commande'], $a['date_commande']));
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
    
    <?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre'): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>

    <style>
        .livreur-container { max-width: 500px; margin: 30px auto; padding: 0 20px; }
        .no-commande { background: #fff; padding: 40px; text-align: center; border-left: 4px solid var(--color-gold); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .delivery-info-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        .articles-list { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .prix-total { font-size: 18px; font-weight: 600; color: var(--color-bordeaux); text-align: right; }
        .btn-complete { display: block; width: 100%; padding: 15px; background: #27ae60; color: #fff; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; margin-top: 20px; }
        .btn-abandonnee { display: block; width: 100%; padding: 12px; background: #e74c3c; color: #fff; border: none; border-radius: 5px; font-size: 12px; cursor: pointer; margin-top: 10px; }
        .status-livree { color: #27ae60; font-weight: 600; }
        .status-abandonnee { color: #e74c3c; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <nav id="navbar" class="nav-scrolled">
        <div class="logo">L'Antica Trattoria</div>
        <div class="nav-buttons">
            <span style="color:#fff; font-size:12px; margin-right:5px;"><?php echo htmlspecialchars($_SESSION['user_prenom']); ?></span>
            
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold">
                <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre'; ?>
            </button>
            
            <button class="btn-gold" onclick="window.location.href='deconnexion.php'">X</button>
        </div>
    </nav>
</header>

<main>
    <div class="livreur-container">
        <h2 style="font-family: var(--font-title); color: var(--color-bordeaux); margin-bottom: 20px;">Ma Course</h2>

        <?php if (!$maCommande): ?>
            <div class="no-commande">
                <h3>Aucune course</h3>
                <p>En attente d'une nouvelle commande du Chef...</p>
            </div>
        <?php else: ?>
            <div class="delivery-info-card" id="card-<?php echo $maCommande['id']; ?>">
                <div class="info-group">
                    <label>COMMANDE #<?php echo htmlspecialchars($maCommande['id']); ?></label>
                </div>

                <div class="articles-list">
                    <ul>
                        <?php foreach ($maCommande['articles'] as $art): ?>
                            <li><?php echo $art['quantite']; ?>x <?php echo htmlspecialchars($art['nom']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="prix-total"><?php echo number_format($maCommande['prix_total'], 2); ?>€</p>
                </div>

                <?php if ($maCommande['adresse_livraison']): ?>
                    <div class="info-group">
                        <label>DESTINATION</label>
                        <p><strong><?php echo htmlspecialchars($maCommande['adresse_livraison']['adresse']); ?></strong></p>
                        <p><?php echo htmlspecialchars($maCommande['adresse_livraison']['ville']); ?></p>
                        
                        <?php if ($client && $client['telephone']): ?>
                            <p style="margin-top:10px;">📞 <a href="tel:<?php echo $client['telephone']; ?>"><?php echo $client['telephone']; ?></a></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <button class="btn-complete" onclick="validerLivraison('<?php echo $maCommande['id']; ?>', 'livree')">
                    LIVRAISON TERMINÉE ✅
                </button>

                <button class="btn-abandonnee" onclick="validerLivraison('<?php echo $maCommande['id']; ?>', 'abandonnee')">
                    ADRESSE INTROUVABLE / ANNULER ❌
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($historique)): ?>
            <div class="historique-block" style="margin-top:40px;">
                <h3 style="font-size:16px;">Historique récent</h3>
                <table class="historique-table">
                    <?php foreach (array_slice($historique, 0, 5) as $h): ?>
                        <tr>
                            <td>#<?php echo $h['id']; ?></td>
                            <td><?php echo number_format($h['prix_total'], 2); ?>€</td>
                            <td class="status-<?php echo $h['statut']; ?>">
                                <?php echo $h['statut'] === 'livree' ? 'OK' : 'Abandon'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="js/theme.js"></script>
<script src="js/livreur.js"></script>
</body>
</html>