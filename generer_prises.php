<?php
require_once 'includes/db.php';

$date_actuelle = date('Y-m-d');

// Étape 1 : récupérer les poses de médicaments actives
$query = "
    SELECT po.IdMed, po.IdA, po.DateP, po.HeureP, po.QuantitéP, pr.IdP, ps.Fréquence
    FROM pose po
    JOIN prescris ps ON ps.IdMed = po.IdMed
    JOIN Prescription pr ON ps.IdPres = pr.IdPres
    WHERE po.DateP = ?
";
$stmt = $connexion->prepare($query);
$stmt->bind_param("s", $date_actuelle);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $idMed = $row['IdMed'];
    $idP = $row['IdP'];
    $heure_depart = strtotime($row['HeureP']);
    $frequence = intval($row['Fréquence']);
    $quantite = intval($row['QuantitéP']);

    // Étape 2 : créer une prise pour chaque unité (en tenant compte de la fréquence)
    for ($i = 0; $i < $quantite; $i++) {
        $heure_prise = date('H:i:s', $heure_depart + $i * 3600 * $frequence);

        // Créer la prise
        $connexion->query("INSERT INTO Prise_Medicament (HeurePrise, Confirme) VALUES ('$heure_prise', 0)");
        $idPrise = $connexion->insert_id;

        // Associer au médicament
        $stmt2 = $connexion->prepare("INSERT INTO concerne (IdPrise, IdMed) VALUES (?, ?)");
        $stmt2->bind_param("ii", $idPrise, $idMed);
        $stmt2->execute();
    }
}

echo "Prises générées pour les poses de $date_actuelle";
?>
