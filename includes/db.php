<?php
$host = 'localhost';
$user = 'smartcareuser';
$password = 'test';
$dbname = 'smartcare';

$connexion = new mysqli($host, $user, $password, $dbname);

if ($connexion->connect_error) {
    die("Erreur de connexion : " . $connexion->connect_error);
}
?>
