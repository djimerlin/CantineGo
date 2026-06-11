<?php
// ── CONNEXION BDD ──
require_once 'db.php';
session_start();

// Si déjà connecté, rediriger directement
if (isset($_SESSION['id_eleve'])) {
    header('Location: index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT id_eleve, nom, prenom, mot_de_passe FROM eleve WHERE email = ?");
        $stmt->execute([$email]);
        $eleve = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($eleve && password_verify($mot_de_passe, $eleve['mot_de_passe'])) {
            $_SESSION['id_eleve']    = $eleve['id_eleve'];
            $_SESSION['nom_complet'] = $eleve['prenom'] . ' ' . $eleve['nom'];
            header('Location: index.php');
            exit;
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — CantineGo</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --vert:         #2d6a4f;
            --vert-hover:   #1b4332;
            --orange:       #e8a838;
            --gris-fond:    #f4f6f4;
            --gris-bord:    #dde8e1;
            --texte:        #2d3748;
            --texte-doux:   #6b7c6e;
            --erreur-bg:    #FEE2E2;
            --erreur-txt:   #DC2626;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--gris-fond);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        nav {
            background: var(--vert);
            padding: 0 2rem;
            height: 62px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }
        .nav-logo {
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            letter-spacing: 1px;
        }
        .nav-logo span { color: var(--orange); }

        /* ── CENTRAGE ── */
        .page-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* ── CARTE LOGIN ── */
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(45,106,79,.12);
            padding: 2.5rem 2.2rem;
            width: 100%;
            max-width: 420px;
        }

        /* Logo centré dans la carte */
        .card-logo {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--vert);
            letter-spacing: 1px;
            margin-bottom: .3rem;
        }
        .card-logo span { color: var(--orange); }

        .card-titre {
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--texte);
            margin-bottom: .3rem;
            letter-spacing: .5px;
        }

        .card-sous-titre {
            text-align: center;
            font-size: .85rem;
            color: var(--texte-doux);
            margin-bottom: 1.8rem;
        }

        /* Séparateur vert sous le titre */
        .separateur {
            width: 40px;
            height: 3px;
            background: var(--vert);
            border-radius: 2px;
            margin: 0 auto 1.8rem;
        }

        /* ── FORMULAIRE ── */
        .form-group {
            margin-bottom: 1.2rem;
        }
        label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            color: var(--texte);
            margin-bottom: .4rem;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: .7rem 1rem;
            border: 1.5px solid var(--gris-bord);
            border-radius: 8px;
            font-size: .95rem;
            color: var(--texte);
            background: #fff;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        input:focus {
            border-color: var(--vert);
            box-shadow: 0 0 0 3px rgba(45,106,79,.12);
        }
        input::placeholder { color: #b2bdb8; }

        /* ── ERREUR ── */
        .erreur {
            background: var(--erreur-bg);
            color: var(--erreur-txt);
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: .7rem 1rem;
            font-size: .88rem;
            font-weight: 500;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        /* ── LIEN INSCRIPTION ── */
        .lien-inscription {
            text-align: center;
            font-size: .84rem;
            color: var(--texte-doux);
            margin-bottom: 1.3rem;
        }
        .lien-inscription a {
            color: var(--vert);
            font-weight: 600;
            text-decoration: none;
        }
        .lien-inscription a:hover {
            text-decoration: underline;
        }

        /* ── BOUTON ── */
        .btn-connexion {
            width: 100%;
            padding: .8rem;
            background: var(--vert);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: .3px;
            transition: background .2s, transform .1s;
        }
        .btn-connexion:hover   { background: var(--vert-hover); }
        .btn-connexion:active  { transform: scale(.98); }

        /* ── FOOTER ── */
        footer {
            text-align: center;
            padding: 1rem;
            font-size: .78rem;
            color: var(--texte-doux);
            border-top: 1px solid var(--gris-bord);
        }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="nav-logo">Cantine<span>Go</span></a>
</nav>

<div class="page-wrap">
    <div class="card">

        <div class="card-logo">Cantine<span>Go</span></div>
        <div class="card-titre">CONNEXION</div>
        <div class="card-sous-titre">Accédez à votre espace élève</div>
        <div class="separateur"></div>

        <?php if ($erreur): ?>
            <div class="erreur">❌ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="exemple@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input
                    type="password"
                    id="mot_de_passe"
                    name="mot_de_passe"
                    placeholder="••••••••"
                    required
                >
            </div>

            <div class="lien-inscription">
                Vous n'avez pas de compte ?
                <a href="inscrip.php">Inscrivez-vous ici</a>
            </div>

            <button type="submit" class="btn-connexion">Se connecter</button>

        </form>

    </div>
</div>

<footer>CantineGo &copy; 2026 — Projet SIL2</footer>

</body>
</html>