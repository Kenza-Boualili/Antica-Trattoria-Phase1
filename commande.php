<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
require_once 'getapikey.php';
demarrerSession();
requireConnexion();
requireRole('client');

define('CYBANK_URL',    'https://www.plateforme-smc.fr/cybank/index.php');
define('CYBANK_VENDEUR','MEF-2_E');
define('RETOUR_URL',    'http://localhost:8000/retour_paiement.php');

function lirePlats() {
    $f = __DIR__ . '/data/plats.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?? []) : [];
}

function lireCommandes() {
    $f = __DIR__ . '/data/commandes.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?? []) : [];
}

function ecrireCommandes($c) {
    file_put_contents(__DIR__ . '/data/commandes.json',
        json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if (empty($_SESSION['panier'])) { header('Location: panier.php'); exit; }

$user  = getUtilisateurConnecte();

$total = 0;
foreach ($_SESSION['panier'] as $item) $total += $item['prix'] * $item['quantite'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeCommande = $_POST['type_commande'] ?? 'livraison';
    $preparImmed  = isset($_POST['preparation_immediate']);
    $datePlanif   = $_POST['date_planifiee'] ?? '';

    $commandes = lireCommandes();

    $articles = array_map(fn($item) => [
        'type'          => 'plat',
        'id'            => $item['id'],
        'nom'           => $item['nom'],
        'quantite'      => $item['quantite'],
        'prix_unitaire' => $item['prix']
    ], $_SESSION['panier']);

    $totalFinal = round($total, 2);
    $isModification = isset($_SESSION['modifier_commande_id']);

    if ($isModification) {
        $nouvelId   = $_SESSION['modifier_commande_id'];
        $ancienPrix = 0;
        foreach ($commandes as $c) {
            if ($c['id'] === $nouvelId) { $ancienPrix = $c['prix_total']; break; }
        }

        $difference = round($totalFinal - $ancienPrix, 2);
        $_SESSION['modif_temp_articles'] = $articles;
        $_SESSION['modif_temp_total']    = $totalFinal;
        $_SESSION['commande_en_cours']   = $nouvelId;

        if ($difference <= 0) {
            foreach ($commandes as &$cmd) {
                if ($cmd['id'] === $nouvelId) {
                    $cmd['articles']   = $articles;
                    $cmd['prix_total'] = $totalFinal;
                    $cmd['type']       = $typeCommande;
                    $cmd['adresse_livraison'] = $typeCommande === 'livraison' ? [
                        'adresse'     => $user['adresse'],
                        'ville'       => $user['ville'],
                        'code_postal' => $user['code_postal'],
                        'etage'       => $user['etage'],
                        'interphone'  => $user['interphone'],
                        'commentaire' => trim($_POST['commentaire'] ?? '')
                    ] : null;
                    break;
                }
            }
            ecrireCommandes($commandes);
            $_SESSION['panier'] = [];
            unset($_SESSION['modifier_commande_id'], $_SESSION['modif_temp_articles'],
                  $_SESSION['modif_temp_total'],    $_SESSION['commande_en_cours']);
            header('Location: profil.php?succes=modifiee');
            exit;
        }
        $totalFinal = $difference;

    } else {
        $nouvelId    = 'ORD-' . strtoupper(substr(uniqid(), -6));
        $commandes[] = [
            'id'                         => $nouvelId,
            'client_id'                  => $user['id'],
            'type'                       => $typeCommande,
            'adresse_livraison'          => $typeCommande === 'livraison' ? [
                'adresse'     => $user['adresse'],
                'ville'       => $user['ville'],
                'code_postal' => $user['code_postal'],
                'etage'       => $user['etage'],
                'interphone'  => $user['interphone'],
                'commentaire' => trim($_POST['commentaire'] ?? '')
            ] : null,
            'articles'                   => $articles,
            'prix_total'                 => $totalFinal,
            'statut'                     => 'en_attente_paiement',
            'livreur_id'                 => null,
            'paiement_id'                => null,
            'preparation_immediate'      => $preparImmed,
            'date_commande'              => date('Y-m-d\TH:i:s'),
            'date_preparation_souhaitee' => (!$preparImmed && !empty($datePlanif))
                                            ? date('Y-m-d\TH:i:s', strtotime($datePlanif))
                                            : null,
            'note_livraison'             => null,
            'note_produits'              => null
        ];
        ecrireCommandes($commandes);
        $_SESSION['commande_en_cours'] = $nouvelId;
    }

    // Préparation CYBank
    $transaction = substr(preg_replace('/[^0-9a-zA-Z]/', '', $nouvelId . substr(md5(time()), 0, 4)), 0, 24);
    $montant     = number_format($totalFinal, 2, '.', '');
    $vendeur     = CYBANK_VENDEUR;
    $retour      = RETOUR_URL . '?session=' . session_id();
    $control     = md5(getAPIKey($vendeur) . '#' . $transaction . '#' . $montant . '#' . $vendeur . '#' . $retour . '#');

    $_SESSION['cybank_transaction'] = $transaction;
    $_SESSION['cybank_montant']     = $montant;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Redirection vers CYBank...</title>
</head>
<body>
    <p style="text-align:center;font-family:sans-serif;margin-top:100px;">
        Redirection vers le paiement sécurisé...
    </p>
    <form id="cybank-form" action="<?= CYBANK_URL ?>" method="POST">
        <input type="hidden" name="transaction" value="<?= htmlspecialchars($transaction) ?>">
        <input type="hidden" name="montant"     value="<?= htmlspecialchars($montant) ?>">
        <input type="hidden" name="vendeur"     value="<?= htmlspecialchars($vendeur) ?>">
        <input type="hidden" name="retour"      value="<?= htmlspecialchars($retour) ?>">
        <input type="hidden" name="control"     value="<?= htmlspecialchars($control) ?>">
    </form>
    <script>document.getElementById('cybank-form').submit();</script>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valider ma commande - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <?php if (($_COOKIE['theme'] ?? '') === 'sombre'): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>
    <style>
        .commande-hero {
            height: 25vh;
            background-image: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url("photo/restophoto.jpeg");
            background-position: center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 70px;
        }
        .commande-hero h1 {
            font-family: var(--font-title); font-size: 48px;
            color: #fff; text-transform: uppercase; letter-spacing: 4px;
        }
        .commande-section {
            padding: 60px 80px 100px; max-width: 1100px; margin: 0 auto;
            display: flex; gap: 40px; align-items: flex-start;
        }
        .commande-form-block { flex: 2; }
        .commande-recap      { flex: 1; }
        .commande-block {
            background: #fff; padding: 30px;
            border-left: 4px solid var(--color-gold);
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
            margin-bottom: 20px;
        }
        .commande-block h2 {
            font-family: var(--font-title); font-size: 22px;
            color: var(--color-bordeaux); margin-bottom: 20px;
            padding-bottom: 10px; border-bottom: 1px solid var(--color-beige);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 12px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 1px;
            color: var(--color-dark); margin-bottom: 8px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid var(--color-gold);
            font-family: var(--font-main); font-size: 14px; background: #fff;
        }
        .form-group textarea { resize: none; }
        .radio-group { display: flex; gap: 20px; margin-bottom: 20px; }
        .radio-option {
            flex: 1; border: 2px solid var(--color-beige); padding: 15px;
            cursor: pointer; text-align: center; transition: border-color .3s;
        }
        .radio-option input { display: none; }
        .radio-option:has(input:checked) { border-color: var(--color-bordeaux); background: #fdf5f5; }
        .radio-option span  { display: block; font-weight: 600; color: var(--color-bordeaux); margin-bottom: 5px; }
        .radio-option small { color: #999; font-size: 12px; }
        .adresse-info {
            background: var(--color-beige); padding: 15px; font-size: 14px;
            margin-bottom: 15px; border-left: 3px solid var(--color-gold);
        }
        .recap-ligne {
            display: flex; justify-content: space-between;
            padding: 8px 0; border-bottom: 1px solid var(--color-beige); font-size: 14px;
        }
        .recap-total {
            display: flex; justify-content: space-between;
            padding: 15px 0; font-size: 18px; font-weight: 600; color: var(--color-bordeaux);
        }
        .btn-payer {
            display: block; width: 100%; background: var(--color-bordeaux);
            color: #fff; border: none; padding: 18px; font-family: var(--font-main);
            font-size: 14px; text-transform: uppercase; letter-spacing: 2px;
            cursor: pointer; margin-top: 20px; transition: background .3s;
        }
        .btn-payer:hover { background: var(--color-gold); }
        .cybank-info { text-align: center; font-size: 12px; color: #999; margin-top: 10px; }
        @media (max-width: 768px) {
            .commande-section { flex-direction: column; padding: 30px 20px; }
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
            <li><a href="panier.php">MON PANIER</a></li>
        </ul>
        <div class="nav-buttons">
            <button class="btn-gold" onclick="location.href='deconnexion.php'">DÉCONNEXION</button>
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold" aria-label="Changer le thème">
                <?= (($_COOKIE['theme'] ?? '') === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre' ?>
            </button>
        </div>
    </nav>
</header>

<main>
    <div class="commande-hero"><h1>Ma Commande</h1></div>

    <div class="commande-section">
        <div class="commande-form-block">
            <form method="POST" action="commande.php">

                <div class="commande-block">
                    <h2>Type de commande</h2>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="type_commande" value="livraison" checked onchange="toggleAdresse(this.value)">
                            <span>🛵 Livraison</span><small>Livré à votre adresse</small>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="type_commande" value="emporter" onchange="toggleAdresse(this.value)">
                            <span>🥡 À emporter</span><small>Récupérer au restaurant</small>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="type_commande" value="sur_place" onchange="toggleAdresse(this.value)">
                            <span>🍽️ Sur place</span><small>Manger au restaurant</small>
                        </label>
                    </div>
                </div>

                <div class="commande-block" id="bloc-adresse">
                    <h2>Adresse de livraison</h2>
                    <div class="adresse-info">
                        <strong><?= htmlspecialchars($user['adresse']) ?></strong><br>
                        <?= htmlspecialchars(($user['code_postal'] ?? '') . ' ' . ($user['ville'] ?? '')) ?>
                        <?php if (!empty($user['etage'])): ?> — <?= htmlspecialchars($user['etage']) ?><?php endif; ?>
                        <?php if (!empty($user['interphone'])): ?> — Interphone : <?= htmlspecialchars($user['interphone']) ?><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Commentaire pour le livreur</label>
                        <textarea name="commentaire" rows="2" placeholder="Instructions particulières..."></textarea>
                    </div>
                </div>

                <div class="commande-block">
                    <h2>Quand souhaitez-vous être livré ?</h2>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="preparation_immediate" value="1" checked onchange="togglePlanif(true)">
                            <span>⚡ Dès que possible</span><small>Préparation immédiate</small>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="preparation_immediate" value="" onchange="togglePlanif(false)">
                            <span>📅 Planifier</span><small>Choisir une date/heure</small>
                        </label>
                    </div>
                    <div id="bloc-planif" style="display:none;">
                        <div class="form-group">
                            <label>Date et heure souhaitées</label>
                            <input type="datetime-local" name="date_planifiee" min="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-payer">
                    <?= isset($_SESSION['modifier_commande_id']) ? 'Confirmer la modification →' : 'Procéder au paiement →' ?>
                </button>
                <p class="cybank-info">🔒 Paiement sécurisé via CYBank</p>

            </form>
        </div>

        <div class="commande-recap">
            <div class="commande-block">
                <h2>Récapitulatif</h2>
                <?php foreach ($_SESSION['panier'] as $item): ?>
                    <div class="recap-ligne">
                        <span><?= $item['quantite'] ?>x <?= htmlspecialchars($item['nom']) ?></span>
                        <span><?= number_format($item['prix'] * $item['quantite'], 2, ',', '') ?> €</span>
                    </div>
                <?php endforeach; ?>
                <div class="recap-total">
                    <span>Total à payer</span>
                    <span><?= number_format($total, 2, ',', '') ?> €</span>
                </div>
                <a href="panier.php" style="display:block;text-align:center;color:var(--color-gold);text-decoration:underline;font-size:13px;margin-top:10px;">
                    ← Modifier mon panier
                </a>
            </div>
        </div>
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
        <div class="footer-column"><h3>Administration</h3><a href="admin.php">Interface Administrateur</a></div>
        <div class="footer-column"><h3>Restaurateur</h3><a href="restaurateur.php">Gestion Cuisine</a></div>
        <div class="footer-column"><h3>Livreur</h3><a href="livreur.php">Interface Livraison</a></div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 L'Antica Trattoria - Site réalisé par Boualili Kenza et Eish Shahd</p>
    </div>
</footer>

<script>
    function toggleAdresse(val) {
        document.getElementById('bloc-adresse').style.display = val === 'livraison' ? 'block' : 'none';
    }
    function togglePlanif(immediate) {
        document.getElementById('bloc-planif').style.display = immediate ? 'none' : 'block';
    }
</script>
<script src="js/theme.js"></script>
</body>
</html>
