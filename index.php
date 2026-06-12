<?php
require_once 'includes/db.php';
require_once 'includes/fonction.php';

// Récupérer les bornes de la semaine en cours
$lundi    = date('Y-m-d', strtotime('monday this week'));
$vendredi = date('Y-m-d', strtotime('friday this week'));

// Jointure SQL pour récupérer les détails du repas associé à chaque menu
$stmt = $pdo->prepare("
    SELECT m.*, r.nom AS nom_repas, r.prix, r.image 
    FROM menu m
    INNER JOIN repas r ON m.id_repas = r.id_repas
    WHERE m.date_menu BETWEEN ? AND ? 
    ORDER BY m.date_menu ASC
");
$stmt->execute([$lundi, $vendredi]);
$menus = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CantineGo — Accueil</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
  <span class="logo">Cantine<span>Go</span></span>
  <div>
    <a href="reservation.php">Réservation</a>
    <?php if (estConnecteEleve()): ?>
      <a href="deconnexion.php">Déconnexion</a>
    <?php else: ?>
      <a href="deconnexion.php">Déconnexion</a>
     
    <?php endif; ?>
  </div>
</nav>

<div class="hero">
  <h1>🍽️ Menu de la semaine</h1>
  <p>Consultez les repas et réservez votre place à la cantine.</p>
</div>

<div class="section">
  <div class="titre-section" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <span>📅 Cette semaine</span>
    <?php if (isset($_SESSION['id_eleve'])): ?>
        <a href="reservation.php" class="btn" style="background: #e8a838; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: 600;">
             Voir mes réservations
        </a>
    <?php endif; ?>
  </div>

  <?php if (empty($menus)): ?>
    <p style="color:#999; text-align: center; padding: 2rem;">Aucun menu disponible cette semaine.</p>
  <?php else: ?>
    <div class="grille">
        <?php foreach ($menus as $menu): ?>
            <div class="carte">
                <div class="carte-header">
                    <?= date('d/m/Y', strtotime($menu['date_menu'])) ?> - <?= htmlspecialchars($menu['plat_principal'] ?? '') ?>
                </div>
                
                <?php if (!empty($menu['image'])): ?>
                    <img src="uploads/repas/<?= htmlspecialchars($menu['image']) ?>" alt="<?= htmlspecialchars($menu['nom_repas']) ?>" class="carte-img" style="width:100%; height:150px; object-fit:cover; display:block;">
                <?php else: ?>
                    <div class="carte-no-img" style="width:100%; height:150px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#ccc;">○</div>
                <?php endif; ?>

                <div class="carte-body" style="padding: 1rem;">
                    <div class="carte-plat" style="font-weight:600; font-size:1.1rem; margin-bottom:0.25rem;">
                        <?= htmlspecialchars($menu['nom_repas']) ?>
                    </div>
                    
                    <div class="carte-prix" style="color:#888884; font-size:0.9rem; font-weight:500;">
                        <?= number_format($menu['prix'], 0, ',', ' ') ?> FCFA
                    </div>
                    
                    <?php if (isset($_SESSION['id_eleve'])): ?>
                        <a href="reserver.php?id=<?= $menu['id_menu'] ?>"
                           class="btn btn-vert btn-full" style="margin-top:10px; display:block; text-align:center; text-decoration:none; background: #2d6a4f; color: white; padding: 0.6rem; border-radius: 6px; font-weight: bold;">
                           🛒 Réserver ce plat
                        </a>
                    <?php else: ?>
                        <p style="font-size: 0.8rem; color: #ff6b6b; margin-top: 10px; text-align: center;">Connectez-vous pour réserver</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<footer style="text-align:center; padding:2rem 1rem; margin-top:3rem; border-top:1px solid #e5e5e3; color:#888884; font-size:0.85rem;">
  CantineGo &copy; <?= date('Y') ?> — Projet SIL2
</footer>

</body>
</html>