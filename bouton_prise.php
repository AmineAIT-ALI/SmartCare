<?php
$conn = mysqli_connect("localhost", "phpmyadmin", "tp", "armoire_connecte");
if (!$conn) die("Erreur MySQL");

// Paramètres
$idx_porte = 16; // à adapter
$IdP = 1;        // à adapter (id du patient concerné)

// Requête Domoticz pour le capteur de porte
$url = "http://localhost:8080/json.htm?type=devices&rid=$idx_porte";
$data = json_decode(file_get_contents($url), true)['result'][0];
$etat_porte = $data['Data'];
$time_porte = strtotime($data['LastUpdate']);
$now = time();

// Si porte ouverte < 30 secondes
if ($etat_porte === "On" && ($now - $time_porte < 30)) {
    $sql = "
        UPDATE Prise_Medicament 
        SET Confirme = 1 
        WHERE IdP = $IdP 
        AND DATE(HeurePrise) = CURDATE() 
        AND Confirme = 0 
        ORDER BY HeurePrise DESC 
        LIMIT 1
    ";
    if (mysqli_query($conn, $sql)) {
        $msg = "✅ Prise confirmée.";
        system('/home/pi/piface/libpifacecad/pifacecad write "Prise OK"');
    } else {
        $msg = "❌ Erreur SQL.";
    }
} else {
    // Alerte
    mysqli_query($conn, "INSERT INTO Alerte (TypeAlerte, DateAlerte, HeureA, IdA) VALUES ('Prise sans ouverture', CURDATE(), CURTIME(), NULL)");
    $msg = "⚠️ Porte non ouverte.";
    system('/home/pi/piface/libpifacecad/pifacecad write "⚠️ Porte fermée"');
}

// Feedback sur LCD
system('/home/pi/piface/libpifacecad/pifacecad backlight on');
sleep(2);
system('/home/pi/piface/libpifacecad/pifacecad backlight off');

mysqli_close($conn);
echo $msg;
?>
