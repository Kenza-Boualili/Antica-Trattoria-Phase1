<?php


require_once '../lib/auth.php';
demarrerSession();

// On précise au navigateur que la réponse sera au format JSON
header('Content-Type: application/json');

// 1. Vérification de sécurité : l'utilisateur doit être connecté
if (!estConnecte()) {
    echo json_encode(['succes' => false, 'message' => 'Session expirée ou non connecté.']);
    exit;
}

// 2. Vérification de la méthode de requête (doit être POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération de l'ID de l'utilisateur stocké en session
    $userId = $_SESSION['user_id'];
    
    // Récupération et nettoyage des données envoyées par le formulaire
    $nouveauNom      = trim($_POST['nom'] ?? '');
    $nouveauPrenom   = trim($_POST['prenom'] ?? '');
    $nouveauTel      = trim($_POST['telephone'] ?? '');
    $nouvelleAdresse = trim($_POST['adresse'] ?? '');

    // 3. Validation minimale côté serveur
    if (empty($nouveauNom) || empty($nouveauPrenom)) {
        echo json_encode(['succes' => false, 'message' => 'Le nom et le prénom sont obligatoires.']);
        exit;
    }

    // 4. Appel de la fonction réelle dans lib/auth.php
    $res = modifierInfosUtilisateur($userId, $nouveauNom, $nouveauPrenom, $nouveauTel, $nouvelleAdresse);
    
    if ($res) {
        // Succès : les données sont écrites dans users.json
        echo json_encode(['succes' => true]);
    } else {
        // Erreur : problème lors de l'écriture ou utilisateur introuvable
        echo json_encode(['succes' => false, 'message' => 'Erreur technique lors de la sauvegarde.']);
    }

} else {
    // Si quelqu'un essaie d'accéder au fichier directement sans POST
    echo json_encode(['succes' => false, 'message' => 'Requête invalide.']);
}