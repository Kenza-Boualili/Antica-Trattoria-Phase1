// Affiche ou masque le mot de passe en changeant le type de l'input 
function togglePassword(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon = document.getElementById(iconId);

    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "🙈"; // Icône quand le texte est visible
    } else {
        input.type = "password";
        icon.textContent = "👁️"; // Icône quand le texte est caché
    }
}

// Met à jour le compteur de caractères en temps réel 
function majCompteur(inputId, counterId) {
    var champ = document.getElementById(inputId);
    var compteur = document.getElementById(counterId);
    
    var nbCaracteres = champ.value.length;
    compteur.textContent = nbCaracteres;

    // Change la couleur si on s'approche de la limite (max 20) 
    if (nbCaracteres >= 18) {
        compteur.style.color = "red";
    } else {
        compteur.style.color = "inherit";
    }
}

function validerConnexion(event) {
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value;
    var errorDiv = document.getElementById("js-erreur");

    // Réinitialisation
    errorDiv.style.display = "none";
    errorDiv.textContent = "";

    var msg = "";

    // Vérifie si les champs sont vides
    if (email === "" || password === "") {
        msg = "Veuillez remplir tous les champs.";
    } 
    // Vérifie le format de l'email
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        msg = "L'adresse e-mail n'est pas valide.";
    }

    if (msg !== "") {
        event.preventDefault(); // Bloque l'envoi sans recharger la page 
        errorDiv.textContent = msg;
        errorDiv.style.display = "block";
        return false;
    }
    return true;
}


function validerInscription(event) {
    var errorDiv = document.getElementById("js-erreur");
    errorDiv.style.display = "none";
    errorDiv.textContent = "";

    // Récupération des valeurs
    var nom = document.getElementById("nom").value.trim();
    var prenom = document.getElementById("prenom").value.trim();
    var email = document.getElementById("email").value.trim();
    var telephone = document.getElementById("telephone").value.trim();
    var pwd = document.getElementById("password").value;
    var confirm = document.getElementById("confirm-password").value;
    var cgu = document.getElementById("cgu").checked;

    var msg = "";

    // On isole les chiffres du téléphone pour le tester (gère les espaces/points)
    var chiffresTelephone = telephone.replace(/[^0-9]/g, '');

    // Tests de validation
    if (!nom || !prenom || !email || !telephone || !pwd || !confirm) {
        msg = "Veuillez remplir tous les champs obligatoires (*).";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        msg = "Format d'email incorrect.";
    } else if (chiffresTelephone.length !== 10) { // Validation téléphone ajoutée 
        msg = "Le numéro de téléphone doit contenir exactement 10 chiffres.";
    } else if (pwd.length < 6) {
        msg = "Le mot de passe doit faire au moins 6 caractères.";
    } else if (pwd !== confirm) {
        msg = "Les deux mots de passe ne sont pas identiques.";
    } else if (!cgu) {
        msg = "Vous devez accepter les CGU pour continuer.";
    }

    if (msg !== "") {
        event.preventDefault(); // Empêche l'envoi au serveur PHP
        errorDiv.textContent = msg;
        errorDiv.style.display = "block";
        window.scrollTo(0, 0); // Remonte la page pour voir l'erreur
        return false;
    }
    return true;
}