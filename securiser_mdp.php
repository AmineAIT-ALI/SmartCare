<?php
require_once 'includes/db.php';

// Tables et colonnes à sécuriser
$tables = [
    'Patient' => 'P',
    'Membre_Famille' => 'M',
    'Aide_Soignant' => 'A'
];

foreach ($tables as $table => $alias) {
    $sql = "SELECT Id$alias, mdp$alias FROM $table";
    $result = $connexion->query($sql);

    while ($row = $result->fetch_assoc()) {
        $id = $row["Id$alias"];
        $mdp = $row["mdp$alias"];

        // Ne pas re-hasher un mot de passe déjà hashé
        if (!str_starts_with($mdp, '$2y$')) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);

            $stmt = $connexion->prepare("UPDATE $table SET mdp$alias = ? WHERE Id$alias = ?");
            $stmt->bind_param("si", $hash, $id);
            $stmt->execute();
        }
    }
}

echo "Tous les mots de passe ont été sécurisés avec password_hash().";
?>
