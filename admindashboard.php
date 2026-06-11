<?php
session_start();

$_SESSION['admin_nom'] = $_SESSION['admin_nom'] ?? 'Administrateur';

try {
    $db = new PDO('mysql:host=localhost;dbname=cantinego;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

require_once 'Admin.php';
$admin = new Admin($db);

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action_ajouter_repas'])) {
        $image = $_FILES['image_repas'] ?? null;
        $ok = $admin->ajouterRepas(trim($_POST['nom_repas']), $_POST['prix_repas'], $image);
        $flash = $ok
            ? ['type' => 'success', 'msg' => 'Repas ajouté.']
            : ['type' => 'error',   'msg' => "Erreur lors de l'ajout."];
    }

    if (isset($_POST['action_modifier_repas'])) {
        $image = $_FILES['image_repas_edit'] ?? null;
        $ok = $admin->modifierRepas($_POST['id_repas'], trim($_POST['nom_repas_edit']), $_POST['prix_repas_edit'], $image);
        $flash = $ok
            ? ['type' => 'success', 'msg' => 'Repas modifié.']
            : ['type' => 'error',   'msg' => 'Erreur lors de la modification.'];
    }

    if (isset($_POST['action_supprimer_repas'])) {
        $ok = $admin->supprimerRepas($_POST['id_repas']);
        $flash = $ok
            ? ['type' => 'success', 'msg' => 'Repas supprimé.']
            : ['type' => 'error',   'msg' => 'Impossible de supprimer (repas utilisé dans une réservation).'];
    }

    if (isset($_POST['action_menu'])) {
        $ok = $admin->ajouterRepasAuMenu($_POST['date_menu'], $_POST['titre_menu'], $_POST['id_repas']);
        $flash = $ok
            ? ['type' => 'success', 'msg' => 'Menu planifié.']
            : ['type' => 'error',   'msg' => 'Erreur lors de la planification.'];
    }

    if (isset($_POST['action_modifier_menu'])) {
        $ok = $admin->modifierMenu($_POST['id_menu'], $_POST['date_menu_edit'], $_POST['titre_menu_edit'], $_POST['id_repas_edit']);
        $flash = $ok
            ? ['type' => 'success', 'msg' => 'Menu mis à jour.']
            : ['type' => 'error',   'msg' => 'Erreur lors de la mise à jour.'];
    }

    if (isset($_POST['action_supprimer_menu'])) {
        $ok = $admin->supprimerMenu($_POST['id_menu']);
        $flash = $ok
            ? ['type' => 'success', 'msg' => 'Menu supprimé.']
            : ['type' => 'error',   'msg' => 'Erreur lors de la suppression.'];
    }

    if (isset($_POST['update_statut'])) {
        $admin->modifierStatutReservation($_POST['id_reservation'], $_POST['update_statut']);
        header('Location: admindashboard.php');
        exit();
    }

    $_SESSION['flash'] = $flash;
    header('Location: admindashboard.php');
    exit();
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$stats        = $admin->obtenirStatistiques();
$repas        = $admin->listerTousLesRepas();
$menus        = $admin->listerMenus();
$reservations = $admin->voirReservations();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CantineGo — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="adminstyle.css">
</head>
<body>
<div class="layout">


<aside class="sidebar">
    <div class="logo">CantineGo <span class="logo-dot"></span></div>

    <div class="nav-label">Navigation</div>
    <button class="nav-item active" onclick="showTab('overview', this)">
        <span class="nav-dot"></span> Vue d'ensemble
    </button>
    <button class="nav-item" onclick="showTab('repas', this)">
        <span class="nav-dot"></span> Repas
    </button>
    <button class="nav-item" onclick="showTab('menu', this)">
        <span class="nav-dot"></span> Menu
    </button>
    <button class="nav-item" onclick="showTab('reservations', this)">
        <span class="nav-dot"></span> Réservations
    </button>

    <div class="sidebar-footer">
        <div class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_nom']); ?></div>
        <a href="connect.php" class="btn-logout">Déconnexion</a>
    </div>
</aside>


<main class="main">

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo $flash['type']; ?>">
            <?php echo $flash['type'] === 'success' ? '✓' : '!'; ?>
            <?php echo htmlspecialchars($flash['msg']); ?>
        </div>
    <?php endif; ?>

   
    <div id="tab-overview" class="tab-panel active">
        <h1 class="page-title">Vue d'ensemble</h1>

        <div class="stats-row">
            <div class="stat">
                <div class="stat-label">Élèves inscrits</div>
                <div class="stat-value"><?php echo $stats['total_eleves']; ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Réservations</div>
                <div class="stat-value"><?php echo $stats['total_reservations']; ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Repas</div>
                <div class="stat-value"><?php echo $stats['total_repas']; ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Menus planifiés</div>
                <div class="stat-value"><?php echo $stats['total_menus']; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Dernières réservations</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Élève</th><th>Plat</th><th>Statut</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($reservations, 0, 8) as $res): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($res['date_reservation'])); ?></td>
                            <td><?php echo htmlspecialchars($res['nom'] . ' ' . $res['prenom']); ?></td>
                            <td>
                                <?php if ($res['image']): ?>
                                    <img src="uploads/repas/<?php echo htmlspecialchars($res['image']); ?>" class="img-thumb" alt="">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($res['plat']); ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo str_replace([' ', 'é', 'ée'], ['-', 'e', 'ee'], $res['statut']); ?>">
                                    <?php echo $res['statut']; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline-flex;gap:.35rem;">
                                    <input type="hidden" name="id_reservation" value="<?php echo $res['id_reservation']; ?>">
                                    <button type="submit" name="update_statut" value="confirmée" class="btn btn-sm">✓</button>
                                    <button type="submit" name="update_statut" value="annulée" class="btn btn-sm btn-danger">✕</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reservations)): ?>
                            <tr><td colspan="5" class="empty">Aucune réservation.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div id="tab-repas" class="tab-panel">
        <h1 class="page-title">Repas</h1>

        <div class="grid-2">
            <div class="card">
                <div class="card-title">Ajouter un repas</div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nom du plat</label>
                        <input type="text" name="nom_repas" placeholder="Ex : Atassi avec sauce" required>
                    </div>
                    <div class="form-group">
                        <label>Prix (FCFA)</label>
                        <input type="number" step="25" name="prix_repas" placeholder="250" required>
                    </div>
                    <div class="form-group">
                        <label>Photo (JPG, PNG, WEBP — optionnel)</label>
                        <input type="file" name="image_repas" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <button type="submit" name="action_ajouter_repas" class="btn">Enregistrer</button>
                </form>
            </div>
            <div class="card" style="display:flex;flex-direction:column;justify-content:center;gap:.75rem;padding:2rem;">
                <p style="color:var(--muted);font-size:.85rem;line-height:1.7;">
                    Les repas constituent le catalogue disponible.<br>
                    Associez-les ensuite à un menu daté.
                </p>
                <p style="color:var(--border);font-size:.78rem;">Formats acceptés : JPG · PNG · WEBP</p>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Catalogue — <?php echo count($repas); ?> repas</div>
            <?php if (empty($repas)): ?>
                <p class="empty">Aucun repas enregistré.</p>
            <?php else: ?>
            <div class="repas-grid">
                <?php foreach($repas as $r): ?>
                <div class="repas-card">
                    <?php if ($r['image']): ?>
                        <img src="uploads/repas/<?php echo htmlspecialchars($r['image']); ?>"
                             alt="<?php echo htmlspecialchars($r['nom']); ?>">
                    <?php else: ?>
                        <div class="repas-no-img">○</div>
                    <?php endif; ?>
                    <div class="repas-body">
                        <div class="repas-name"><?php echo htmlspecialchars($r['nom']); ?></div>
                        <div class="repas-price"><?php echo number_format($r['prix'], 0, ',', ' '); ?> FCFA</div>
                        <div class="repas-actions">
                            <button class="btn btn-ghost btn-sm"
                                onclick="ouvrirModalRepas(
                                    <?php echo $r['id_repas']; ?>,
                                    '<?php echo addslashes(htmlspecialchars($r['nom'])); ?>',
                                    <?php echo $r['prix']; ?>,
                                    '<?php echo $r['image'] ?? ''; ?>'
                                )">Modifier</button>
                            <form method="POST" onsubmit="return confirm('Supprimer ce repas ?')">
                                <input type="hidden" name="id_repas" value="<?php echo $r['id_repas']; ?>">
                                <button type="submit" name="action_supprimer_repas" class="btn btn-danger btn-sm">Suppr.</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div id="tab-menu" class="tab-panel">
        <h1 class="page-title">Menu</h1>

        <div class="card" style="max-width:460px;">
            <div class="card-title">Planifier un menu</div>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date_menu" required>
                </div>
                <div class="form-group">
                    <label>Label d'affichage</label>
                    <input type="text" name="titre_menu" value="Plat Principal" required>
                </div>
                <div class="form-group">
                    <label>Repas associé</label>
                    <select name="id_repas" required>
                        <option value="">— Choisir —</option>
                        <?php foreach($repas as $r): ?>
                            <option value="<?php echo $r['id_repas']; ?>">
                                <?php echo htmlspecialchars($r['nom']); ?> · <?php echo number_format($r['prix'],0,',',' '); ?> FCFA
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="action_menu" class="btn">Ajouter au menu</button>
            </form>
        </div>

        <div class="card">
            <div class="card-title">Menus planifiés — <?php echo count($menus); ?></div>
            <?php if (empty($menus)): ?>
                <p class="empty">Aucun menu planifié.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Label</th><th>Repas</th><th>Prix</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($menus as $m): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($m['date_menu'])); ?></td>
                            <td><?php echo htmlspecialchars($m['plat_principal']); ?></td>
                            <td>
                                <?php if ($m['image']): ?>
                                    <img src="uploads/repas/<?php echo htmlspecialchars($m['image']); ?>" class="img-thumb" alt="">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($m['nom_repas']); ?>
                            </td>
                            <td><?php echo number_format($m['prix'],0,',',' '); ?> FCFA</td>
                            <td style="display:flex;gap:.35rem;flex-wrap:wrap;">
                                <button class="btn btn-ghost btn-sm"
                                    onclick="ouvrirModalMenu(
                                        <?php echo $m['id_menu']; ?>,
                                        '<?php echo $m['date_menu']; ?>',
                                        '<?php echo addslashes(htmlspecialchars($m['plat_principal'])); ?>',
                                        <?php echo $m['id_repas']; ?>
                                    )">Modifier</button>
                                <form method="POST" onsubmit="return confirm('Supprimer ce menu ?')">
                                    <input type="hidden" name="id_menu" value="<?php echo $m['id_menu']; ?>">
                                    <button type="submit" name="action_supprimer_menu" class="btn btn-danger btn-sm">Suppr.</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div id="tab-reservations" class="tab-panel">
        <h1 class="page-title">Réservations</h1>

        <div class="card">
            <div class="card-title"><?php echo count($reservations); ?> réservation<?php echo count($reservations) !== 1 ? 's' : ''; ?></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Élève</th><th>Plat</th><th>Prix</th><th>Statut</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($reservations as $res): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($res['date_reservation'])); ?></td>
                            <td><?php echo htmlspecialchars($res['nom'] . ' ' . $res['prenom']); ?></td>
                            <td>
                                <?php if ($res['image']): ?>
                                    <img src="uploads/repas/<?php echo htmlspecialchars($res['image']); ?>" class="img-thumb" alt="">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($res['plat']); ?>
                            </td>
                            <td><?php echo number_format($res['prix'],0,',',' '); ?> FCFA</td>
                            <td>
                                <span class="badge badge-<?php echo str_replace([' ', 'é', 'ée'], ['-', 'e', 'ee'], $res['statut']); ?>">
                                    <?php echo $res['statut']; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline-flex;gap:.35rem;">
                                    <input type="hidden" name="id_reservation" value="<?php echo $res['id_reservation']; ?>">
                                    <button type="submit" name="update_statut" value="confirmée" class="btn btn-sm">✓</button>
                                    <button type="submit" name="update_statut" value="annulée" class="btn btn-sm btn-danger">✕</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reservations)): ?>
                            <tr><td colspan="6" class="empty">Aucune réservation.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>
