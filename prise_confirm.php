<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Vérifie que seul un patient peut confirmer une prise
verifierRole('patient');

$idPrise = $_POST['idPrise'] ?? null;

if ($idPrise && is_numeric($idPrise)) {
    $stmt = $connexion->prepare("UPDATE Prise_Medicament SET Confirme = 1 WHERE IdPrise = ?");
    $stmt->bind_param("i", $idPrise);
    $stmt->execute();
}

// Redirection vers le tableau de bord ou une autre page
header("Location: index.php?page=dashboard");
exit;
?>
