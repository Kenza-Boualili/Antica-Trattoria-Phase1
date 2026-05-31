<?php

// Chemins vers les fichiers de données
define('DATA_USERS', __DIR__ . '/../data/users.json');

// Démarrer la session si elle n'est pas déjà démarrée
function demarrerSession() 
{
    if (session_status() === PHP_SESSION_NONE) 
    {
        session_start();
    }
}

// Lire tous les utilisateurs depuis users.json
function lireUtilisateurs() 
{
    if (!file_exists(DATA_USERS)) 
    {
        return [];
    }

    $contenu = file_get_contents(DATA_USERS);
    return json_decode($contenu, true) ?? [];
}

// Écrire les utilisateurs dans users.json
function ecrireUtilisateurs($users) 
{
    file_put_contents(DATA_USERS, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Inscrire un nouvel utilisateur
function inscrire($donnees) 
{
    $users = lireUtilisateurs();

    // Vérifier si l'email est déjà utilisé
    foreach ($users as $user) 
    {
        if ($user['login'] === $donnees['email']) 
        {
            return [
                'succes' => false, 
                'message' => 'Cet email est déjà utilisé.'
            ];
        }
    }

    // Trouver le prochain ID
    $maxId = 0;
    foreach ($users as $user) 
    {
        if ($user['id'] > $maxId) 
        {
            $maxId = $user['id'];
        }
    }

    // Créer le nouvel utilisateur
    $nouvelUser = [
        'id'                           => $maxId + 1,
        'login'                        => $donnees['email'],
        'password'                     => password_hash($donnees['password'], PASSWORD_DEFAULT),
        'role'                         => 'client',
        'nom'                          => $donnees['nom'],
        'prenom'                       => $donnees['prenom'],
        'pseudo'                       => strtolower($donnees['prenom']) . strtolower($donnees['nom']),
        'date_naissance'               => '',
        'telephone'                    => $donnees['telephone'] ?? '',
        'adresse'                      => $donnees['adresse'] ?? '',
        'ville'                        => $donnees['ville'] ?? '',
        'code_postal'                  => $donnees['codepostal'] ?? '',
        'etage'                        => $donnees['etage'] ?? '',
        'interphone'                   => $donnees['interphone'] ?? '',
        'informations_complementaires' => $donnees['complement'] ?? '',
        'statut'                       => 'standard',
        'remise'                       => 0,
        'points_fidelite'              => 0,
        'actif'                        => true,
        'date_inscription'             => date('Y-m-d\TH:i:s'),
        'derniere_connexion'           => date('Y-m-d\TH:i:s')
    ];

    $users[] = $nouvelUser;
    ecrireUtilisateurs($users);

    return [
        'succes' => true, 
        'message' => 'Inscription réussie !'
    ];
}

// Connecter un utilisateur
function connecter($email, $password) 
{
    demarrerSession();
    $users = lireUtilisateurs();

    foreach ($users as &$user) 
    {
        if ($user['login'] === $email) 
        {
            // Vérifier si le compte est actif
            if (!$user['actif']) 
            {
                return [
                    'succes' => false, 
                    'message' => 'Ce compte est désactivé.'
                ];
            }

            // Vérifier le mot de passe
            if (password_verify($password, $user['password'])) 
            {
                // Mettre à jour la dernière connexion
                $user['derniere_connexion'] = date('Y-m-d\TH:i:s');
                ecrireUtilisateurs($users);

                // Créer la session
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['user_nom']    = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_role']   = $user['role'];
                $_SESSION['user_email']  = $user['login'];

                return [
                    'succes' => true, 
                    'role' => $user['role']
                ];
            } 
            else 
            {
                return [
                    'succes' => false, 
                    'message' => 'Mot de passe incorrect.'
                ];
            }
        }
    }

    return [
        'succes' => false, 
        'message' => 'Aucun compte trouvé avec cet email.'
    ];
}

// Déconnecter l'utilisateur
function deconnecter() 
{
    demarrerSession();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Vérifier si l'utilisateur est connecté
function estConnecte() 
{
    demarrerSession();
    return isset($_SESSION['user_id']);
}

// Récupérer le rôle de l'utilisateur connecté
function getRoleConnecte() 
{
    demarrerSession();
    return $_SESSION['user_role'] ?? null;
}

// Forcer la connexion (redirige vers connexion.php si pas connecté)
function requireConnexion() 
{
    if (!estConnecte()) 
    {
        header('Location: connexion.php');
        exit;
    }
}

// Forcer un rôle précis (redirige vers accueil si mauvais rôle)
function requireRole($roleAttendu) 
{
    requireConnexion();

    if ($_SESSION['user_role'] !== $roleAttendu) 
    {
        header('Location: index.php');
        exit;
    }
}

// Récupérer les infos complètes de l'utilisateur connecté
function getUtilisateurConnecte() 
{
    demarrerSession();

    if (!estConnecte()) 
    {
        return null;
    }

    $users = lireUtilisateurs();
    foreach ($users as $user) 
    {
        if ($user['id'] === $_SESSION['user_id']) 
        {
            return $user;
        }
    }

    return null;
}

