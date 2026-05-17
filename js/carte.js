
async function filtrerLaCarte(event) {
    if (event) event.preventDefault(); // Empêche le rechargement si c'est le formulaire de recherche

    const search = document.getElementById('input-search').value;
    const saveur = document.getElementById('select-saveur').value;
    const allergene = document.getElementById('select-allergene').value;
    const container = document.getElementById('menu-container');

    // Afficher un petit effet de chargement
    container.style.opacity = "0.5";

    try {
        // Construction de l'URL avec les paramètres
        
        const url = `api/filtrer_plats.php?search=${encodeURIComponent(search)}&saveur=${saveur}&allergene=${allergene}`;
        
        const response = await fetch(url);
        const html = await response.text();

        // Mise à jour de la page sans rechargement
        container.innerHTML = html;
    } catch (error) {
        console.error("Erreur lors du filtrage :", error);
    } finally {
        container.style.opacity = "1";
    }
}