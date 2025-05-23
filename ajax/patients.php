<?php
require_once '../includes/db.php';

$term = $_GET['term'] ?? '';
$stmt = $connexion->prepare("SELECT NomP FROM Patient WHERE NomP LIKE CONCAT(?, '%') LIMIT 10");
$stmt->bind_param("s", $term);
$stmt->execute();
$res = $stmt->get_result();

$suggestions = [];
while ($row = $res->fetch_assoc()) {
  $suggestions[] = $row['NomP'];
}

echo json_encode($suggestions);
?>
