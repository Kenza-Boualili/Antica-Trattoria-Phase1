<?php
// On vérifie si on a des plats à afficher
$aucunPlatTrouve = true;

foreach ($categories as $catKey => $platsCat): 
    if (!empty($platsCat)): 
        $aucunPlatTrouve = false;
?>
        <section class="carte-section" id="<?php echo $catKey; ?>">
            <h1 class="carte-title"><?php echo $nomCategories[$catKey]; ?></h1>
            
            <div class="carte-grid">
                <?php foreach ($platsCat as $plat): ?>
                    <div class="carte-item">
                        <img src="<?php echo htmlspecialchars($plat['image']); ?>" 
                             alt="<?php echo htmlspecialchars($plat['nom']); ?>" 
                             class="carte-image"
                             onerror="this.src='photo/placeholder.jpg'">

                        <h3 class="carte-item-name"><?php echo htmlspecialchars($plat['nom']); ?></h3>

                        <p class="carte-item-description"><?php echo htmlspecialchars($plat['description']); ?></p>

                        <p class="carte-item-prix"><?php echo number_format($plat['prix'], 2, ',', ''); ?> €</p>

                        <?php if (isset($plat['vegetarien']) && $plat['vegetarien']): ?>
                            <span class="badge-veg">🌿 Végétarien</span>
                        <?php endif; ?>

                        <?php if (isset($plat['pimente']) && $plat['pimente']): ?>
                            <span class="badge-piment">🌶 Pimenté</span>
                        <?php endif; ?>

                        <?php if (!empty($plat['allergenes'])): ?>
                            <p class="carte-item-allergenes">
                                Allergènes : <?php echo implode(', ', $plat['allergenes']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (estConnecte() && getRoleConnecte() === 'client'): ?>
                            <button class="btn-panier" 
                                    onclick="window.location.href='panier.php?ajouter=<?php echo $plat['id']; ?>'">
                                AJOUTER AU PANIER
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
<?php 
    endif; 
endforeach; 

// Si après avoir parcouru toutes les catégories, on n'a rien trouvé
if ($aucunPlatTrouve): 
?>
    <section class="carte-section">
        <div class="carte-grid">
            <p class="aucun-resultat">Désolé, aucun plat ne correspond à vos critères de recherche.</p>
        </div>
    </section>
<?php endif; ?>