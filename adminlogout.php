<?php
session_start();

// On vide toutes les variables de session
$_SESSION = array();

// On détruit complètement la session
session_destroy();

// On redirige l'administrateur vers la page de connexion
header('Location: connect.php');
exit;