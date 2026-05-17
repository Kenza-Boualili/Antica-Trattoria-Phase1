<?php
require_once '../lib/auth.php';
demarrerSession();

header('Content-Type: application/json');

if (getRoleConnecte() !== 'restaurateur' && getRoleConnecte() !== 'livreur') {
    echo json_encode(['succes' => false, 'message' => 'Accès interdit']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $statut = $_POST['nouveau_statut'] ?? '';
    $livreurId = $_POST['livreur_id'] ?? null;

    $fichier = __DIR__ . '/../data/commandes.json';
    $commandes = json_decode(file_get_contents($fichier), true);

    $trouve = false;
   foreach ($commandes as &$cmd) {
    if ($cmd['id'] == $id) {
        $cmd['statut'] = $statut;
        
        // Si un livreur est envoyé, on l'enregistre
        if (isset($_POST['livreur_id'])) {
            $cmd['livreur_id'] = (int)$_POST['livreur_id'];
        }
        
        $trouve = true;
        break;
    }
}

    if ($trouve) {
        file_put_contents($fichier, json_encode($commandes, JSON_PRETTY_PRINT));
        echo json_encode(['succes' => true]);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Commande introuvable']);
    }
}