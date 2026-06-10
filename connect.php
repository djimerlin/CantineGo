<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (estConnecteEleve()) rediriger('index.php');

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = escape($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';
    $role  = $_POST['role'] ?? 'eleve';

    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            $_SESSION['admin_id']  = $user['id_admin'];
            $_SESSION['admin_nom'] = $user['nom'];
            rediriger('admin/dashboard.php');
        }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM eleve WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            $_SESSION['eleve_id']  = $user['id_eleve'];
            $_SESSION['eleve_nom'] = $user['prenom'];
            rediriger('index.php');
        }
    }
    $erreur = "Email ou mot de passe incorrect.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CantineGo — Connexion</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="logo">Cantine<span>Go</span></a>
  <div>
    <a href="index.php">Accueil</a>
    <a href="inscription.php">S'inscrire</a>
  </div>
</nav>

<div class="form-card">
  <div class="form-titre">🔑 Connexion</div>

  <?php if ($erreur): ?>
    <div class="alerte alerte-err"><?= $erreur ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-groupe">
      <label>Rôle</label>
      <select name="role">
        <option value="eleve">Élève</option>
        <option value="admin">Administrateur</option>
      </select>
    </div>
    <div class="form-groupe">
      <label>Email</label>
      <input type="email" name="email" required placeholder="exemple@email.com">
    </div>
    <div class="form-groupe">
      <label>Mot de passe</label>
      <input type="password" name="mot_de_passe" required placeholder="••••••••">
    </div>
    <button type="submit" class="btn btn-vert btn-full">Se connecter</button>
  </form>

  <p style="text-align:center;margin-top:15px;font-size:14px;color:#999">
    Pas encore inscrit ? <a href="inscription.php">Créer un compte</a>
  </p>
</div>

<footer>CantineGo &copy; <?= date('Y') ?></footer>
</body>
</html>