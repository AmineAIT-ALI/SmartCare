<?php
require_once '../includes/db.php';

$term = $_GET['term'] ?? '';
$stmt = $connexion->prepare("SELECT NomMed FROM Medicament WHERE NomMed LIKE CONCAT(?, '%') LIMIT 10");
$stmt->bind_param("s", $term);
$stmt->execute();
$res = $stmt->get_result();

$suggestions = [];
while ($row = $res->fetch_assoc()) {
  $suggestions[] = $row['NomMed'];
}

echo json_encode($suggestions);
?>
