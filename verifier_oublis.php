<?php
require_once 'includes/db.php';

date_default_timezone_set('Europe/Paris');
$now = new DateTime();

// Étape 1 : récupérer les prises non confirmées depuis plus de 40 minutes
$query = "
    SELECT pm.IdPrise, pm.HeurePrise, c.IdMed, pr.IdP
    FROM Prise_Medicament pm
    JOIN concerne c ON pm.IdPrise = c.IdPrise
    JOIN prescris ps ON ps.IdMed = c.IdMed
    JOIN Prescription pr ON ps.IdPres = pr.IdPres
    WHERE pm.Confirme = 0
";
$result = $connexion->query($query);

while ($row = $result->fetch_assoc()) {
    $heurePrise = new DateTime($row['HeurePrise']);
    $diff = $now->getTimestamp() - $heurePrise->getTimestamp();

    if ($diff > 2400 && $diff < 3600) { // entre 40min et 1h pour éviter doublons
        // Vérifie si une alerte existe déjà pour cette prise
        $check = $connexion->prepare("SELECT COUNT(*) FROM Alerte WHERE TypeA = 'Oubli de prise' AND DateA = CURDATE() AND HeureA = ?");
        $heure_str = $heurePrise->format('H:i:s');
        $check->bind_param("s", $heure_str);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count == 0) {
            // Crée l’alerte
            $stmt = $connexion->prepare("INSERT INTO Alerte (TypeA, DateA, HeureA) VALUES ('Oubli de prise', CURDATE(), ?)");
            $stmt->bind_param("s", $heure_str);
            $stmt->execute();
            $idAlerte = $connexion->insert_id;

            // Associe cette alerte à tous les proches (via recevoir)
            $idP = $row['IdP'];
            $res = $connexion->prepare("SELECT IdM FROM relié WHERE IdP = ?");
            $res->bind_param("i", $idP);
            $res->execute();
            $rel = $res->get_result();
            while ($membre = $rel->fetch_assoc()) {
                $insert = $connexion->prepare("INSERT INTO recevoir (IdM, IdAlerte) VALUES (?, ?)");
                $insert->bind_param("ii", $membre['IdM'], $idAlerte);
                $insert->execute();
            }

            echo "Alerte générée pour un oubli de prise à $heure_str (IdP = $idP)<br>";
        }
    }
}
?>
