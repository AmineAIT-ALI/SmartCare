<?php
session_start();

/**
 * Vérifie que l'utilisateur est connecté avec le rôle exact requis.
 *
 * @param string $role_requis Ex: 'patient', 'aide_soignant', 'membre_famille'
 */
function verifierRole($role_requis) {
    if (!isset($_SESSION['role'])) {
        header('Location: login.php');
        exit;
    }

    if ($_SESSION['role'] !== $role_requis) {
        echo "<h2>Accès refusé</h2><p>Cette page est réservée aux utilisateurs de type : <strong>$role_requis</strong>.</p>";
        exit;
    }
}
?>
