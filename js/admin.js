
async function toggleUserStatus(userId) {
    const btn = document.getElementById('btn-toggle-' + userId);
    const row = document.getElementById('user-row-' + userId);
    
    // On verrouille le bouton pendant le chargement
    btn.disabled = true;
    btn.style.opacity = "0.5";

    try {
        const response = await fetch('api/toggle_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(userId)
        });

        const result = await response.json();

        if (result.succes) {
            if (result.nouvel_etat) {
                // Utilisateur réactivé
                btn.textContent = "Bloquer";
                btn.className = "btn-admin-action btn-bloquer";
                row.classList.remove('compte-inactif');
            } else {
                // Utilisateur bloqué
                btn.textContent = "Activer";
                btn.className = "btn-admin-action btn-activer";
                row.classList.add('compte-inactif');
            }
        } else {
            alert("Erreur : " + result.message);
        }
    } catch (error) {
        console.error("Erreur Fetch Admin :", error);
        alert("Problème de connexion au serveur.");
    } finally {
        btn.disabled = false;
        btn.style.opacity = "1";
    }
}