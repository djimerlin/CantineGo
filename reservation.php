<?php
// CONNEXION via db.php partagé
require_once 'db.php';

// SESSION ÉLEVe
session_start();
$id_eleve    = (int) ($_SESSION['id_eleve'] ?? 0);
$nom_complet = '';
$s = $pdo->prepare("SELECT nom, prenom FROM eleve WHERE id_eleve = ?");
$s->execute([$id_eleve]);
$eleve = $s->fetch(PDO::FETCH_ASSOC);
if ($eleve) {
    $nom_complet = htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']);
}

// ACTIONS : CONFIRMER / ANNULER
$message      = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id_reservation'])) {
    $id_resa = (int) $_POST['id_reservation'];
    $action  = $_POST['action'];

    $check = $pdo->prepare("SELECT id_reservation, statut FROM reservation WHERE id_reservation = ? AND id_eleve = ?");
    $check->execute([$id_resa, $id_eleve]);
    $resa = $check->fetch(PDO::FETCH_ASSOC);

    if (!$resa) {
        $message      = "Réservation introuvable.";
        $message_type = "error";
    } elseif ($resa['statut'] === 'annulée') {
        $message      = "Cette réservation est déjà annulée.";
        $message_type = "warning";
    } else {
        if ($action === 'confirmer' && $resa['statut'] === 'en attente') {
            $pdo->prepare("UPDATE reservation SET statut = 'confirmée' WHERE id_reservation = ? AND id_eleve = ?")
                ->execute([$id_resa, $id_eleve]);
            $message      = "Réservation confirmée avec succès.";
            $message_type = "success";
        } elseif ($action === 'annuler') {
            $pdo->prepare("UPDATE reservation SET statut = 'annulée' WHERE id_reservation = ? AND id_eleve = ?")
                ->execute([$id_resa, $id_eleve]);
            $message      = "Réservation annulée.";
            $message_type = "info";
        }
    }
}

// RÉCUPÉRATION DES RÉSERVATIONS
$rows       = [];
$par_periode = [];
$total = $confirmees = $en_attente = $annulees = 0;

$sql = "
    SELECT
        r.id_reservation,
        r.date_reservation,
        r.statut,
        rp.nom   AS nom_repas,
        rp.prix  AS prix_repas,
        YEARWEEK(r.date_reservation, 1) AS semaine_code,
        DATE_FORMAT(
            DATE_SUB(r.date_reservation, INTERVAL WEEKDAY(r.date_reservation) DAY),
            '%d/%m/%Y'
        ) AS debut_semaine,
        DATE_FORMAT(
            DATE_ADD(r.date_reservation, INTERVAL (6 - WEEKDAY(r.date_reservation)) DAY),
            '%d/%m/%Y'
        ) AS fin_semaine
    FROM reservation r
    JOIN repas rp ON r.id_repas = rp.id_repas
    WHERE r.id_eleve = :id_eleve
    ORDER BY r.date_reservation DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id_eleve' => $id_eleve]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $cle = $row['semaine_code'];
    if (!isset($par_periode[$cle])) {
        $par_periode[$cle] = [
            'label'        => "Semaine du " . $row['debut_semaine'] . " au " . $row['fin_semaine'],
            'reservations' => []
        ];
    }
    $par_periode[$cle]['reservations'][] = $row;
}

