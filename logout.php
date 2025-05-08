<?php
session_start();
session_unset();
session_destroy();

// Redirection vers la page d'accueil ou de connexion
header("Location: index.php");
exit;
?>
