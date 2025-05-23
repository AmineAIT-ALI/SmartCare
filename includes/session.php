<?php
// Démarre la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie que l'utilisateur est connecté.
 * Redirige vers la page de connexion si ce n'est pas le cas.
 */
function verifierConnexion() {
    if (!isset($_SESSION['role'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Vérifie que l'utilisateur connecté a le rôle requis.
 * Redirige vers le tableau de bord ou affiche un message en cas d'accès refusé.
 *
 * @param string $role_requis Rôle attendu : 'patient', 'aide_soignant', 'membre_famille'
 */
function verifierRole(string $role_requis) {
    verifierConnexion();

    if ($_SESSION['role'] !== $role_requis) {
        echo "<h2>Accès refusé</h2><p>Cette page est réservée aux utilisateurs de type : <strong>$role_requis</strong>.</p>";
        exit;
    }
}

