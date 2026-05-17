<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

requireConnexion();

$user = getUtilisateurConnecte();

function getCommandesUtilisateur($userId)
{
    $fichier = __DIR__ . '/data/commandes.json';
    if (!file_exists($fichier)) return [];
    
    $commandes = json_decode(file_get_contents($fichier), true) ?? [];
    $result = [];
    
    foreach ($commandes as $cmd) {
        if ($cmd['client_id'] == $userId) {
            $result[] = $cmd;
        }
    }
    // Tri : la plus récente en premier
    usort($result, fn($a, $b) => strcmp($b['date_commande'], $a['date_commande']));
    return $result;
}

$commandes = getCommandesUtilisateur($user['id']);

$statutLabels = [
    'en_attente'     => 'En attente',
    'en_preparation' => 'En préparation',
    'en_livraison'   => 'En livraison',
    'livree'         => 'Livrée',
    'abandonnee'     => 'Abandonnée',
];

$statutClasses = [
    'en_attente'     => 'status-waiting',
    'en_preparation' => 'status-preparing',
    'en_livraison'   => 'status-shipping',
    'livree'         => 'status-delivered',
    'abandonnee'     => 'status-cancelled',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <link rel="stylesheet" href="style-profil.css">
    
    <?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre'): ?>
        <link rel="stylesheet" href="dark-mode.css" id="css-darkmode">
    <?php endif; ?>
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
            <button class="btn-gold" onclick="window.location.href='deconnexion.php'">DÉCONNEXION</button>
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold">
                <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre'; ?>
            </button>
        </div>
    </nav>
</header>

<main>
    <div class="profil-hero">
        <div class="profil-hero-overlay">
            <h1>Mon Espace</h1>
            <div class="separator"></div>
        </div>
    </div>

    <section class="profil-section">
        <div class="profil-container">

            <div class="profil-block" id="block-infos">
                <div class="block-header">
                    <h3>Mes Informations</h3>
                    <span class="edit-icon" id="btn-edit-profil" title="Modifier" onclick="toggleEditMode(true)" style="cursor:pointer;">✎</span>
                </div>

                <div id="profil-msg" class="form-msg" style="display:none; margin-bottom:15px; padding:10px; border-radius:4px;"></div>

                <div id="profil-display" class="info-grid">
                    <div class="info-item"><strong>Nom :</strong> <span id="txt-nom"><?php echo htmlspecialchars($user['nom']); ?></span></div>
                    <div class="info-item"><strong>Prénom :</strong> <span id="txt-prenom"><?php echo htmlspecialchars($user['prenom']); ?></span></div>
                    <div class="info-item"><strong>Email :</strong> <?php echo htmlspecialchars($user['login']); ?></div>
                    <div class="info-item"><strong>Tél :</strong> <span id="txt-tel"><?php echo htmlspecialchars($user['telephone'] ?: '—'); ?></span></div>
                    <div class="info-item full-width">
                        <strong>Adresse :</strong> 
                        <span id="txt-adresse"><?php echo htmlspecialchars(($user['adresse'] ?? '') . ' ' . ($user['ville'] ?? '')); ?></span>
                    </div>
                </div>

                <form id="form-edit-profil" style="display:none;" onsubmit="sauvegarderProfil(event)">
                    <div class="info-grid">
                        <div class="info-item"><label>Nom</label><input type="text" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required></div>
                        <div class="info-item"><label>Prénom</label><input type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required></div>
                        <div class="info-item"><label>Téléphone</label><input type="tel" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>"></div>
                        <div class="info-item full-width"><label>Adresse</label><input type="text" name="adresse" value="<?php echo htmlspecialchars($user['adresse']); ?>"></div>
                    </div>
                    <div style="margin-top:15px;">
                        <button type="submit" class="btn-gold">ENREGISTRER</button>
                        <button type="button" class="btn-link" onclick="toggleEditMode(false)">Annuler</button>
                    </div>
                </form>
            </div>

            <div class="profil-block loyalty-card">
                <div class="block-header"><h3>Fidélité</h3></div>
                <div class="loyalty-content">
                    <div class="points-circle"><span class="points-value"><?php echo $user['points_fidelite']; ?></span><span class="points-label">Points</span></div>
                    <p>Statut : <strong><?php echo strtoupper($user['statut']); ?></strong></p>
                </div>
            </div>

            <div class="profil-block" style="grid-column: 1 / -1;">
                <div class="block-header"><h3>Mes Commandes</h3></div>
                <?php if (empty($commandes)): ?>
                    <p>Aucune commande passée.</p>
                <?php else: ?>
                    <table class="orders-table" style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom: 1px solid #eee;">
                                <th style="padding:10px;">N°</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Action / Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $cmd): ?>
                                <tr style="border-bottom: 1px solid #f9f9f9;">
                                    <td style="padding:10px;">#<?php echo $cmd['id']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($cmd['date_commande'])); ?></td>
                                    <td><?php echo number_format($cmd['prix_total'], 2); ?>€</td>
                                    <td><span class="<?php echo $statutClasses[$cmd['statut']] ?? ''; ?>"><?php echo $statutLabels[$cmd['statut']] ?? $cmd['statut']; ?></span></td>
                                    
                                    <td>
                                        <div id="notation-container-<?php echo $cmd['id']; ?>">
                                            <?php if ($cmd['statut'] === 'livree'): ?>
                                                <?php if (empty($cmd['note_produits'])): ?>
                                                    <select id="note-val-<?php echo $cmd['id']; ?>" style="padding:2px;">
                                                        <option value="5">5 ⭐</option>
                                                        <option value="4">4 ⭐</option>
                                                        <option value="3">3 ⭐</option>
                                                        <option value="2">2 ⭐</option>
                                                        <option value="1">1 ⭐</option>
                                                    </select>
                                                    <button class="btn-admin-action" onclick="envoyerNotation('<?php echo $cmd['id']; ?>')">NOTER</button>
                                                <?php else: ?>
                                                    <span style="color:#27ae60; font-weight:600;">Note : <?php echo $cmd['note_produits']; ?>/5</span>
                                                <?php endif; ?>
                                            
                                            <?php elseif ($cmd['statut'] === 'en_attente'): ?>
                                                <a href="panier.php?modifier_commande=<?php echo urlencode($cmd['id']); ?>" 
                                                   class="btn-admin-action" 
                                                   style="text-decoration:none; background:var(--color-gold); color:#fff; padding:5px 10px; display:inline-block; font-size:11px; text-transform:uppercase;">
                                                    ⚙️ Modifier
                                                </a>

                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>
    </section>
</main>

<footer>
    <div class="footer-bottom"><p>© 2026 L'Antica Trattoria - Kenza et Shahd</p></div>
</footer>

<script src="js/theme.js"></script>
<script src="js/profil.js"></script>
</body>
</html>