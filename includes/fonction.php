<?php
session_start();

function estConnecteEleve() {
    return isset($_SESSION['eleve_id']);
}

function estConnecteAdmin() {
    return isset($_SESSION['admin_id']);
}

function rediriger($url) {
    header("Location: $url");
    exit;
}

function escape($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['','Janvier','Février','Mars','Avril','Mai','Juin',
              'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    $ts = strtotime($date);
    return $jours[date('w',$ts)].' '.date('d',$ts).' '.$mois[(int)date('m',$ts)];
}

function formatPrix($prix) {
    return number_format($prix, 0, ',', ' ') . ' FCFA';
}