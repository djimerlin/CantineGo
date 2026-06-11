CREATE DATABASE IF NOT EXISTS cantinego;
USE cantinego;


//nouvel ajout
ALTER TABLE repas
    ADD COLUMN image VARCHAR(255) NULL DEFAULT NULL AFTER prix;

ALTER TABLE admin 
ADD COLUMN cle_unique VARCHAR(50) NOT NULL UNIQUE AFTER id_admin;

INSERT INTO admin (cle_unique, nom, email, mot_de_passe) 
VALUES (
  'CG-8942', 
  'Administrateur Principal', 
  'admin@cantinego.com', 
  'admin1'
);
//noubel ajout 
CREATE TABLE eleve (
  id_eleve INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL, 
  filiere VARCHAR(50)                 
);


CREATE TABLE admin (
  id_admin INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL
);


CREATE TABLE repas (
  id_repas INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prix DECIMAL(5,2) NOT NULL -- Le prix migre ici conformément au schéma
);



CREATE TABLE menu (
  id_menu INT AUTO_INCREMENT PRIMARY KEY,
  date_menu DATE NOT NULL,
  plat_principal VARCHAR(200) NOT NULL, -- Correspond à Plat_principa
  id_repas INT NOT NULL,
  FOREIGN KEY (id_repas) REFERENCES repas(id_repas) ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE reservation (
  id_reservation INT AUTO_INCREMENT PRIMARY KEY,
  date_reservation DATETIME DEFAULT NOW(),
  statut ENUM('en attente', 'confirmée', 'annulée') DEFAULT 'en attente',
  id_eleve INT NOT NULL,
  id_repas INT NOT NULL, 
  FOREIGN KEY (id_eleve) REFERENCES eleve(id_eleve) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_repas) REFERENCES repas(id_repas) ON DELETE CASCADE ON UPDATE CASCADE
);