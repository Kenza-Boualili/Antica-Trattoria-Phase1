
async function validerLivraison(idCommande, nouveauStatut) {
    // Confirmation pour l'abandon
    if (nouveauStatut === 'abandonnee' && !confirm("Confirmer l'abandon de cette course ?")) {
        return;
    }

    const formData = new FormData();
    formData.append('id', idCommande);
    formData.append('nouveau_statut', nouveauStatut);

    try {
        const response = await fetch('api/maj_commande.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.succes) {
            // Animation de sortie
            const card = document.getElementById('card-' + idCommande);
            card.style.transform = "translateX(100px)";
            card.style.opacity = "0";
            
            // On recharge la page après l'animation pour mettre à jour l'historique
            setTimeout(() => {
                location.reload();
            }, 400);
        } else {
            alert("Erreur serveur : " + result.message);
        }
    } catch (error) {
        console.error("Erreur de connexion :", error);
        alert("Impossible de joindre le serveur. Vérifiez votre connexion.");
    }
}