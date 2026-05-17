<?php
require_once '../lib/auth.php';
demarrerSession();

header('Content-Type: application/json');

// Sécurité : Seul l'admin peut faire ça
if (getRoleConnecte() !== 'admin') {
    echo json_encode(['succes' => false, 'message' => 'Accès refusé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $users = lireUtilisateurs();
    $nouvelEtat = false;
    $trouve = false;

    foreach ($users as &$u) {
        if ($u['id'] == $id) {
            // On inverse le statut actuel
            $u['actif'] = !$u['actif'];
            $nouvelEtat = $u['actif'];
            $trouve = true;
            break;
        }
    }

    if ($trouve) {
        ecrireUtilisateurs($users);
        echo json_encode(['succes' => true, 'nouvel_etat' => $nouvelEtat]);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Utilisateur introuvable']);
    }
}