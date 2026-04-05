<?php
//Affiche les erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'lib/auth.php';
demarrerSession();

// Seuls les admins peuvent accéder à cette page
requireRole('admin');


// Lire toutes les commandes
function lireCommandes() {
    $fichier = __DIR__ . '/data/commandes.json';
    if (!file_exists($fichier)) return [];
    return json_decode(file_get_contents($fichier), true) ?? [];
}

$users     = lireUtilisateurs();
$commandes = lireCommandes();

// On filtre tous ou seulement ceux avec commandes
$filtre = $_GET['filtre'] ?? 'tous';
if ($filtre === 'avec_commandes') {
    $clientsAvecCommandes = array_unique(array_column($commandes, 'client_id'));
    $users = array_filter($users, fn($u) => in_array($u['id'], $clientsAvecCommandes));
}

// On compte les commandes par client
$nbCommandes = [];
foreach ($commandes as $cmd) {
    $id = $cmd['client_id'];
    $nbCommandes[$id] = ($nbCommandes[$id] ?? 0) + 1;
}

$roleLabels = [
    'client'       => 'Client',
    'admin'        => 'Admin',
    'restaurateur' => 'Restaurateur',
    'livreur'      => 'Livreur',
];

$statutLabels = [
    'standard' => 'Standard',
    'premium'  => 'Premium',
    'vip'      => 'VIP',
    'admin'    => 'Admin',
    'staff'    => 'Staff',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <link rel="stylesheet" href="style-admin.css">
    <style>

         /* Badge de rôle */
        .badge-role {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Couleurs selon rôle */
        .badge-client       { background: #e8f4fd; color: #2980b9; }
        .badge-admin        { background: #fde8e8; color: #c0392b; }
        .badge-restaurateur { background: #fef9e7; color: #d35400; }
        .badge-livreur      { background: #e8f8f5; color: #27ae60; }

        /* Badge statut */
        .badge-statut {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-standard { background: #f4f4f4; color: #666; }
        .badge-premium  { background: #fef9e7; color: #d35400; }
        .badge-vip      { background: #f5eef8; color: #8e44ad; }

        /* Style visuel pour comptes inactifs */
        .compte-inactif { opacity: 0.5; }
         /* Boutons admin */
        .btn-admin-action {
            border: 1px solid var(--color-gold);
            color: var(--color-gold);
            background: transparent;
            padding: 4px 10px;
            font-size: 11px;
            cursor: pointer;
            font-family: var(--font-main);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            margin-right: 5px;
        }
        .btn-admin-action:hover {
            background: var(--color-gold);
            color: #fff;
        }
        /* Bouton bloquer */
        .btn-bloquer {
            border-color: #c0392b;
            color: #c0392b;
        }
        .btn-bloquer:hover {
            background: #c0392b;
            color: #fff;
        }
        /* Bouton activer */
        .btn-activer {
            border-color: #27ae60;
            color: #27ae60;
        }
        .btn-activer:hover {
            background: #27ae60;
            color: #fff;
        }
        /* Bloc statistiques */
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: #fff;
            padding: 25px;
            border-left: 4px solid var(--color-gold);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-number {
            font-family: var(--font-title);
            font-size: 40px;
            color: var(--color-bordeaux);
            display: block;
        }
        .stat-label {
            font-size: 13px;
            color: #5a5a5a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .note-phase3 {
            font-size: 11px;
            color: #999;
            font-style: italic;
            display: block;
            margin-top: 3px;
        }
        .filtre-actif {
            background: var(--color-gold) !important;
            color: #fff !important;
        }
    </style>
</head>
<body>

<header>
    <nav id="navbar" class="nav-scrolled">
        <div class="logo">L'Antica Trattoria</div>
        <!-- Navigation -->
        <ul class="nav-links">
            <li><a href="index.php">ACCUEIL</a></li>
            <li><a href="carte.php">NOTRE CARTE</a></li>
        </ul>
        <div class="nav-buttons">
             <!-- Infos admin connecté -->
            <span style="color:#fff; font-size:13px; margin-right:10px;">
                Admin : <?php echo htmlspecialchars($_SESSION['user_prenom']); ?>
            </span>
            <!-- Bouton déconnexion -->
            <button class="btn-gold" onclick="window.location.href='deconnexion.php'">DÉCONNEXION</button>
        </div>
    </nav>
</header>

<main>
    <div class="admin-hero">
        <div class="admin-hero-overlay">
            <h1>Espace Gestion</h1>
            <div class="separator"></div>
        </div>
    </div>

    <section class="admin-section">
        <header class="admin-intro">
            <h2>ADMINISTRATION</h2>
            <p>Interface de gestion des utilisateurs et suivi de l'activité.</p>
        </header>

        <div class="admin-container">

            <!-- Statistiques -->
            <div class="admin-stats">

            <!-- Nombre de clients -->
                <div class="stat-card">
                    <span class="stat-number"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'client')); ?></span>
                    <span class="stat-label">Clients</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($commandes); ?></span>
                    <span class="stat-label">Commandes</span>
                </div>
                 <!-- Comptes désactivés -->
                <div class="stat-card">
                    <span class="stat-number"><?php echo count(array_filter($users, fn($u) => !$u['actif'])); ?></span>
                    <span class="stat-label">Comptes désactivés</span>
                </div>
                <!-- Chiffre d'affaires -->
                <div class="stat-card">
                    <span class="stat-number"><?php
                        $total = array_sum(array_column($commandes, 'prix_total'));
                        echo number_format($total, 0, ',', ' ');
                    ?>€</span>
                    <span class="stat-label">CA Total</span>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="admin-block">
                <div class="block-header">
                    <h3>Liste des Utilisateurs</h3>
                    <div class="admin-filters">
                        <a href="admin.php?filtre=tous">
                            <button class="btn-filter <?php echo $filtre === 'tous' ? 'filtre-actif' : ''; ?>">Tous</button>
                        </a>
                        <a href="admin.php?filtre=avec_commandes">
                            <button class="btn-filter <?php echo $filtre === 'avec_commandes' ? 'filtre-actif' : ''; ?>">Avec commandes</button>
                        </a>
                    </div>
                </div>
            <!-- TABLE UTILISATEURS -->
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom & Prénom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Commandes</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr class="<?php echo !$u['actif'] ? 'compte-inactif' : ''; ?>">
                            <td>#<?php echo $u['id']; ?></td>
                            <!-- Protection XSS -->
                            <td><?php echo htmlspecialchars($u['nom'] . ' ' . $u['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($u['login']); ?></td>
                            <!-- Rôle -->
                            <td>
                                <span class="badge-role badge-<?php echo $u['role']; ?>">
                                    <?php echo $roleLabels[$u['role']] ?? $u['role']; ?>
                                </span>
                            </td>
                            <!-- Statut -->
                            <td>
                                <span class="badge-statut badge-<?php echo $u['statut']; ?>">
                                    <?php echo $statutLabels[$u['statut']] ?? $u['statut']; ?>
                                </span>
                            </td>
                            <!-- Nombre de commandes -->
                            <td><?php echo $nbCommandes[$u['id']] ?? 0; ?></td>
                            <!-- Dernière connexion -->
                            <td><?php echo $u['derniere_connexion'] ? date('d/m/Y', strtotime($u['derniere_connexion'])) : '—'; ?></td>
                            <td>
                                <!--Actions affichage qui ne fonctionne pas encore (phase 3) -->
                                <?php if ($u['actif']): ?>
                                    <button class="btn-admin-action btn-bloquer" title="Disponible phase 3">Bloquer</button>
                                <?php else: ?>
                                    <button class="btn-admin-action btn-activer" title="Disponible phase 3">Activer</button>
                                <?php endif; ?>
                                <button class="btn-admin-action" title="Disponible phase 3">VIP</button>
                                <button class="btn-admin-action" title="Disponible phase 3">Remise</button>
                                <span class="note-phase3">Actions actives en phase 3</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
            <h3>Compte</h3>
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
            <a href="restaurateur.php">Gestion Commandes</a>
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