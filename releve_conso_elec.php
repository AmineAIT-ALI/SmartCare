<?php
require_once 'includes/db.php'; // $connexion = mysqli

$url = "http://192.168.4.1:8080/json.htm?type=devices&used=true&filter=utility";

// Récupération des données depuis Domoticz
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response);

if (!isset($data->result)) {
    echo "Erreur de récupération Domoticz";
    exit;
}

foreach ($data->result as $device) {
    $idx = intval($device->idx);
    $nom = $device->Name;
    $valeur = floatval($device->Usage); // pour les kWh
    $timestamp = date('Y-m-d H:i:s');

    // Récupération de l’IdPeriph dans notre base
    $stmt = $connexion->prepare("SELECT IdPeriph FROM Peripherique WHERE IdxDomoticz = ?");
    $stmt->bind_param("i", $idx);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $idPeriph = $res->fetch_assoc()['IdPeriph'];

        // Insertion dans Releve
        $stmtInsert = $connexion->prepare("INSERT INTO Releve (DateHeure, ValeurR, Type_valeur, IdPeriph) VALUES (?, ?, 'Conso_Elec', ?)");
        $stmtInsert->bind_param("sdi", $timestamp, $valeur, $idPeriph);
        $stmtInsert->execute();

        // Mise à jour Dernier_Statut
        $stmtUpdate = $connexion->prepare("
            INSERT INTO Dernier_Statut (IdPeriph, DerniereValeur, DernierTimestamp)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE DerniereValeur = VALUES(DerniereValeur), DernierTimestamp = VALUES(DernierTimestamp)
        ");
        $stmtUpdate->bind_param("iss", $idPeriph, $device->Data, $timestamp);
        $stmtUpdate->execute();
    }
}
echo "Relevé électrique effectué.";
?>
