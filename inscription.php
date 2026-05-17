<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/auth.php';
demarrerSession();

// Si déjà connecté, rediriger
if (estConnecte())
{
    header('Location: profil.php');
    exit;
}

$erreur  = '';
$succes  = '';
$donnees = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $donnees = [
        'nom'        => trim($_POST['nom'] ?? ''),
        'prenom'     => trim($_POST['prenom'] ?? ''),
        'email'      => trim($_POST['email'] ?? ''),
        'telephone'  => trim($_POST['telephone'] ?? ''),
        'password'   => $_POST['password'] ?? '',
        'confirm'    => $_POST['confirm-password'] ?? '',
        'adresse'    => trim($_POST['adresse'] ?? ''),
        'ville'      => trim($_POST['ville'] ?? ''),
        'codepostal' => trim($_POST['codepostal'] ?? ''),
        'etage'      => trim($_POST['etage'] ?? ''),
        'interphone' => trim($_POST['interphone'] ?? ''),
        'complement' => trim($_POST['complement'] ?? ''),
    ];

    // Validations PHP
    if (empty($donnees['nom']) || empty($donnees['prenom']) || empty($donnees['email']) || empty($donnees['password']))
    {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    }
    elseif (!filter_var($donnees['email'], FILTER_VALIDATE_EMAIL))
    {
        $erreur = 'Adresse e-mail invalide.';
    }
    elseif (strlen($donnees['password']) < 6)
    {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
    }
    elseif ($donnees['password'] !== $donnees['confirm'])
    {
        $erreur = 'Les mots de passe ne correspondent pas.';
    }
    elseif (!isset($_POST['cgu']))
    {
        $erreur = 'Vous devez accepter les conditions générales d\'utilisation.';
    }
    else
    {
        $resultat = inscrire($donnees);
        
        if ($resultat['succes'])
        {
            $succes = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            $donnees = []; // Vider le formulaire
        }
        else
        {
            $erreur = $resultat['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - L'Antica Trattoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fichiercommun.css">
    <link id="css-theme" rel="stylesheet" href="style-inscription.css">
    
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
            <button class="btn-gold" onclick="window.location.href='connexion.php'">SE CONNECTER</button>
            <button class="btn-gold" onclick="window.location.href='inscription.php'">S'INSCRIRE</button>
            
            <button id="btn-theme" onclick="basculerTheme()" class="btn-gold">
                <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'sombre') ? '☀️ Mode clair' : '🌙 Mode sombre'; ?>
            </button>
        </div>
    </nav>
</header>

<main>
    <div class="inscription-hero">
        <div class="inscription-hero-overlay">
            <h1>Créer un compte</h1>
            <div class="separator"></div>
        </div>
    </div>

    <section class="inscription-section">
        <header class="inscription-intro">
            <h2>REJOIGNEZ-NOUS</h2>
            <p>Créez votre compte pour profiter de nos avantages exclusifs.</p>
        </header>

        <form id="registerForm" action="inscription.php" method="POST" class="inscription-form" onsubmit="return validerInscription(event)">

            <div id="js-erreur" class="form-erreur" style="display:none; margin-bottom: 20px; padding: 15px; background: #f8d7da; color: #721c24; border-radius: 4px; border: 1px solid #f5c6cb;"></div>

            <?php if (!empty($erreur)): ?>
                <div class="form-erreur"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if (!empty($succes)): ?>
                <div class="form-succes">
                    <?php echo htmlspecialchars($succes); ?>
                    <a href="connexion.php">Se connecter maintenant →</a>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" placeholder="Votre nom"
                           value="<?php echo htmlspecialchars($donnees['nom'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" placeholder="Votre prénom"
                           value="<?php echo htmlspecialchars($donnees['prenom'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Adresse e-mail *</label>
                    <input type="email" id="email" name="email" placeholder="votre@email.com"
                           value="<?php echo htmlspecialchars($donnees['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone *</label>
                    <input type="tel" id="telephone" name="telephone" placeholder="06 00 00 00 00"
                           value="<?php echo htmlspecialchars($donnees['telephone'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="position: relative;">
                    <label for="password">Mot de passe * (<span id="pwd-count">0</span>/20)</label>
                    <input type="password" id="password" name="password" maxlength="20" placeholder="••••••••" required oninput="majCompteur('password', 'pwd-count')">
                    <span id="toggle-pwd" class="toggle-icon" onclick="togglePassword('password', 'toggle-pwd')" style="position: absolute; right: 10px; top: 35px; cursor: pointer;">👁️</span>
                </div>
                <div class="form-group" style="position: relative;">
                    <label for="confirm-password">Confirmer le mot de passe *</label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="••••••••" required>
                    <span id="toggle-confirm" class="toggle-icon" onclick="togglePassword('confirm-password', 'toggle-confirm')" style="position: absolute; right: 10px; top: 35px; cursor: pointer;">👁️</span>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="adresse">Adresse de livraison</label>
                <input type="text" id="adresse" name="adresse" placeholder="Numéro et nom de rue"
                       value="<?php echo htmlspecialchars($donnees['adresse'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ville">Ville</label>
                    <input type="text" id="ville" name="ville" placeholder="Ville"
                           value="<?php echo htmlspecialchars($donnees['ville'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="codepostal">Code postal</label>
                    <input type="text" id="codepostal" name="codepostal" placeholder="00000"
                           value="<?php echo htmlspecialchars($donnees['codepostal'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="etage">Étage</label>
                    <input type="text" id="etage" name="etage" placeholder="Ex : 3ème étage"
                           value="<?php echo htmlspecialchars($donnees['etage'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="interphone">Code interphone</label>
                    <input type="text" id="interphone" name="interphone" placeholder="Ex : B123"
                           value="<?php echo htmlspecialchars($donnees['interphone'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group full-width">
                <label for="complement">Informations complémentaires</label>
                <textarea id="complement" name="complement" rows="3"
                          placeholder="Allergies, préférences alimentaires..."><?php echo htmlspecialchars($donnees['complement'] ?? ''); ?></textarea>
            </div>

            <div class="form-cgu">
                <input type="checkbox" id="cgu" name="cgu" required>
                <label for="cgu">J'accepte les <a href="#">conditions générales d'utilisation</a> *</label>
            </div>

            <div class="form-submit">
                <button type="submit" class="btn-inscrire">CRÉER MON COMPTE</button>
            </div>
            <p class="form-login">Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
        </form>
    </section>
</main>

<footer>
    <div class="footer-top">
        <div class="footer-column"><h3>Notre Adresse</h3><p>Avenue du Parc<br>95000 Cergy</p></div>
        <div class="footer-column"><h3>Horaires</h3><p>Lun-Dim : 12:00 - 23:45</p></div>
        <div class="footer-column"><h3>Mon Compte</h3><a href="connexion.php">Se connecter</a></div>
    </div>
    <div class="footer-bottom"><p>© 2026 L'Antica Trattoria - Kenza et Shahd</p></div>
</footer>

<script src="js/theme.js"></script>
<script src="js/validation.js"></script>
</body>
</html>
