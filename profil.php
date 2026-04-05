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
    
    if (!file_exists($fichier))
    {
        return [];
    }
    
    $commandes = json_decode(file_get_contents($fichier), true) ?? [];
    $result = [];
    
    foreach ($commandes as $cmd)
    {
        if ($cmd['client_id'] == $userId)
        {
            $result[] = $cmd;
        }
    }
    
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
    'sur_place'      => 'Sur place',
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
            <button class="btn-gold" onclick="window.location.href='deconnexion.php'">SE DECONNECTER</button>
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
        <header class="profil-intro">
            <h2>BENVENUTO</h2>
            <p>Gerez vos informations personnelles, suivez vos avantages fidelite et retrouvez le gout de vos precedentes visites chez L'Antica Trattoria.</p>
        </header>

        <div class="profil-container">

            <div class="profil-block">
                <div class="block-header">
                    <h3>Mes Informations</h3>
                    <span class="edit-icon" title="Modifier (disponible phase 3)">✎</span>
                </div>
                <div class="info-grid">
                    <div class="info-item"><strong>Nom :</strong> <?php echo htmlspecialchars($user['nom']); ?></div>
                    <div class="info-item"><strong>Prenom :</strong> <?php echo htmlspecialchars($user['prenom']); ?></div>
                    <div class="info-item"><strong>Email :</strong> <?php echo htmlspecialchars($user['login']); ?></div>
                    <div class="info-item"><strong>Telephone :</strong> <?php echo htmlspecialchars($user['telephone'] ?: 'Non renseigne'); ?></div>
                    <div class="info-item full-width">
                        <strong>Adresse :</strong>
                        <?php
                        $adresse = trim($user['adresse'] . ' ' . $user['code_postal'] . ' ' . $user['ville']);
                        echo htmlspecialchars($adresse ?: 'Non renseignee');
                        ?>
                    </div>
                    <?php if (!empty($user['etage'])): ?>
                        <div class="info-item"><strong>Etage :</strong> <?php echo htmlspecialchars($user['etage']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['interphone'])): ?>
                        <div class="info-item"><strong>Interphone :</strong> <?php echo htmlspecialchars($user['interphone']); ?></div>
                    <?php endif; ?>
                    
                    <div class="info-item"><strong>Statut :</strong> <?php echo ucfirst($user['statut']); ?></div>
                    <div class="info-item"><strong>Membre depuis :</strong> <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></div>
                </div>
            </div>

            <div class="profil-block loyalty-card">
                <div class="block-header">
                    <h3>Mon Compte Fidelite</h3>
                </div>
                <div class="loyalty-content">
                    <div class="points-circle">
                        <span class="points-value"><?php echo $user['points_fidelite']; ?></span>
                        <span class="points-label">Points</span>
                    </div>
                    <div class="loyalty-text">
                        <?php if ($user['remise'] > 0): ?>
                            <p>Vous beneficiez d'une remise de <strong><?php echo $user['remise']; ?>%</strong> sur toutes vos commandes !</p>
                        <?php else: ?>
                            <p>Continuez a commander pour accumuler des points et debloquer des <strong>avantages exclusifs</strong> !</p>
                        <?php endif; ?>
                        <a href="#" class="btn-link">Voir mes avantages</a>
                    </div>
                </div>
            </div>

            <?php
            // Chercher une commande en cours
            $commandeEnCours = null;
            foreach ($commandes as $cmd) 
            {
                if (in_array($cmd['statut'], ['en_attente', 'en_preparation', 'en_livraison', 'en_attente_paiement'])) 
                {
                    $commandeEnCours = $cmd;
                    break;
                }
            }
            ?>

            <?php if ($commandeEnCours): ?>
                <div class="profil-block" style="border-left: 4px solid var(--color-bordeaux);">
                    <div class="block-header">
                        <h3>Commande en cours</h3>
                        <span style="font-size:12px; color:#999;">Mis à jour en temps réel</span>
                    </div>

                    <div style="display:flex; align-items:center; gap:30px; flex-wrap:wrap;">

                        <div style="flex:1; min-width:300px;">
                            <div style="display:flex; align-items:center; gap:0; margin-bottom:20px;">

                                <?php
                                $etapes = [
                                    'en_attente'     => ['label' => 'Reçue',      'icon' => '📋'],
                                    'en_preparation' => ['label' => 'Préparation','icon' => '👨‍🍳'],
                                    'en_livraison'   => ['label' => 'En route',   'icon' => '🛵'],
                                    'livree'         => ['label' => 'Livrée',     'icon' => '✅'],
                                ];
                                $statutActuel = $commandeEnCours['statut'];
                                $statutsOrdre = array_keys($etapes);
                                $indexActuel  = array_search($statutActuel, $statutsOrdre);
                                ?>

                                <?php foreach ($etapes as $key => $etape):
                                    $index   = array_search($key, $statutsOrdre);
                                    $actif   = ($key === $statutActuel);
                                    $passe   = ($index < $indexActuel);
                                    $couleur = $actif ? 'var(--color-bordeaux)' : ($passe ? '#27ae60' : '#ccc');
                                ?>
                                    <div style="text-align:center; flex:1;">
                                        <div style="width:40px; height:40px; border-radius:50%;
                                                    background:<?php echo $couleur; ?>;
                                                    color:#fff; display:flex; align-items:center;
                                                    justify-content:center; margin:0 auto 5px;
                                                    font-size:18px;">
                                            <?php echo $etape['icon']; ?>
                                        </div>
                                        <div style="font-size:11px; color:<?php echo $couleur; ?>;
                                                    font-weight:<?php echo $actif ? '600' : '400'; ?>;">
                                            <?php echo $etape['label']; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($key !== 'livree'): ?>
                                        <div style="flex:1; height:2px; background:<?php echo $passe ? '#27ae60' : '#eee'; ?>;
                                                    margin-bottom:20px;"></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div style="font-size:14px; color:#5a5a5a; min-width:200px;">
                            <p><strong>Commande :</strong> <?php echo htmlspecialchars($commandeEnCours['id']); ?></p>
                            <p><strong>Total :</strong> <?php echo number_format($commandeEnCours['prix_total'], 2); ?> €</p>
                            <p><strong>Type :</strong> <?php echo ucfirst(str_replace('_', ' ', $commandeEnCours['type'])); ?></p>
                            <p><strong>Statut :</strong>
                                <span class="<?php echo $statutClasses[$commandeEnCours['statut']] ?? ''; ?>">
                                    <?php echo $statutLabels[$commandeEnCours['statut']] ?? $commandeEnCours['statut']; ?>
                                </span>
                            </p>
                            <?php if ($commandeEnCours['date_preparation_souhaitee']): ?>
                                <p><strong>Livraison prévue :</strong><br>
                                    <?php echo date('d/m/Y à H:i', strtotime($commandeEnCours['date_preparation_souhaitee'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="profil-block">
                <div class="block-header">
                    <h3>Mes Commandes</h3>
                </div>

                <?php if (empty($commandes)): ?>
                    <p style="color:#5a5a5a; font-style:italic;">Vous n'avez pas encore passe de commande.</p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Date</th>
                                <th>Articles</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $cmd): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cmd['id']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($cmd['date_commande'])); ?></td>
                                    <td>
                                        <?php
                                        $noms = array_map(fn($a) => $a['quantite'] . 'x ' . $a['nom'], $cmd['articles']);
                                        echo htmlspecialchars(implode(', ', $noms));
                                        ?>
                                    </td>
                                    <td><?php echo number_format($cmd['prix_total'], 2); ?> EUR</td>
                                    <td>
                                        <span class="<?php echo $statutClasses[$cmd['statut']] ?? ''; ?>">
                                            <?php echo $statutLabels[$cmd['statut']] ?? $cmd['statut']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cmd['statut'] === 'livree' && $cmd['note_produits'] === null): ?>
                                            <a href="notation.php?commande=<?php echo $cmd['id']; ?>" class="btn-noter">Noter</a>
                                        <?php elseif ($cmd['statut'] === 'livree'): ?>
                                            <span style="color:#27ae60; font-size:13px;">Note</span>
                                        <?php else: ?>
                                            <span style="color:#5a5a5a; font-size:13px;">--</span>
                                        <?php endif; ?>
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
    <div class="footer-top">
        <div class="footer-column">
            <h3>Notre Adresse</h3>
            <p>Avenue du Parc<br>95000 Cergy</p>
        </div>
        <div class="footer-column">
            <h3>Horaires</h3>
            <p>Lundi - Jeudi :<br>12:00 - 22:45</p>
            <p>Vendredi - Dimanche :<br>12:00 - 23:45</p>
        </div>
        <div class="footer-column">
            <h3>Mon Compte</h3>
            <a href="deconnexion.php">Deconnexion</a>
            <a href="notation.php">Noter ma commande</a>
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
        <p>© 2026 L'Antica Trattoria - Site realise par Boualili Kenza et Eish Shahd</p>
    </div>
</footer>

</body>
</html>
