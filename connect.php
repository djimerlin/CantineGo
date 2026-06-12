<?php
require_once 'db.php';
session_start();

if (isset($_SESSION['id_eleve'])) {
    header('Location: index.php');
    exit;
} elseif (isset($_SESSION['admin_id'])) {
    header('Location: admindashboard.php');
    exit;
}

$erreur = '';
$etape_connexion_admin = false; // Passe à true quand la clé unique est correcte

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etape = $_POST['etape'] ?? '';

    //  ETAPE 1 : Vérification de la clé unique dans la BDD
    if ($etape === 'verif_cle') {
        $cle_saisie = trim($_POST['cle_admin'] ?? '');

        // On cherche si un admin possède cette clé unique
        $stmt = $pdo->prepare("SELECT id_admin FROM admin WHERE cle_unique = ? LIMIT 1");
        $stmt->execute([$cle_saisie]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            // Clé correcte ! On passe à l'étape suivante
            $etape_connexion_admin = true;
            $_POST['cle_validee'] = $cle_saisie;
        } else {
            $erreur = "Clé unique incorrecte.";
        }
    }

    // ── ÉTAPE 2 : Clé validée → Vérification Email & Mot de passe (EN CLAIR) ──
    elseif ($etape === 'admin_login') {
        $cle_validee = trim($_POST['cle_validee'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $mdp_saisi   = $_POST['mot_de_passe'] ?? '';

        // On cherche l'admin avec son email, sa clé ET son mot de passe en clair
        $stmt = $pdo->prepare("SELECT id_admin, nom FROM admin WHERE email = ? AND cle_unique = ? AND mot_de_passe = ? LIMIT 1");
        $stmt->execute([$email, $cle_validee, $mdp_saisi]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si la requête renvoie une ligne, c'est que tout est parfaitement correct
        if ($adminData) {
            $_SESSION['admin_id']  = $adminData['id_admin'];
            $_SESSION['admin_nom'] = $adminData['nom'];
            header('Location: admindashboard.php');
            exit;
        } else {
            $erreur = "Identifiants administrateur incorrects.";
            // On force le maintien de l'affichage du formulaire de login admin
            $etape_connexion_admin = true;
        }
    }

    // ── ÉTAPE ALTERNATIVE : Connexion élève standard (EN CLAIR) ──
    elseif ($etape === 'eleve') {
        $email        = trim($_POST['email'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';

        if (empty($email) || empty($mot_de_passe)) {
            $erreur = "Veuillez remplir tous les champs.";
        } else {
            // On cherche l'élève directement avec son email et son mot de passe en clair
            $stmt = $pdo->prepare("SELECT id_eleve, nom, prenom FROM eleve WHERE email = ? AND mot_de_passe = ?");
            $stmt->execute([$email, $mot_de_passe]);
            $eleve = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($eleve) {
                $_SESSION['id_eleve']    = $eleve['id_eleve'];
                $_SESSION['nom_complet'] = $eleve['prenom'] . ' ' . $eleve['nom'];
                header('Location: index.php');
                exit;
            } else {
                $erreur = "Email ou mot de passe incorrect.";
            }
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
            max-width: 420px;
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

        .form-group { margin-bottom: 1.2rem; }

        label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            color: var(--texte);
            margin-bottom: .4rem;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
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
        .lien-inscription a:hover { text-decoration: underline; }

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
        .btn-connexion:hover  { background: var(--vert-hover); }
        .btn-connexion:active { transform: scale(.98); }

        .admin-trigger {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--gris-bord);
            text-align: center;
        }
        .btn-admin-discret {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--texte-doux);
            font-size: .78rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .4rem .7rem;
            border-radius: 6px;
            transition: background .2s, color .2s;
        }
        .btn-admin-discret:hover {
            background: var(--gris-fond);
            color: var(--orange);
        }

        .zone-admin-bloc {
            display: none;
            margin-top: 1.25rem;
            border-radius: 10px;
            padding: 1.25rem;
            animation: fadeIn .2s ease;
            text-align: left;
        }
        .zone-admin-bloc.visible { display: block; }

        .style-cle {
            background: #fffbf0;
            border: 1px solid #f0d080;
        }
        .style-login {
            background: #f4fbf7;
            border: 1px solid var(--gris-bord);
        }

        .zone-titre {
            font-size: .8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

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
        <div class="card-sous-titre">Espace d'authentification</div>
        <div class="separateur"></div>

        <?php if ($erreur): ?>
            <div class="erreur">❌ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if (!$etape_connexion_admin && (!isset($_POST['etape']) || $_POST['etape'] === 'eleve' || ($erreur && $_POST['etape'] === 'verif_cle'))): ?>
        <form method="POST" action="">
            <input type="hidden" name="etape" value="eleve">
            <div class="form-group">
                <label for="email">Adresse e-mail élève</label>
                <input type="email" id="email" name="email" placeholder="exemple@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="••••••••" required>
            </div>
            <div class="lien-inscription">
                Vous n'avez pas de compte ? <a href="iscrip.php">Inscrivez-vous ici</a>
            </div>
            <button type="submit" class="btn-connexion">Se connecter</button>
        </form>

        <div class="admin-trigger">
            <button class="btn-admin-discret" type="button" onclick="toggleAdminForm()">
                🔒 Accès Administrateur
            </button>
        </div>
        <?php endif; ?>


        <div class="zone-admin-bloc style-cle <?= (isset($_POST['etape']) && $_POST['etape'] === 'verif_cle' && !$etape_connexion_admin) ? 'visible' : '' ?>" id="bloc-cle-admin">
            <div class="zone-titre" style="color: #92600a;">🔑 Clé Secrète Admin</div>
            <form method="POST" action="">
                <input type="hidden" name="etape" value="verif_cle">
                <div class="form-group">
                    <label>Veuillez renseigner la clé unique d'accès</label>
                    <input type="text" name="cle_admin" placeholder="Ex: CG-XXXX" value="<?= htmlspecialchars($_POST['cle_admin'] ?? '') ?>" required>
                </div>
                <button type="submit" class="btn-connexion" style="background: #92600a;">Valider la clé</button>
                <a href="connexion.php" style="display:block; text-align:center; font-size:.8rem; margin-top:.8rem; color:var(--texte-doux); text-decoration:none;">Retour aux élèves</a>
            </form>
        </div>


        <?php if ($etape_connexion_admin): ?>
        <div class="zone-admin-bloc style-login visible">
            <div class="zone-titre" style="color: var(--vert);">🔓 Clé validée : Identifiants requis</div>
            <form method="POST" action="">
                <input type="hidden" name="etape" value="admin_login">
                <input type="hidden" name="cle_validee" value="<?= htmlspecialchars($_POST['cle_admin'] ?? $_POST['cle_validee'] ?? '') ?>">

                <div class="form-group">
                    <label>Adresse e-mail admin</label>
                    <input type="email" name="email" placeholder="admin@cantinego.com" value="<?= htmlspecialchars($_POST['email'] ?? 'admin@cantinego.com') ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="mot_de_passe" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-connexion" style="background: var(--vert);">Se connecter</button>
                <a href="connect.php" style="display:block; text-align:center; font-size:.8rem; margin-top:.8rem; color:var(--texte-doux); text-decoration:none;">Annuler</a>
            </form>
        </div>
        <?php endif; ?>

    </div>
</div>

<footer>CantineGo &copy; 2026 — Projet SIL2</footer>

<script>
function toggleAdminForm() {
    const blocCle = document.getElementById('bloc-cle-admin');
    const formEleve = document.querySelector('form[action=""]');
    const trigger = document.querySelector('.admin-trigger');
    
    if(formEleve) formEleve.style.display = 'none';
    if(trigger) trigger.style.display = 'none';
    
    blocCle.classList.add('visible');
    blocCle.querySelector('input[name="cle_admin"]').focus();
}
</script>
</body>
</html>