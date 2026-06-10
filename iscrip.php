<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (estConnecteEleve()) rediriger('index.php');

$erreur = $succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = escape($_POST['nom'] ?? '');
    $prenom = escape($_POST['prenom'] ?? '');
    $email  = escape($_POST['email'] ?? '');
    $classe = escape($_POST['classe'] ?? '');
    $mdp    = $_POST['mot_de_passe'] ?? '';
    $mdp2   = $_POST['confirm_mdp'] ?? '';

    if ($mdp !== $mdp2) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mdp) < 6) {
        $erreur = "Le mot de passe doit faire au moins 6 caractères.";
    } else {
        $stmt = $pdo->prepare("SELECT id_eleve FROM eleve WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreur = "Cet email est déjà utilisé.";
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO eleve (nom, prenom, email, mot_de_passe, classe) VALUES (?,?,?,?,?)");
            $stmt->execute([$nom, $prenom, $email, $hash, $classe]);
            $succes = "Compte créé avec succès ! Vous pouvez vous connecter.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CantineGo — Inscription</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="logo">Cantine<span>Go</span></a>
  <div>
    <a href="index.php">Accueil</a>
    <a href="connexion.php">Connexion</a>
  </div>
</nav>

<div class="form-card">
  <div class="form-titre">📝 Créer un compte</div>

  <?php if ($erreur): ?>
    <div class="alerte alerte-err"><?= $erreur ?></div>
  <?php endif; ?>
  <?php if ($succes): ?>
    <div class="alerte alerte-ok"><?= $succes ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-groupe">
      <label>Nom</label>
      <input type="text" name="nom" required placeholder="Votre nom">
    </div>
    <div class="form-groupe">
      <label>Prénom</label>
      <input type="text" name="prenom" required placeholder="Votre prénom">
    </div>
    <div class="form-groupe">
      <label>Email</label>
      <input type="email" name="email" required placeholder="exemple@email.com">
    </div>
    <div class="form-groupe">
      <label>Classe</label>
      <input type="text" name="classe" placeholder="Ex : SIL2">
    </div>
    <div class="form-groupe">
      <label>Mot de passe</label>
      <input type="password" name="mot_de_passe" required placeholder="Min. 6 caractères">
    </div>
    <div class="form-groupe">
      <label>Confirmer le mot de passe</label>
      <input type="password" name="confirm_mdp" required placeholder="Répéter le mot de passe">
    </div>
    <button type="submit" class="btn btn-vert btn-full">Créer mon compte</button>
  </form>

  <p style="text-align:center;margin-top:15px;font-size:14px;color:#999">
    Déjà inscrit ? <a href="connexion.php">Se connecter</a>
  </p>
</div>

<footer>CantineGo &copy; <?= date('Y') ?></footer>
</body>
</html>