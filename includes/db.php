<?php
$host = 'localhost';
$dbname = 'armoire_connecte';
$username = 'smartcare';
$password = 'motdepasse';

$connexion = new mysqli($host, $username, $password, $dbname);

// Vérifie la connexion
if ($connexion->connect_error) {
    die("Connexion échouée : " . $connexion->connect_error);
}

// Force l'encodage UTF-8 pour éviter les problèmes avec les accents
$connexion->set_charset("utf8");
?>


