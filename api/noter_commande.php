<?php
require_once '../lib/auth.php';
demarrerSession();

header('Content-Type: application/json');

if (!estConnecte()) {
    echo json_encode(['succes' => false, 'message' => 'Non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCmd = $_POST['id_commande'] ?? '';
    $note = (int)($_POST['note'] ?? 0);
    $userId = $_SESSION['user_id'];

    $fichier = __DIR__ . '/../data/commandes.json';
    $commandes = json_decode(file_get_contents($fichier), true);

    $trouve = false;
    foreach ($commandes as &$cmd) {
        // Sécurité : on vérifie que la commande appartient bien à l'utilisateur
        if ($cmd['id'] == $idCmd && $cmd['client_id'] == $userId) {
            $cmd['note_produits'] = $note;
            $trouve = true;
            break;
        }
    }

    if ($trouve) {
        file_put_contents($fichier, json_encode($commandes, JSON_PRETTY_PRINT));
        echo json_encode(['succes' => true, 'note' => $note]);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Commande introuvable ou accès refusé']);
    }
}