<?php
require_once 'db.php';
session_start();

// Sécurité : Si l'élève n'est pas connecté, retour à la page de connexion
if (!isset($_SESSION['id_eleve'])) {
    header('Location: connect.php');
    exit;
}

$id_eleve = $_SESSION['id_eleve'];
$message = '';
$erreur = '';

// ── ACTION 1 : TRAITER L'AJOUT D'UNE RÉSERVATION ──
if (isset($_GET['id'])) {
    $id_menu = intval($_GET['id']);

    // Éviter les doublons : Vérifier si ce plat est déjà réservé par cet élève
    $verif = $pdo->prepare("SELECT id_reservation FROM reservation WHERE id_eleve = ? AND id_menu = ?");
    $verif->execute([$id_eleve, $id_menu]);
    
    if ($verif->fetch()) {
        $erreur = "Vous avez déjà réservé ce plat !";
    } else {
        // On récupère l'id_repas associé à ce menu pour pouvoir remplir la colonne id_repas de ta table reservation
        $stmtMenu = $pdo->prepare("SELECT id_repas FROM menu WHERE id_menu = ? LIMIT 1");
        $stmtMenu->execute([$id_menu]);
        $menuInfo = $stmtMenu->fetch(PDO::FETCH_ASSOC);

        if ($menuInfo) {
            $id_repas = $menuInfo['id_repas'];

            // Insertion dans la table reservation (statut 'en attente' par défaut)
            $ins = $pdo->prepare("INSERT INTO reservation (id_eleve, id_menu, id_repas, statut) VALUES (?, ?, ?, 'en attente')");
            if ($ins->execute([$id_eleve, $id_menu, $id_repas])) {
                $message = "🎉 Votre réservation a été enregistrée avec succès !";
            } else {
                $erreur = "Une erreur est survenue lors de la réservation.";
            }
        } else {
            $erreur = "Ce menu n'existe pas.";
        }
    }
}

// ── ACTION 2 : TRAITER LA SUPPRESSION D'UNE RÉSERVATION ──
if (isset($_POST['supprimer_id'])) {
    $id_res_a_supprimer = intval($_POST['supprimer_id']);

    // Sécurité : On s'assure que la réservation appartient bien à l'élève connecté
    $del = $pdo->prepare("DELETE FROM reservation WHERE id_reservation = ? AND id_eleve = ?");
    if ($del->execute([$id_res_a_supprimer, $id_eleve])) {
        $message = "❌ Réservation annulée avec succès.";
    } else {
        $erreur = "Impossible d'annuler cette réservation.";
    }
}

// ── ACTION 3 : RÉCUPÉRER LES INFOS (DATE DANS MENU, NOM ET PRIX DANS REPAS) ──
// Jointure r.id_menu -> m.id_menu ET r.id_repas -> rep.id_repas
// On sélectionne rep.nom et rep.prix comme demandé !
$query = "SELECT r.id_reservation, r.statut, m.date_menu, rep.nom AS nom_repas, rep.prix 
          FROM reservation r
          JOIN menu m ON r.id_menu = m.id_menu
          JOIN repas rep ON r.id_repas = rep.id_repas
          WHERE r.id_eleve = ?
          ORDER BY m.date_menu DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_eleve]);
$mes_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations — CantineGo</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --vert: #2d6a4f;
            --vert-hover: #1b4332;
            --orange: #e8a838;
            --gris-fond: #f4f6f4;
            --texte: #2d3748;
            --rouge-del: #dc2626;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--gris-fond);
            color: var(--texte);
            padding: 2rem 1rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        h1 { color: var(--vert); margin-bottom: 0.5rem; font-size: 1.8rem; }
        .subtitle { font-size: 0.9rem; color: #666; margin-bottom: 2rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: var(--vert); }
        
        .btn-action { padding: 0.4rem 0.8rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: opacity 0.2s; }
        .btn-danger { background: var(--rouge-del); color: white; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; display: inline-block; margin-top: 1.5rem; }
        .btn-action:hover, .btn-back:hover { opacity: 0.85; }
        
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-transform: capitalize; }
        .badge-attente { background: #ffeeba; color: #856404; }
        .badge-confirme { background: #d4edda; color: #155724; }
        .badge-annule { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="container">
    <h1>📝 Gestion de vos réservations</h1>
    <p class="subtitle">Espace élève — Connecté en tant que : <strong><?= htmlspecialchars($_SESSION['nom_complet'] ?? 'Élève') ?></strong></p>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= $erreur ?></div>
    <?php endif; ?>

    <h3>📋 Liste de vos plats réservés</h3>

    <?php if (empty($mes_reservations)): ?>
        <p style="padding: 2rem; text-align: center; color: #999;">Vous n'avez pas encore effectué de réservation pour le moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date du repas</th>
                    <th>Repas</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mes_reservations as $res): ?>
                    <tr>
                        <td><strong><?= date('d/m/Y', strtotime($res['date_menu'])) ?></strong></td>
                        <td><?= htmlspecialchars($res['nom_repas']) ?></td>
                        <td><?= number_format($res['prix'], 0, ',', ' ') ?> FCFA</td>
                        <td>
                            <?php 
                            $statut = $res['statut'];
                            $classe_badge = 'badge-attente';
                            if ($statut === 'confirmée') $classe_badge = 'badge-confirme';
                            if ($statut === 'annulée') $classe_badge = 'badge-annule';
                            ?>
                            <span class="badge <?= $classe_badge ?>"><?= htmlspecialchars($statut) ?></span>
                        </td>
                        <td>
                            <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');" style="display:inline;">
                                <input type="hidden" name="supprimer_id" value="<?= $res['id_reservation'] ?>">
                                <button type="submit" class="btn-action btn-danger">Annuler</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="index.php" class="btn-back">⬅ Retour au menu de la cantine</a>
</div>

</body>
</html>