<?php
// prise_on_off.php
require_once 'includes/db.php'; // Connexion $connexion

// Paramètres GET attendus
$idx = isset($_GET['idx']) ? intval($_GET['idx']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Vérification des paramètres
if (!$idx || !in_array($action, ['On', 'Off'])) {
    echo "Paramètres invalides.";
    exit;
}

// Envoi de la commande à Domoticz
$url = "http://192.168.4.1:8080/json.htm?type=command&param=switchlight&idx=$idx&switchcmd=$action";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec($ch);
curl_close($ch);

// Traitement de la réponse Domoticz
$response = json_decode($output);

if ($response && $response->status === "OK") {
    echo "Commande $action envoyée avec succès à la prise $idx.";

    // Mise à jour en base si le périphérique est connu
    $stmt = $connexion->prepare("SELECT IdPeriph FROM Peripherique WHERE IdxDomoticz = ?");
    $stmt->bind_param("i", $idx);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $idPeriph = $res->fetch_assoc()['IdPeriph'];
        $timestamp = date('Y-m-d H:i:s');

        $stmtUpdate = $connexion->prepare("
            INSERT INTO Dernier_Statut (IdPeriph, DerniereValeur, DernierTimestamp)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE DerniereValeur = VALUES(DerniereValeur), DernierTimestamp = VALUES(DernierTimestamp)
        ");
        $stmtUpdate->bind_param("iss", $idPeriph, $action, $timestamp);
        $stmtUpdate->execute();
    }

} else {
    echo "Erreur lors de l'envoi de la commande à Domoticz.";
}
?>
