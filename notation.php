<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'lib/auth.php';
demarrerSession();

requireConnexion();
requireRole('client');

function lireCommandes() {
    $fichier = __DIR__ . '/data/commandes.json';
    if (!file_exists($fichier)) return [];
    return json_decode(file_get_contents($fichier), true) ?? [];
}

function ecrireCommandes($commandes) {
    $fichier = __DIR__ . '/data/commandes.json';
    file_put_contents($fichier, json_encode($commandes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$commandeId = $_GET['commande'] ?? '';
$commandes  = lireCommandes();
$userId     = $_SESSION['user_id'];

// Trouver la commande
$maCommande = null;
foreach ($commandes as $cmd) {
    if ($cmd['id'] === $commandeId && $cmd['client_id'] == $userId) {
        $maCommande = $cmd;
        break;
    }
}

$erreur = '';
$succes = '';

// Vérifications
if (!$maCommande) {
    $erreur = 'Commande introuvable ou accès non autorisé.';
} elseif ($maCommande['statut'] !== 'livree') {
    $erreur = 'Vous ne pouvez noter que les commandes livrées.';
} elseif ($maCommande['note_produits'] !== null) {
    $succes = 'Vous avez déjà noté cette commande. Merci !';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erreur) && empty($succes)) {
    $noteProduits  = intval($_POST['produit'] ?? 0);
    $noteLivraison = intval($_POST['livraison'] ?? 0);
    $commentaire   = trim($_POST['commentaire'] ?? '');

    if ($noteProduits < 1 || $noteProduits > 5 || $noteLivraison < 1 || $noteLivraison > 5) {
        $erreur = 'Veuillez attribuer une note entre 1 et 5 étoiles pour chaque critère.';
    } else {
        // Mettre à jour la commande
        foreach ($commandes as &$cmd) {
            if ($cmd['id'] === $commandeId && $cmd['client_id'] == $userId) {
                $cmd['note_produits']  = $noteProduits;
                $cmd['note_livraison'] = $noteLivraison;
                $cmd['commentaire']    = $commentaire;
                break;
            }
        }
        ecrireCommandes($commandes);
        $succes = 'Merci pour votre avis ! Votre note a bien été enregistrée.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Avis - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <link rel="stylesheet" href="style-notation.css">
    <style>
        .form-erreur {
            background-color: #fde8e8;
            border-left: 4px solid #c0392b;
            color: #c0392b;
            padding: 12px 16px;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .form-succes {
            background-color: #e8f8e8;
            border-left: 4px solid #27ae60;
            color: #27ae60;
            padding: 12px 16px;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .form-succes a {
            display: block;
            margin-top: 8px;
            color: #27ae60;
            font-weight: 600;
            text-decoration: underline;
        }
        .commande-recap {
            background: var(--color-beige);
            padding: 15px;
            margin-bottom: 30px;
            border-left: 3px solid var(--color-gold);
            font-size: 14px;
        }
        .commande-recap strong {
            color: var(--color-bordeaux);
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
            <li><a href="profil.php">MON PROFIL</a></li>
        </ul>
        <div class="nav-buttons">
            <button class="btn-gold" onclick="window.location.href='deconnexion.php'">DÉCONNEXION</button>
        </div>
    </nav>
</header>

<main>
    <div class="notation-hero">
        <div class="notation-hero-overlay">
            <h1>Votre Expérience</h1>
            <div class="separator"></div>
        </div>
    </div>

    <section class="notation-section">
        <header class="notation-intro">
            <h2>VOTRE AVIS COMPTE</h2>
            <p>Parce que nous avons à cœur de vous offrir le meilleur de l'Italie, votre retour nous est précieux.</p>
            <a href="profil.php" class="btn-retour-profil">← Retour au profil</a>
        </header>

        <div style="flex:1;">

            <?php if (!empty($erreur)): ?>
                <div class="form-erreur">
                    <?php echo htmlspecialchars($erreur); ?>
                    <br><a href="profil.php" style="color:#c0392b; font-weight:600;">Retour au profil</a>
                </div>

            <?php elseif (!empty($succes)): ?>
                <div class="form-succes">
                    <?php echo htmlspecialchars($succes); ?>
                    <a href="profil.php">Retour à mon profil →</a>
                </div>

            <?php else: ?>

                <!-- Récap de la commande -->
                <div class="commande-recap">
                    <strong>Commande <?php echo htmlspecialchars($maCommande['id']); ?></strong>
                    du <?php echo date('d/m/Y', strtotime($maCommande['date_commande'])); ?> —
                    <?php
                    $noms = array_map(fn($a) => $a['quantite'] . 'x ' . $a['nom'], $maCommande['articles']);
                    echo htmlspecialchars(implode(', ', $noms));
                    ?>
                    — <strong><?php echo number_format($maCommande['prix_total'], 2); ?>€</strong>
                </div>

                <form action="notation.php?commande=<?php echo htmlspecialchars($commandeId); ?>"
                      method="POST" class="notation-form">

                    <div class="rating-group">
                        <label>Qualité des produits *</label>
                        <div class="stars">
                            <input type="radio" name="produit" id="p5" value="5" required><label for="p5">★</label>
                            <input type="radio" name="produit" id="p4" value="4"><label for="p4">★</label>
                            <input type="radio" name="produit" id="p3" value="3"><label for="p3">★</label>
                            <input type="radio" name="produit" id="p2" value="2"><label for="p2">★</label>
                            <input type="radio" name="produit" id="p1" value="1"><label for="p1">★</label>
                        </div>
                    </div>

                    <div class="rating-group">
                        <label>Qualité de la livraison *</label>
                        <div class="stars">
                            <input type="radio" name="livraison" id="l5" value="5" required><label for="l5">★</label>
                            <input type="radio" name="livraison" id="l4" value="4"><label for="l4">★</label>
                            <input type="radio" name="livraison" id="l3" value="3"><label for="l3">★</label>
                            <input type="radio" name="livraison" id="l2" value="2"><label for="l2">★</label>
                            <input type="radio" name="livraison" id="l1" value="1"><label for="l1">★</label>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="commentaire">Commentaire (optionnel)</label>
                        <textarea id="commentaire" name="commentaire" rows="4"
                                  placeholder="Dites-nous en plus..."></textarea>
                    </div>

                    <div class="form-submit">
                        <button type="submit" class="btn-envoyer">ENVOYER MON AVIS</button>
                    </div>

                </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer>
    <div class="footer-top">
        <div class="footer-column">
            <h3>Adresse</h3>
            <p>Avenue du Parc<br>95000 Cergy</p>
        </div>
        <div class="footer-column">
            <h3>Horaires</h3>
            <p>Lun - Jeu : 12:00 - 22:45</p>
            <p>Ven - Dim : 12:00 - 23:45</p>
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

</body>
</html>