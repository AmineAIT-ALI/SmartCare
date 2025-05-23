<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

// Définition des rôles autorisés
$roles = [
    'patient' => ['table' => 'Patient', 'id' => 'IdP'],
    'aide_soignant' => ['table' => 'Aide_Soignant', 'id' => 'IdA'],
    'membre_famille' => ['table' => 'Membre_Famille', 'id' => 'IdM']
];

$role = $_GET['role'] ?? '';
$id = intval($_GET['id'] ?? 0);
$message = '';

if (!isset($roles[$role]) || $id <= 0) {
    die("<h2>Requête invalide</h2><p>Paramètres manquants ou incorrects.</p>");
}

$table = $roles[$role]['table'];
$idField = $roles[$role]['id'];

// Récupération de l'IdU
$stmt = $connexion->prepare("SELECT IdU FROM $table WHERE $idField = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

if ($data && isset($data['IdU'])) {
    $idU = $data['IdU'];

    // Suppression de la ligne dans la table spécifique
    $delSpec = $connexion->prepare("DELETE FROM $table WHERE $idField = ?");
    $delSpec->bind_param("i", $id);
    if ($delSpec->execute()) {
        // Suppression dans Utilisateur (cascade depuis Patient/Membre/Aide_Soignant suffit si bien configuré)
        $delU = $connexion->prepare("DELETE FROM Utilisateur WHERE IdU = ?");
        $delU->bind_param("i", $idU);
        if ($delU->execute()) {
            $message = "Utilisateur supprimé avec succès.";
        } else {
            $message = "Erreur Utilisateur : " . $delU->error;
        }
    } else {
        $message = "Erreur $role : " . $delSpec->error;
    }
} else {
    $message = "Utilisateur introuvable.";
}

header("Location: liste_utilisateurs.php?msg=" . urlencode($message));
exit;
?>
