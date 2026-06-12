<?php
require_once 'db.php';
session_start();

// Si déjà connecté, rediriger directement
if (isset($_SESSION['id_eleve'])) {
    header('Location: index.php');
    exit;
}

$erreur  = '';
$succes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom          = trim($_POST['nom'] ?? '');
    $prenom       = trim($_POST['prenom'] ?? '');
    $email        = trim($_POST['email'] ?? ''); 
    $filiere      = trim($_POST['filiere'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($confirmation)) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse e-mail n'est pas valide.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($mot_de_passe !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'email existe déjà
        $check = $pdo->prepare("SELECT id_eleve FROM eleve WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $erreur = "Cette adresse e-mail est déjà utilisée.";
        } else {
            // Insertion sans hachage
            $stmt = $pdo->prepare("INSERT INTO eleve (nom, prenom, email, filiere, mot_de_passe) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $filiere, $mot_de_passe]);

            // Connexion automatique après inscription
            $_SESSION['id_eleve']    = $pdo->lastInsertId();
            $_SESSION['nom_complet'] = $prenom . ' ' . $nom;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — CantineGo</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --vert:       #2d6a4f;
            --vert-hover: #1b4332;
            --orange:     #e8a838;
            --gris-fond:  #f4f6f4;
            --gris-bord:  #dde8e1;
            --texte:      #2d3748;
            --texte-doux: #6b7c6e;
            --erreur-bg:  #FEE2E2;
            --erreur-txt: #DC2626;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--gris-fond);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

       
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

        
        .page-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

      
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(45,106,79,.12);
            padding: 2.5rem 2.2rem;
            width: 100%;
            max-width: 460px;
        }

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
            margin-bottom: 1.5rem;
        }

        .separateur {
            width: 40px;
            height: 3px;
            background: var(--vert);
            border-radius: 2px;
            margin: 0 auto 1.8rem;
        }


        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group { margin-bottom: 1.1rem; }

        label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            color: var(--texte);
            margin-bottom: .4rem;
        }
        .label-optionnel {
            font-size: .75rem;
            font-weight: 400;
            color: var(--texte-doux);
            margin-left: .3rem;
        }

        input[type="text"],
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

       
        .force-wrap { margin-top: .4rem; display: none; }
        .force-barre {
            height: 4px;
            border-radius: 2px;
            background: var(--gris-bord);
            overflow: hidden;
        }
        .force-barre-inner {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: width .3s, background .3s;
        }
        .force-label {
            font-size: .75rem;
            color: var(--texte-doux);
            margin-top: .25rem;
        }


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

        
        .lien-connexion {
            text-align: center;
            font-size: .84rem;
            color: var(--texte-doux);
            margin-bottom: 1.3rem;
        }
        .lien-connexion a {
            color: var(--vert);
            font-weight: 600;
            text-decoration: none;
        }
        .lien-connexion a:hover { text-decoration: underline; }


        .btn-inscription {
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
        .btn-inscription:hover  { background: var(--vert-hover); }
        .btn-inscription:active { transform: scale(.98); }

       
        footer {
            text-align: center;
            padding: 1rem;
            font-size: .78rem;
            color: var(--texte-doux);
            border-top: 1px solid var(--gris-bord);
        }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
            .card { padding: 2rem 1.3rem; }
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
        <div class="card-titre">INSCRIPTION</div>
        <div class="card-sous-titre">Créez votre compte élève</div>
        <div class="separateur"></div>

        <?php if ($erreur): ?>
            <div class="erreur">❌ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <!-- Nom / Prénom -->
            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom"
                        placeholder="Dupont"
                        value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                        required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom"
                        placeholder="Jean"
                        value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                        required>
                </div>
            </div>

            
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                    placeholder="exemple@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required autofocus>
            </div>

            <!-- Filière -->
            <div class="form-group">
                <label for="filiere">
                    Filière <span class="label-optionnel">(optionnel)</span>
                </label>
                <input type="text" id="filiere" name="filiere"
                    placeholder="ex : SIL2, GIT1…"
                    value="<?= htmlspecialchars($_POST['filiere'] ?? '') ?>">
            </div>

            <!-- Mot de passe -->
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe"
                    placeholder="Minimum 6 caractères"
                    required>
                <div class="force-wrap" id="force-wrap">
                    <div class="force-barre">
                        <div class="force-barre-inner" id="force-barre-inner"></div>
                    </div>
                    <div class="force-label" id="force-label"></div>
                </div>
            </div>

            <!-- Confirmation -->
            <div class="form-group">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input type="password" id="confirmation" name="confirmation"
                    placeholder="Répétez votre mot de passe"
                    required>
            </div>

            <!-- Lien connexion -->
            <div class="lien-connexion">
                Vous avez déjà un compte ?
                <a href="connect.php">Connectez-vous ici</a>
            </div>

            <button type="submit" class="btn-inscription">Créer mon compte</button>

        </form>

    </div>
</div>

<footer>CantineGo &copy; 2026 — Projet SIL2</footer>

<script>
    // ── Indicateur de force du mot de passe ──
    const input  = document.getElementById('mot_de_passe');
    const wrap   = document.getElementById('force-wrap');
    const barre  = document.getElementById('force-barre-inner');
    const label  = document.getElementById('force-label');

    input.addEventListener('input', () => {
        const val = input.value;
        if (!val) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';

        let score = 0;
        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const niveaux = [
            { pct: '20%', color: '#DC2626', txt: 'Très faible' },
            { pct: '40%', color: '#F59E0B', txt: 'Faible' },
            { pct: '60%', color: '#e8a838', txt: 'Moyen' },
            { pct: '80%', color: '#2d6a4f', txt: 'Fort' },
            { pct: '100%',color: '#1b4332', txt: 'Très fort' },
        ];
        const n = niveaux[score - 1] || niveaux[0];
        barre.style.width      = n.pct;
        barre.style.background = n.color;
        label.textContent      = n.txt;
        label.style.color      = n.color;
    });
</script>

</body>
</html>