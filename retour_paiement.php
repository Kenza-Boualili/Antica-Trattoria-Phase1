<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
require_once 'getapikey.php';
demarrerSession();

define('CYBANK_VENDEUR', 'MEF-2_E');

function lireCommandes()
{
    $fichier = __DIR__ . '/data/commandes.json';
    if (!file_exists($fichier))
    {
        return [];
    }
    return json_decode(file_get_contents($fichier), true) ?? [];
}

function ecrireCommandes($commandes)
{
    $fichier = __DIR__ . '/data/commandes.json';
    file_put_contents($fichier, json_encode($commandes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function lirePaiements()
{
    $fichier = __DIR__ . '/data/paiements.json';
    if (!file_exists($fichier))
    {
        return [];
    }
    return json_decode(file_get_contents($fichier), true) ?? [];
}

function ecrirePaiements($paiements)
{
    $fichier = __DIR__ . '/data/paiements.json';
    file_put_contents($fichier, json_encode($paiements, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Récupérer les paramètres de retour CYBank
$transaction = $_GET['transaction'] ?? '';
$montant     = $_GET['montant']     ?? '';
$vendeur     = $_GET['vendeur']     ?? '';
$statut      = $_GET['status']      ?? '';
$controlRecu = $_GET['control']     ?? '';

$paiementOk  = false;
$erreurMsg   = '';

// Vérifier la valeur de contrôle
if (!empty($transaction) && !empty($montant) && !empty($vendeur) && !empty($statut))
{
    $api_key     = getAPIKey($vendeur);
    $controlCalc = md5($api_key . '#' . $transaction . '#' . $montant . '#' . $vendeur . '#' . $statut . '#');

    if ($controlCalc === $controlRecu && $statut === 'accepted')
    {
        $paiementOk = true;
    }
    elseif ($statut !== 'accepted')
    {
        $erreurMsg = 'Paiement refusé par CYBank.';
    }
    else
    {
        $erreurMsg = 'Erreur de contrôle : transaction invalide.';
    }
}

// Mettre à jour la commande
$commandeId = $_SESSION['commande_en_cours'] ?? '';
$isModification = isset($_SESSION['modifier_commande_id']);

// Préparer les messages personnalisés pour la vue HTML avant de nettoyer la session
$msgSuccesTitre = $isModification ? 'Commande modifiée !' : 'Commande confirmée !';
$msgSuccesDesc  = $isModification 
    ? 'Les modifications ont bien été enregistrées et le règlement de la différence a été validé.' 
    : 'Votre paiement a été accepté et votre commande est en cours de préparation.';

if (!empty($commandeId))
{
    $commandes  = lireCommandes();
    $paiements  = lirePaiements();

    foreach ($commandes as &$cmd)
    {
        if ($cmd['id'] === $commandeId)
        {
            if ($paiementOk)
            {
                if ($isModification && isset($_SESSION['modif_temp_articles']))
                {
                    // On applique la nouvelle liste d'articles et le nouveau prix cumulé
                    $cmd['articles']   = $_SESSION['modif_temp_articles'];
                    $cmd['prix_total'] = $_SESSION['modif_temp_total'];
                    $cmd['statut']     = 'en_attente'; // Reste en attente de traitement cuisine
                }
                else
                {
                    // Commande classique : Passage standard en préparation
                    $cmd['statut'] = 'en_preparation';
                }

                $paiementId         = 'PAY-' . strtoupper(substr(md5($transaction), 0, 6));
                $cmd['paiement_id'] = $paiementId;

                // Enregistrer le reçu du paiement (soit le montant total, soit le delta)
                $paiements[] = [
                    'id'                    => $paiementId,
                    'commande_id'           => $commandeId,
                    'client_id'             => $cmd['client_id'],
                    'montant'               => floatval($montant),
                    'statut'                => 'validé',
                    'methode'               => 'carte',
                    'cybank_transaction_id' => $transaction,
                    'date_transaction'      => date('Y-m-d\TH:i:s')
                ];
                ecrirePaiements($paiements);
            }
            else
            {
                //Si le paiement échoue sur une modification, on N'ANNULE PAS la commande !
                // Le client a déjà payé son panier initial, on rejette juste ses modifications.
                if (!$isModification)
                {
                    $cmd['statut'] = 'annulee';
                }
            }
            break;
        }
    }
    ecrireCommandes($commandes);

    if ($paiementOk)
    {
        $_SESSION['panier'] = [];
        unset($_SESSION['commande_en_cours']);
        unset($_SESSION['cybank_transaction']);
        unset($_SESSION['cybank_montant']);
        unset($_SESSION['modifier_commande_id']);
        unset($_SESSION['modif_temp_articles']);
        unset($_SESSION['modif_temp_total']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    
    <?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre'): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>

    <style>
        .confirmation-section {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px 20px;
        }

        .confirmation-card {
            background: #fff;
            padding: 60px 50px;
            border-left: 4px solid var(--color-gold);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .icone-ok { font-size: 60px; margin-bottom: 20px; }
        .icone-err { font-size: 60px; margin-bottom: 20px; }

        .confirmation-card h1 {
            font-family: var(--font-title);
            font-size: 32px;
            color: var(--color-bordeaux);
            margin-bottom: 15px;
        }

        .confirmation-card p {
            font-size: 15px;
            color: #5a5a5a;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .commande-ref {
            background: var(--color-beige);
            padding: 10px 20px;
            font-size: 18px;
            font-weight: 600;
            color: var(--color-bordeaux);
            margin: 20px 0;
            letter-spacing: 2px;
        }

        .btn-retour {
            display: inline-block;
            margin-top: 25px;
            background: var(--color-bordeaux);
            color: #fff;
            padding: 14px 35px;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 2px;
            transition: background 0.3s;
        }

        .btn-retour:hover { background: var(--color-gold); }

        .btn-retour-sec {
            display: inline-block;
            margin-top: 15px;
            color: var(--color-gold);
            text-decoration: underline;
            font-size: 14px;
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
            
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold">
                <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre'; ?>
            </button>
        </div>
    </nav>
</header>

<main>
    <section class="confirmation-section">
        <div class="confirmation-card">

            <?php if ($paiementOk): ?>
                <div class="icone-ok">✅</div>
                <h1><?php echo htmlspecialchars($msgSuccesTitre); ?></h1>
                <p><?php echo htmlspecialchars($msgSuccesDesc); ?></p>
                <div class="commande-ref"><?php echo htmlspecialchars($commandeId); ?></div>
                <p>Vous pouvez suivre le statut de votre commande depuis votre profil.</p>
                <a href="profil.php" class="btn-retour">Voir mon profil</a>
                <br>
                <a href="carte.php" class="btn-retour-sec">Continuer mes achats</a>

            <?php else: ?>
                <div class="icone-err">❌</div>
                <h1>Paiement refusé</h1>
                <p><?php echo htmlspecialchars($erreurMsg ?: 'Une erreur est survenue lors du paiement.'); ?></p>
                <p><?php echo $isModification ? 'Les modifications ont été annulées. Votre commande initiale reste inchangée.' : 'Votre commande a été annulée. Aucun montant n\'a été débité.'; ?></p>
                <a href="panier.php" class="btn-retour">Retour au panier</a>
                <br>
                <a href="carte.php" class="btn-retour-sec">Continuer mes achats</a>
            <?php endif; ?>

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