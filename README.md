# 🍝 L'Antica Trattoria

## 📌 Projet Creative-Yumland – PréING2 (2025-2026)

**👩‍💻 Auteurs :** Boualili Kenza & Eish Shahd  
**👩‍🏫 Encadrante :** Mme Arib Souhila  

---

## 🧾 Description du projet

L'Antica Trattoria est un site web de restauration italienne développé dans le cadre du projet **Creative-Yumland**.

L'objectif est de créer une application web multi-utilisateurs permettant de gérer toute la chaîne de commande :

- 🍽️ Consultation de la carte  
- 🔐 Inscription / connexion  
- 🛒 Commande en ligne  
- 💳 Paiement  
- 👨‍🍳 Préparation (restaurateur)  
- 🚚 Livraison (livreur)  

---

## 🚀 Phases réalisées

### ✅ Phase 1 – Frontend (HTML / CSS)

- Création de toutes les pages du site  
- Mise en place d’une charte graphique cohérente  
- Structure complète du site  
- Interfaces adaptées aux différents profils  

✔ Pages réalisées :
- Accueil  
- Carte  
- Inscription / Connexion  
- Profil  
- Administrateur  
- Restaurateur  
- Livreur  
- Notation  

---

### ✅ Phase 2 – Backend (PHP)

- Conversion HTML → PHP  
- Mise en place des données en JSON  
- Authentification complète  
- Gestion des rôles utilisateurs  
- Système de panier et commande  
- Intégration du paiement CYBank  
- Génération dynamique des pages  

---

### ✅ Phase 3 – Frontend Dynamique & Asynchronisme (JS / AJAX) 

- **Cookie Thématique Intégré :** Persistance du choix d'affichage (Clair/Sombre) stocké exclusivement via Cookie 30 jours pour neutraliser le flash blanc au chargement.
- **Moteur de Validation Client :** Interception JavaScript bloquant les requêtes tant que l'email ou le téléphone (exactement 10 chiffres numériques requis) sont invalides.
- **Éléments UX Interactifs :** Compteur en temps réel (gabarit de saisie type X/20 avec alerte rouge) et bouton œil d'affichage/masquage des mots de passe.
- **Architecture de Requêtes Asynchrones :** Recours obligatoire à l'API Fetch JavaScript pour l'édition de profil, le rafraîchissement des filtres de la carte et les verrous admin.
- **Moteur de Modification de Commande :** Algorithme différentiel calculant l'écart budgétaire (Delta) et passerelle CYBank configurée pour n'encaisser que le complément.
- **Dispositif de Sécurité Kick :** Interception amont de session et expulsion forcée instantanée de tout profil banni par l'administrateur.

---
## 🏗️ Architecture du projet
```
/project
├── lib/ # Fonctions PHP (authentification, utils)
├── data/ # Fichiers JSON (users, plats, commandes...)
├── api/
├── js/ # Scripts de dynamisation Front-End
├── index.php
├── carte.php
├── profil.php
├── admin.php
├── restaurateur.php
├── livreur.php
|
├── fichiercommun.css
├── style-admin.css
├──  style carte.css
```

---

## 📂 Données (format JSON)

### 👤 users.json
Contient les utilisateurs :
- id, login, password (hashé)
- rôle (client, admin, restaurateur, livreur)
- informations personnelles
- statut (standard, premium, vip)
- actif (true / false)

---

### 🍕 plats.json
Catalogue des plats :
- nom, description, prix  
- catégorie (antipasti, pasta, pizze, etc.)  
- allergènes  
- végétarien / pimenté  

---

### 📦 commandes.json
Historique des commandes :
- client_id  
- articles commandés  
- statut (en préparation, livré, etc.)  
- prix total  

---

### 💳 paiements.json
Transactions :
- montant  
- statut (validé / refusé)  
- lien avec commande  

---

## 🔐 Fonctionnalités principales

### 🔑 Authentification
- Inscription sécurisée (password_hash)  
- Connexion avec vérification  
- Gestion des sessions PHP  
- Protection des pages avec rôles  

---

### 🍽️ Carte dynamique
- Chargement des plats depuis JSON  
- Recherche intelligente avec synonymes :
  - pizza → pizze  
  - pâtes → pasta  
- Filtres :
  - 🌿 végétarien  
  - 🌶️ pimenté  
  - 🚫 sans gluten / lactose  

---

### 🛒 Panier & Commande
- Ajout / suppression de plats  
- Modification des quantités  
- Commande immédiate ou planifiée  
- Calcul automatique du total  

---

### 💳 Paiement (CYBank)
- Intégration API CYBank  
- Vérification de transaction  
- Mise à jour du statut de commande  

---

### 👥 Interfaces spécifiques

#### 👨‍💼 Administrateur
- Liste des utilisateurs  
- Statistiques  
- Actions (bloquer, VIP – phase 3)  

#### 👨‍🍳 Restaurateur
- Visualisation des commandes  
- Gestion des statuts  

#### 🚚 Livreur
- Interface mobile simplifiée  
- Accès aux informations de livraison  

#### 👤 Client
- Historique des commandes  
- Suivi en temps réel  
- Notation  

---

## 🎨 Charte graphique

- 🟥 Bordeaux : `#5B1F1F`  
- 🟨 Doré : `#B08D57`  
- ⬜ Beige : `#F4EFEA`  
- 🟦 Texte : `#2C3E50`  

**Polices :**
- Playfair Display (titres)  
- Poppins (texte)  

---

## ⚠️ Problèmes rencontrés

### Techniques
- Conflits de fonctions PHP  
- Comparaison d’IDs (`==` vs `===`)  
- JSON vide  
- Recherche trop stricte  

### Organisationnels
- Coordination du travail  
- Gestion des fichiers partagés  

### Solutions
- Utilisation de Git  
- Tests réguliers  
- Structuration du code  

---

## 📊 Conformité au cahier des charges

✔ Authentification fonctionnelle  
✔ Multi-utilisateurs (client, admin, restaurateur, livreur)  
✔ 47 plats + 3 menus  
✔ Panier + commande + paiement  
✔ Interfaces adaptées  
✔ Suivi des commandes  

---

## 🔮 Phase 3 

- Actions admin fonctionnelles (bloquer, VIP, remise)  
- Modification du profil utilisateur  
- Attribution des livreurs  
- Ajout d’articles après commande  
- Javascript / AJAX  

---

## 🛠️ Technologies utilisées

- HTML / CSS  
- PHP  
- JSON  
- API CYBank  

---

## ⚙️ Installation

1. Cloner le projet :
   git clone <repo>

2. Lancer le serveur PHP :
   php -S localhost:8000

3. Accéder au site :
   http://localhost:8000


---

## 📌 Remarque

Projet réalisé dans un cadre pédagogique (CY Tech).  
Certaines fonctionnalités seront finalisées en Phase 4.

---
