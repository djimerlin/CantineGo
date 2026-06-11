<?php
$hote = 'localhost';
$base = 'cantinego';
$user = 'root';
$mdp  = '';

try {
    $pdo = new PDO("mysql:host=$hote;dbname=$base;charset=utf8", $user, $mdp);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}