$total      = count($rows);
$confirmees = count(array_filter($rows, fn($r) => $r['statut'] === 'confirmée'));
$en_attente = count(array_filter($rows, fn($r) => $r['statut'] === 'en attente'));
$annulees   = count(array_filter($rows, fn($r) => $r['statut'] === 'annulée'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations — CantineGo</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            /* Palette index.php */
            --vert:          #2d6a4f;
            --vert-clair:    #e8f5ee;
            --vert-hover:    #1b4332;
            --orange:        #e8a838;
            --orange-hover:  #c98a1a;
            --dark:          #1a1a2e;
            --gris-fond:     #f4f6f4;
            --gris-bord:     #dde8e1;
            --texte:         #2d3748;
            --texte-doux:    #6b7c6e;

            
            --att-bg:  #FFF8E1; --att-txt: #e8a838;
            --ok-bg:   #e8f5ee; --ok-txt:  #2d6a4f;
            --ann-bg:  #FEE2E2; --ann-txt: #DC2626;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--gris-fond);
            color: var(--texte);
            min-height: 100vh;
        }


        nav {
            background: var(--vert);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 62px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }
        .nav-logo {
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
            text-decoration: none;
        }
        .nav-logo span { color: var(--orange); }
        .nav-links { display: flex; gap: 1.8rem; align-items: center; }
        .nav-links a {
            color: rgba(255,255,255,.85);
            text-decoration: none;
            font-size: .92rem;
            font-weight: 500;
            transition: color .2s;
        }
        .nav-links a:hover { color: #fff; }
        .nav-links a.active {
            color: var(--orange);
            font-weight: 700;
            border-bottom: 2px solid var(--orange);
            padding-bottom: 2px;
        }
        .nav-user {
            color: rgba(255,255,255,.8);
            font-size: .85rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .nav-user strong { color: #fff; }
        .nav-user a {
            color: #fca5a5;
            text-decoration: none;
            font-size: .85rem;
        }
        .nav-user a:hover { color: #fff; }

       
        main {
            max-width: 1050px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header { margin-bottom: 1.5rem; }
        .page-header h1 {
            font-size: 1.6rem;
            color: var(--vert);
            font-weight: 700;
        }
        .page-header p {
            color: var(--texte-doux);
            font-size: .95rem;
            margin-top: .3rem;
        }

        
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.8rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            border-left: 4px solid var(--vert);
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
        }
        .stat-card.attente  { border-color: var(--att-txt); }
        .stat-card.confirme { border-color: var(--ok-txt); }
        .stat-card.annule   { border-color: var(--ann-txt); }
        .stat-card .val { font-size: 1.9rem; font-weight: 700; color: var(--dark); line-height: 1; }
        .stat-card .lbl { font-size: .8rem; color: var(--texte-doux); margin-top: .3rem; }

       
        .flash {
            padding: .85rem 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.2rem;
            font-size: .92rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .flash.success { background: var(--ok-bg);  color: var(--ok-txt);  border: 1px solid #b7dfc8; }
        .flash.error   { background: var(--ann-bg); color: var(--ann-txt); border: 1px solid #fecaca; }
        .flash.warning { background: var(--att-bg); color: var(--att-txt); border: 1px solid #fde68a; }
        .flash.info    { background: var(--vert-clair); color: var(--vert); border: 1px solid #b7dfc8; }

        
        .alert-login {
            background: #fff8e1;
            border: 1px solid var(--orange);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            color: var(--texte);
        }
        .alert-login a {
            color: var(--vert);
            font-weight: 600;
            text-decoration: underline;
        }

        
        .empty {
            text-align: center;
            padding: 3rem;
            background: #fff;
            border-radius: 12px;
            color: var(--texte-doux);
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
        }
        .empty .icon { font-size: 3rem; margin-bottom: 1rem; }
        .empty h3 { font-size: 1.1rem; color: var(--dark); margin-bottom: .5rem; }
        .empty a {
            display: inline-block;
            margin-top: 1rem;
            background: var(--orange);
            color: #fff;
            padding: .6rem 1.6rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: .9rem;
            transition: background .2s;
        }
        .empty a:hover { background: var(--orange-hover); }

       
        .periode { margin-bottom: 2rem; }
        .periode-header {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin-bottom: .8rem;
        }
        .periode-label {
            font-size: .82rem;
            font-weight: 700;
            color: var(--vert);
            text-transform: uppercase;
            letter-spacing: .5px;
            background: var(--vert-clair);
            padding: .3rem .9rem;
            border-radius: 20px;
            border: 1px solid #b7dfc8;
        }
        .periode-count { font-size: .8rem; color: var(--texte-doux); }

       
        .table-wrap {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--vert); }
        thead th {
            padding: .75rem 1rem;
            text-align: left;
            font-size: .82rem;
            font-weight: 600;
            color: #fff;
            letter-spacing: .3px;
        }
        tbody tr { border-bottom: 1px solid var(--gris-bord); transition: background .15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f0f7f3; }
        tbody td { padding: .8rem 1rem; font-size: .9rem; vertical-align: middle; }

       
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .8rem;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
        }
        .badge.attente  { background: var(--att-bg); color: var(--att-txt); }
        .badge.confirme { background: var(--ok-bg);  color: var(--ok-txt); }
        .badge.annule   { background: var(--ann-bg); color: var(--ann-txt); }


        .actions { display: flex; gap: .5rem; flex-wrap: wrap; }
        .btn {
            padding: .35rem .9rem;
            border: none;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
        }
        .btn:hover   { opacity: .88; transform: translateY(-1px); }
        .btn:active  { transform: translateY(0); }
        .btn-confirmer { background: var(--vert);    color: #fff; }
        .btn-annuler   { background: var(--ann-txt); color: #fff; }
        .btn-disabled  { background: var(--gris-bord); color: var(--texte-doux); cursor: not-allowed; }


        footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--texte-doux);
            font-size: .82rem;
            margin-top: 2rem;
            border-top: 1px solid var(--gris-bord);
        }

        
        @media (max-width: 680px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            thead { display: none; }
            tbody tr {
                display: block;
                padding: .8rem;
                margin-bottom: .5rem;
                border-radius: 8px;
                border: 1px solid var(--gris-bord);
            }
            tbody td {
                display: flex;
                justify-content: space-between;
                padding: .4rem 0;
                font-size: .85rem;
                border: none;
            }
            tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--texte-doux);
                font-size: .8rem;
            }
        }
    </style>
</head>
<body>


<nav>
    <a href="index.php" class="nav-logo">Cantine<span>Go</span></a>
    <div class="nav-links">
        <a href="index.php">Accueil</a>
        <a href="mes_reservations.php" class="active">Mes réservations</a>
    </div>
    <div class="nav-user">
        Bonjour, <strong><?= $nom_complet ?></strong>
        &nbsp;|&nbsp;
    </div>
</nav>


<main>

    <div class="page-header">
        <h1>Mes réservations</h1>
        <p>Retrouvez toutes vos réservations classées par semaine.</p>
    </div>

   
        <div class="stats">
            <div class="stat-card">
                <div class="val"><?= $total ?></div>
                <div class="lbl">Total</div>
            </div>
            <div class="stat-card attente">
                <div class="val"><?= $en_attente ?></div>
                <div class="lbl">En attente</div>
            </div>
            <div class="stat-card confirme">
                <div class="val"><?= $confirmees ?></div>
                <div class="lbl">Confirmées</div>
            </div>
            <div class="stat-card annule">
                <div class="val"><?= $annulees ?></div>
                <div class="lbl">Annulées</div>
            </div>
        </div>


        <?php if ($message): ?>
            <div class="flash <?= $message_type ?>">
                <?= ['success'=>'✅','error'=>'❌','warning'=>'⚠️','info'=>'ℹ️'][$message_type] ?? '' ?>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

       
        <?php if (empty($par_periode)): ?>
            <div class="empty">
                <div class="icon">🍽️</div>
                <h3>Aucune réservation pour le moment</h3>
                <p>Consultez le menu et réservez votre premier repas.</p>
                <a href="index.php">Voir le menu</a>
            </div>

        <?php else: ?>
            <?php foreach ($par_periode as $periode): ?>
                <div class="periode">
                    <div class="periode-header">
                        <span class="periode-label"><?= htmlspecialchars($periode['label']) ?></span>
                        <span class="periode-count">
                            <?= count($periode['reservations']) ?> réservation<?= count($periode['reservations']) > 1 ? 's' : '' ?>
                        </span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Repas</th>
                                    <th>Prix</th>
                                    <th>Date de réservation</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($periode['reservations'] as $r): ?>
                                <?php
                                    $statut      = $r['statut'];
                                    $badge_class = match($statut) {
                                        'confirmée' => 'confirme',
                                        'annulée'   => 'annule',
                                        default     => 'attente'
                                    };
                                    $badge_icon  = match($statut) {
                                        'confirmée' => '✔',
                                        'annulée'   => '✖',
                                        default     => '⏳'
                                    };
                                    $date_fmt = date('d/m/Y à H:i', strtotime($r['date_reservation']));
                                ?>
                                <tr>
                                    <td data-label="#"><?= $r['id_reservation'] ?></td>
                                    <td data-label="Repas"><strong><?= htmlspecialchars($r['nom_repas']) ?></strong></td>
                                    <td data-label="Prix"><?= number_format($r['prix_repas'], 0, ',', ' ') ?> FCFA</td>
                                    <td data-label="Date"><?= $date_fmt ?></td>
                                    <td data-label="Statut">
                                        <span class="badge <?= $badge_class ?>"><?= $badge_icon ?> <?= ucfirst($statut) ?></span>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="actions">
                                            <?php if ($statut === 'en attente'): ?>
                                                <form method="POST" style="display:inline">
                                                    <input type="hidden" name="id_reservation" value="<?= $r['id_reservation'] ?>">
                                                    <input type="hidden" name="action" value="confirmer">
                                                    <button type="submit" class="btn btn-confirmer"
                                                        onclick="return confirm('Confirmer cette réservation ?')">✔ Confirmer</button>
                                                </form>
                                                <form method="POST" style="display:inline">
                                                    <input type="hidden" name="id_reservation" value="<?= $r['id_reservation'] ?>">
                                                    <input type="hidden" name="action" value="annuler">
                                                    <button type="submit" class="btn btn-annuler"
                                                        onclick="return confirm('Annuler cette réservation ?')">✖ Annuler</button>
                                                </form>
                                            <?php elseif ($statut === 'confirmée'): ?>
                                                <form method="POST" style="display:inline">
                                                    <input type="hidden" name="id_reservation" value="<?= $r['id_reservation'] ?>">
                                                    <input type="hidden" name="action" value="annuler">
                                                    <button type="submit" class="btn btn-annuler"
                                                        onclick="return confirm('Annuler cette réservation confirmée ?')">✖ Annuler</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="btn btn-disabled">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

</main>

<footer>CantineGo &copy; 2026 — Projet SIL2</footer>

</body>
</html>