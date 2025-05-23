<?php
// URL de l’API Domoticz pour filtrer les objets de type "utility"
$url = "http://192.168.4.1:8080/json.htm?type=devices&used=true&filter=utility";

// Initialisation de cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec($ch);
curl_close($ch);

// Analyse de la réponse JSON
$data = json_decode($output);

// Vérification et affichage
if ($data && isset($data->result)) {
    echo "<h1>Consommation électrique actuelle</h1>";
    echo "<ul>";
    foreach ($data->result as $device) {
        echo "<li><strong>{$device->Name}</strong> : {$device->Data}</li>";
    }
    echo "</ul>";
} else {
    echo "Erreur : Impossible de récupérer les données.";
}
?>
