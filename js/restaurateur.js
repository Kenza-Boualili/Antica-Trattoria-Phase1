async function changerStatutResto(idCommande, nouveauStatut) {
    const formData = new FormData();
    formData.append('id', idCommande);
    formData.append('nouveau_statut', nouveauStatut);

    // Si on passe en livraison, on DOIT récupérer l'ID du livreur
    if (nouveauStatut === 'en_livraison') {
        const selectLivreur = document.getElementById('livreur-' + idCommande);
        const livreurId = selectLivreur.value;

        if (!livreurId) {
            alert("Veuillez choisir un livreur !");
            return;
        }
        formData.append('livreur_id', livreurId);
    }

    try {
        const response = await fetch('api/maj_commande.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.succes) {
            // Effet visuel : la carte disparaît
            const card = document.getElementById('card-' + idCommande);
            card.style.transform = "scale(0.8)";
            card.style.opacity = "0";
            
            setTimeout(() => {
                card.remove();
                // Si plus de commandes, on rafraîchit pour afficher le message "vide"
                if (document.querySelectorAll('.orders-grid .order-card').length === 0) {
                    location.reload();
                }
            }, 400);
        } else {
            alert("Erreur : " + result.message);
        }
    } catch (error) {
        console.error("Erreur Fetch :", error);
        alert("Le serveur ne répond pas.");
    }
}