
// Crée ou met à jour un cookie valide sur tout le site
function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Lax";
}

// Récupère la valeur d'un cookie par son nom
function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// Déclenché lors du clic sur le bouton du menu
function basculerTheme() {
    const themeActuel = getCookie("theme") || "clair";
    const nouveauTheme = (themeActuel === "clair") ? "sombre" : "clair";
    
    // Sauvegarde immédiate dans le cookie
    setCookie("theme", nouveauTheme, 30);
    // Application du changement visuel (chargement/déchargement du CSS)
    appliquerTheme(nouveauTheme);
}

// Gère l'injection et la suppression de darkmode.css sans rechargement
function appliquerTheme(theme) {
    const btn = document.getElementById('btn-theme');
    let linkDark = document.getElementById('css-darkmode');

    if (theme === "sombre") {
        // Si la feuille de style sombre n'est pas encore chargée, on la génère
        if (!linkDark) {
            linkDark = document.createElement('link');
            linkDark.id = 'css-darkmode';
            linkDark.rel = 'stylesheet';
            linkDark.href = 'dark-mode.css'; // Chemin vers votre fichier
            document.head.appendChild(linkDark);
        }
        if (btn) btn.textContent = "☀️ Mode clair";
    } else {
        // Si on repasse en mode clair, on retire le fichier CSS sombre
        if (linkDark) {
            linkDark.remove();
        }
        if (btn) btn.textContent = "🌙 Mode sombre";
    }
}

// Vérification de sécurité au chargement initial de la page
window.addEventListener('DOMContentLoaded', () => {
    const themeSauvegarde = getCookie("theme");
    if (themeSauvegarde === "sombre") {
        appliquerTheme("sombre");
    }
});