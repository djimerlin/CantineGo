<?php
require_once 'includes/db.php';
require_once 'includes/fonction.php';

// Récupérer les menus de la semaine
$lundi    = date('Y-m-d', strtotime('monday this week'));
$vendredi = date('Y-m-d', strtotime('friday this week'));
$stmt = $pdo->prepare("SELECT * FROM menu WHERE date_menu BETWEEN ? AND ? ORDER BY date_menu");
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

<!-- NAVBAR -->
<nav class="navbar">
  <span class="logo">Cantine<span>Go</span></span>
  <div>
    <a href="index.php">Accueil</a>
    <?php if (estConnecteEleve()): ?>
      <a href="deconnexion.php">Déconnexion</a>
    <?php else: ?>
      <a href="connect.php">Connexion</a>
      <a href="iscrip.php">S'inscrire</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <h1>🍽️ Menu de la semaine</h1>
  <p>Consultez les repas et réservez votre place à la cantine.</p>
  <?php if (!estConnecteEleve()): ?>
    <a href="connect.php" class="btn btn-orange">Se connecter pour réserver</a>
  <?php endif; ?>
</div>

<!-- MENUS -->
<div class="section">
  <div class="titre-section">📅 Cette semaine</div>

  <?php if (empty($menus)): ?>
    <p style="color:#999">Aucun menu disponible cette semaine.</p>
  <?php else: ?>
    <div class="grille">
      <?php foreach ($menus as $menu): ?>
        <div class="carte">
          <div class="carte-header"><?= formatDate($menu['date_menu']) ?></div>
          <div class="carte-body">
            <div class="carte-plat"><?= escape($menu['plat_principal']) ?></div>
            <div class="carte-prix"><?= formatPrix($menu['prix']) ?></div>
            <?php if (estConnecteEleve()): ?>
              <a href="reservation.php?id=<?= $menu['id_menu'] ?>"
                 class="btn btn-vert btn-full" style="margin-top:10px">
                Réserver
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<footer>CantineGo &copy; <?= date('Y') ?> — Projet SIL2</footer>

</body>
</html>