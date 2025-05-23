<?php
session_start();

// Empêcher le cache du navigateur sur la page précédente
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false); // pour IE
header("Pragma: no-cache");

// Destruction de la session
session_unset();
session_destroy();

// Redirection vers la page d'accueil ou de connexion
header("Location: index.php");
exit;
?>
