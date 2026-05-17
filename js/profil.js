
function toggleEditMode(show) {
    const displayDiv = document.getElementById('profil-display');
    const formElement = document.getElementById('form-edit-profil');
    const msgDiv = document.getElementById('profil-msg');

    if (show) {
        displayDiv.style.display = 'none';
        formElement.style.display = 'block';
    } else {
        displayDiv.style.display = 'grid';
        formElement.style.display = 'none';
        msgDiv.style.display = 'none'; // Cache les messages d'erreur si on annule
    }
}

async function sauvegarderProfil(event) {
    event.preventDefault(); // Empêche le rechargement de la page

    const form = event.target;
    const formData = new FormData(form);
    const msgDiv = document.getElementById('profil-msg');

    try {
        const response = await fetch('api/modifier_profil.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.succes) {
            // 1. Mettre à jour les textes dans la page dynamiquement
            document.getElementById('txt-nom').textContent = formData.get('nom');
            document.getElementById('txt-prenom').textContent = formData.get('prenom');
            document.getElementById('txt-tel').textContent = formData.get('telephone') || '—';
            document.getElementById('txt-adresse').textContent = formData.get('adresse') || '—';

            // 2. Afficher un message de succès
            msgDiv.textContent = "Profil mis à jour avec succès !";
            msgDiv.style.backgroundColor = "#d4edda";
            msgDiv.style.color = "#155724";
            msgDiv.style.display = "block";

            // 3. Revenir au mode affichage après 1.5 seconde
            setTimeout(() => {
                toggleEditMode(false);
            }, 1500);

        } else {
            // Afficher l'erreur renvoyée par le PHP
            msgDiv.textContent = "Erreur : " + result.message;
            msgDiv.style.backgroundColor = "#f8d7da";
            msgDiv.style.color = "#721c24";
            msgDiv.style.display = "block";
        }
    } catch (error) {
        console.error("Erreur lors de la sauvegarde :", error);
        alert("Impossible de contacter le serveur.");
    }
}


async function envoyerNotation(idCommande) {
    const noteSelect = document.getElementById('note-val-' + idCommande);
    const noteValue = noteSelect.value;
    const container = document.getElementById('notation-container-' + idCommande);

    // Préparation des données
    const formData = new FormData();
    formData.append('id_commande', idCommande);
    formData.append('note', noteValue);

    try {
        // Animation : on grise un peu le temps de la requête
        container.style.opacity = "0.5";

        const response = await fetch('api/noter_commande.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.succes) {
            // Remplacement du formulaire par la note fixe
            container.innerHTML = `<span style="color:#27ae60; font-weight:600;">Note : ${result.note}/5 ⭐</span>`;
            container.style.opacity = "1";
        } else {
            alert("Erreur : " + result.message);
            container.style.opacity = "1";
        }
    } catch (error) {
        console.error("Erreur notation :", error);
        alert("Erreur réseau lors de l'envoi de la note.");
        container.style.opacity = "1";
    }
}