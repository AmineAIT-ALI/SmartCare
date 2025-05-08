<?php

function activerPrise($idx) {
    $url = "http://192.168.4.1:8080/json.htm?type=command&param=switchlight&idx=$idx&switchcmd=On";
    return appelerDomoticz($url);
}

function lireTemperature($idx) {
    $url = "http://192.168.4.1:8080/json.htm?type=devices&filter=temperature&used=true";
    $reponse = appelerDomoticz($url);
    foreach ($reponse->result as $capteur) {
        if ($capteur->idx == $idx) {
            return $capteur->Temp;
        }
    }
    return null;
}

function appelerDomoticz($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return json_decode($output);
}
?>