</div>


<div class="modal-overlay" id="modal-repas">
    <div class="modal">
        <div class="modal-title">Modifier le repas</div>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_repas" id="edit-id-repas">
            <div class="form-group">
                <label>Nom du plat</label>
                <input type="text" name="nom_repas_edit" id="edit-nom-repas" required>
            </div>
            <div class="form-group">
                <label>Prix (FCFA)</label>
                <input type="number" step="25" name="prix_repas_edit" id="edit-prix-repas" required>
            </div>
            <div class="form-group">
                <label>Nouvelle photo (vide = conserver l'actuelle)</label>
                <div id="edit-img-preview" style="margin-bottom:.5rem;"></div>
                <input type="file" name="image_repas_edit" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="fermerModals()">Annuler</button>
                <button type="submit" name="action_modifier_repas" class="btn">Enregistrer</button>
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="modal-menu">
    <div class="modal">
        <div class="modal-title">Modifier le menu</div>
        <form action="" method="POST">
            <input type="hidden" name="id_menu" id="edit-id-menu">
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date_menu_edit" id="edit-date-menu" required>
            </div>
            <div class="form-group">
                <label>Label d'affichage</label>
                <input type="text" name="titre_menu_edit" id="edit-titre-menu" required>
            </div>
            <div class="form-group">
                <label>Repas associé</label>
                <select name="id_repas_edit" id="edit-idrepas-menu" required>
                    <?php foreach($repas as $r): ?>
                        <option value="<?php echo $r['id_repas']; ?>">
                            <?php echo htmlspecialchars($r['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="fermerModals()">Annuler</button>
                <button type="submit" name="action_modifier_menu" class="btn">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

function fermerModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
}

function ouvrirModalRepas(id, nom, prix, image) {
    document.getElementById('edit-id-repas').value   = id;
    document.getElementById('edit-nom-repas').value  = nom;
    document.getElementById('edit-prix-repas').value = prix;
    const prev = document.getElementById('edit-img-preview');
    prev.innerHTML = image
        ? `<img src="uploads/repas/${image}" style="height:48px;border-radius:4px;" alt="">`
        : `<span style="font-size:.75rem;color:var(--muted);">Aucune image actuelle</span>`;
    document.getElementById('modal-repas').classList.add('open');
}

function ouvrirModalMenu(id, date, titre, idRepas) {
    document.getElementById('edit-id-menu').value      = id;
    document.getElementById('edit-date-menu').value    = date;
    document.getElementById('edit-titre-menu').value   = titre;
    document.getElementById('edit-idrepas-menu').value = idRepas;
    document.getElementById('modal-menu').classList.add('open');
}

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) fermerModals(); });
});
</script>
</body>
</html>