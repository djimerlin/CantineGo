<?php
class Admin {
    private $db;
    public $id_admin;
    public $nom;
    public $email;

    public function __construct($database_connection) {
        $this->db = $database_connection;
    }

    

    public function login($email, $password) {
        $stmt = $this->db->prepare(
            "SELECT id_admin, nom, email, mot_de_passe FROM admin WHERE email = :email LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adminData && password_verify($password, $adminData['mot_de_passe'])) {
            $this->id_admin       = $adminData['id_admin'];
            $this->nom            = $adminData['nom'];
            $this->email          = $adminData['email'];
            $_SESSION['admin_id'] = $this->id_admin;
            $_SESSION['admin_nom']= $this->nom;
            return true;
        }
        return false;
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        session_unset();
        session_destroy();
    }

    
    public function ajouterRepas($nom, $prix, $fichierImage = null) {
        $nomImage = $this->_traiterImage($fichierImage);
        $stmt = $this->db->prepare(
            "INSERT INTO repas (nom, prix, image) VALUES (:nom, :prix, :image)"
        );
        $ok = $stmt->execute([':nom' => $nom, ':prix' => $prix, ':image' => $nomImage]);
        return $ok ? $this->db->lastInsertId() : false;
    }

    
    public function modifierRepas($id_repas, $nom, $prix, $fichierImage = null) {
        // Récupère l'ancienne image pour éventuellement la supprimer
        $ancienne = $this->_getImageRepas($id_repas);
        $nomImage = $this->_traiterImage($fichierImage);

        if ($nomImage) {
            // Supprime l'ancien fichier physique si existant
            if ($ancienne && file_exists('uploads/repas/' . $ancienne)) {
                unlink('uploads/repas/' . $ancienne);
            }
        } else {
            $nomImage = $ancienne; // Conserve l'image existante
        }

        $stmt = $this->db->prepare(
            "UPDATE repas SET nom = :nom, prix = :prix, image = :image WHERE id_repas = :id"
        );
        return $stmt->execute([':nom' => $nom, ':prix' => $prix, ':image' => $nomImage, ':id' => $id_repas]);
    }

   
    public function supprimerRepas($id_repas) {
        $image = $this->_getImageRepas($id_repas);
        $stmt  = $this->db->prepare("DELETE FROM repas WHERE id_repas = :id");
        $ok    = $stmt->execute([':id' => $id_repas]);
        if ($ok && $image && file_exists('uploads/repas/' . $image)) {
            unlink('uploads/repas/' . $image);
        }
        return $ok;
    }

    public function listerTousLesRepas() {
        return $this->db->query("SELECT * FROM repas ORDER BY nom ASC")
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRepas($id_repas) {
        $stmt = $this->db->prepare("SELECT * FROM repas WHERE id_repas = :id");
        $stmt->execute([':id' => $id_repas]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    

    public function ajouterRepasAuMenu($date_menu, $plat_principal, $id_repas) {
        $stmt = $this->db->prepare(
            "INSERT INTO menu (date_menu, plat_principal, id_repas)
             VALUES (:date_menu, :plat_principal, :id_repas)"
        );
        return $stmt->execute([
            ':date_menu'      => $date_menu,
            ':plat_principal' => $plat_principal,
            ':id_repas'       => $id_repas,
        ]);
    }

    public function modifierMenu($id_menu, $date_menu, $plat_principal, $id_repas) {
        $stmt = $this->db->prepare(
            "UPDATE menu
             SET date_menu = :date_menu, plat_principal = :plat_principal, id_repas = :id_repas
             WHERE id_menu = :id_menu"
        );
        return $stmt->execute([
            ':date_menu'      => $date_menu,
            ':plat_principal' => $plat_principal,
            ':id_repas'       => $id_repas,
            ':id_menu'        => $id_menu,
        ]);
    }

    public function supprimerMenu($id_menu) {
        $stmt = $this->db->prepare("DELETE FROM menu WHERE id_menu = :id");
        return $stmt->execute([':id' => $id_menu]);
    }

    public function listerMenus() {
        return $this->db->query(
            "SELECT m.id_menu, m.date_menu, m.plat_principal,
                    r.id_repas, r.nom AS nom_repas, r.prix, r.image
             FROM menu m
             JOIN repas r ON m.id_repas = r.id_repas
             ORDER BY m.date_menu DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMenu($id_menu) {
        $stmt = $this->db->prepare(
            "SELECT m.*, r.nom AS nom_repas, r.prix, r.image
             FROM menu m JOIN repas r ON m.id_repas = r.id_repas
             WHERE m.id_menu = :id"
        );
        $stmt->execute([':id' => $id_menu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function voirReservations() {
        return $this->db->query(
            "SELECT r.id_reservation, r.date_reservation, r.statut,
                    e.nom, e.prenom,
                    rep.nom AS plat, rep.prix, rep.image
             FROM reservation r
             JOIN eleve   e   ON r.id_eleve = e.id_eleve
             JOIN repas   rep ON r.id_repas = rep.id_repas
             ORDER BY r.date_reservation DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function modifierStatutReservation($id_reservation, $nouveau_statut) {
        $valides = ['en attente', 'confirmée', 'annulée'];
        if (!in_array($nouveau_statut, $valides)) { return false; }
        $stmt = $this->db->prepare(
            "UPDATE reservation SET statut = :statut WHERE id_reservation = :id"
        );
        return $stmt->execute([':statut' => $nouveau_statut, ':id' => $id_reservation]);
    }

    
    public function obtenirStatistiques() {
        $eleves       = $this->db->query("SELECT COUNT(*) AS total FROM eleve")->fetch(PDO::FETCH_ASSOC)['total'];
        $reservations = $this->db->query("SELECT COUNT(*) AS total FROM reservation")->fetch(PDO::FETCH_ASSOC)['total'];
        $menus        = $this->db->query("SELECT COUNT(*) AS total FROM menu")->fetch(PDO::FETCH_ASSOC)['total'];
        $repas        = $this->db->query("SELECT COUNT(*) AS total FROM repas")->fetch(PDO::FETCH_ASSOC)['total'];
        return [
            'total_eleves'       => $eleves,
            'total_reservations' => $reservations,
            'total_menus'        => $menus,
            'total_repas'        => $repas,
        ];
    }

    

    private function _getImageRepas($id_repas) {
        $stmt = $this->db->prepare("SELECT image FROM repas WHERE id_repas = :id");
        $stmt->execute([':id' => $id_repas]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['image'] : null;
    }

    
    private function _traiterImage($fichier) {
        if (empty($fichier) || $fichier['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $typesAutorises = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($fichier['type'], $typesAutorises)) {
            return null;
        }

        $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
        $nomFichier = uniqid('repas_', true) . '.' . strtolower($extension);
        $dossier    = 'uploads/repas/';

        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }

        if (move_uploaded_file($fichier['tmp_name'], $dossier . $nomFichier)) {
            return $nomFichier;
        }
        return null;
    }
}