<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('patient');

$idU = $_SESSION['idU'] ?? 0;
if ($idU <= 0) {
    header("Location: prise_du_jour.php?success=0");
    exit;
}

// Récupérer IdP
$stmt = $connexion->prepare("SELECT IdP FROM Patient WHERE IdU = ?");
$stmt->bind_param("i", $idU);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$idP = $row['IdP'] ?? 0;

$idPrise = $_POST['idPrise'] ?? null;
if ($idP > 0 && $idPrise !== null && ctype_digit($idPrise)) {
    $idPrise = intval($idPrise);

    // Vérifie que la prise appartient au patient
    $check = $connexion->prepare("SELECT IdPrise FROM Prise_Medicament WHERE IdPrise = ? AND IdP = ?");
    $check->bind_param("ii", $idPrise, $idP);
    $check->execute();
    $res = $check->get_result();

    if ($res && $res->num_rows > 0) {
        // 🔍 Vérifie état du capteur porte
        $idx_porte = 16;
        $url = "http://localhost:8080/json.htm?type=devices&rid=$idx_porte";
        $data = json_decode(file_get_contents($url), true)['result'][0];
        $etat_porte = $data['Data'];
        $time_porte = strtotime($data['LastUpdate']);

        if ($etat_porte === "On" && (time() - $time_porte < 30)) {
            // ✅ Prise validée
            $stmt = $connexion->prepare("UPDATE Prise_Medicament SET Confirme = 1 WHERE IdPrise = ?");
            $stmt->bind_param("i", $idPrise);
            $stmt->execute();
            $stmt->close();

            // Feedback LCD (optionnel)
            system('/home/pi/piface/libpifacecad/pifacecad write "Prise OK"');
            system('/home/pi/piface/libpifacecad/pifacecad backlight on');
            sleep(2);
            system('/home/pi/piface/libpifacecad/pifacecad backlight off');

            header("Location: prise_du_jour.php?success=1");
            exit;
        } else {
            // 🚨 Alerte : porte non ouverte
            $stmt = $connexion->prepare("INSERT INTO Alerte (TypeAlerte, DateAlerte, HeureA) VALUES ('Prise sans ouverture', CURDATE(), CURTIME())");
            $stmt->execute();

            system('/home/pi/piface/libpifacecad/pifacecad write "⚠ Porte fermée"');
            system('/home/pi/piface/libpifacecad/pifacecad backlight on');
            sleep(2);
            system('/home/pi/piface/libpifacecad/pifacecad backlight off');
        }
    }
}

header("Location: prise_du_jour.php?success=0");
exit;
?>
