<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

$confirmation = '';
$erreur = '';
$etatCompartiments = '';
$tempMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['patient'])) {
        $nomPatient = $_POST['patient'] ?? '';
        $nomMedicament = $_POST['medic'] ?? '';
        $heure = $_POST['horaire'] ?? '';

        if ($nomPatient && $nomMedicament && $heure) {
            $stmt = $connexion->prepare("SELECT IdP FROM Patient WHERE NomP = ?");
            $stmt->bind_param("s", $nomPatient);
            $stmt->execute();
            $res = $stmt->get_result();
            $patient = $res->fetch_assoc();

            if ($patient) {
                $idP = $patient['IdP'];

                $stmt = $connexion->prepare("SELECT IdMed FROM Medicament WHERE NomMed = ?");
                $stmt->bind_param("s", $nomMedicament);
                $stmt->execute();
                $res = $stmt->get_result();
                $med = $res->fetch_assoc();

                $idMed = $med ? $med['IdMed'] : null;

                if (!$idMed) {
                    $stmt = $connexion->prepare("INSERT INTO Medicament (NomMed) VALUES (?)");
                    $stmt->bind_param("s", $nomMedicament);
                    $stmt->execute();
                    $idMed = $stmt->insert_id;
                }

                $stmt = $connexion->prepare("INSERT INTO Prise_Medicament (HeurePrise, IdP) VALUES (?, ?)");
                $stmt->bind_param("si", $heure, $idP);
                $stmt->execute();
                $idPrise = $stmt->insert_id;

                $stmt = $connexion->prepare("INSERT INTO concerne (IdMed, IdPrise) VALUES (?, ?)");
                $stmt->bind_param("ii", $idMed, $idPrise);
                $stmt->execute();

                $confirmation = "Médicament ajouté pour $nomPatient à $heure.";
            } else {
                $erreur = "Patient '$nomPatient' introuvable.";
            }
        } else {
            $erreur = "Veuillez remplir tous les champs.";
        }
    } elseif (isset($_POST['temperature'])) {
        $temperature = (int) $_POST['temperature'];
        if ($temperature >= 2 && $temperature <= 25) {
            $tempMessage = "Température réglée à <strong>$temperature°C</strong> (simulation).";
        } else {
            $tempMessage = "Température hors limites (2°C à 25°C).";
        }
    } elseif (isset($_POST['compartiment'])) {
        if ($_POST['compartiment'] === 'verrouiller') {
            $etatCompartiments = "Tous les compartiments ont été verrouillés (simulation).";
        } elseif ($_POST['compartiment'] === 'deverrouiller') {
            $etatCompartiments = "Tous les compartiments ont été déverrouillés (simulation).";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Administration - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <li><a href="dashboard.php">Tableau de bord</a></li>
      <li><a href="historique_soignant.php">Historique</a></li>
      <li><a href="alertes.php">Alertes</a></li>
      <li><a href="admin.php" class="active">Administration</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="card fade-in">
    <h2>Espace Administration</h2>
    <p>Gérez les médicaments, la température et la sécurité de l’armoire connectée.</p>

    <?php if ($confirmation): ?><p class="ok"><?= $confirmation ?></p><?php endif; ?>
    <?php if ($erreur): ?><p class="alert"><?= $erreur ?></p><?php endif; ?>
    <?php if ($tempMessage): ?><p class="info"><?= $tempMessage ?></p><?php endif; ?>
    <?php if ($etatCompartiments): ?><p class="info"><?= $etatCompartiments ?></p><?php endif; ?>

    <div class="card">
      <h3>Gestion des médicaments</h3>
      <form method="post">
        <label>Patient (nom) :</label>
        <input type="text" name="patient" placeholder="Ex : Durand" required>
        <label>Médicament :</label>
        <input type="text" name="medic" placeholder="Ex : Doliprane" required>
        <label>Heure :</label>
        <input type="time" name="horaire" required>
        <button type="submit">Ajouter à l’armoire</button>
      </form>
    </div>

    <div class="card">
      <h3>Réglage de la température</h3>
      <form method="post">
        <input type="number" name="temperature" min="2" max="25" value="20">
        <button type="submit">Appliquer</button>
      </form>
    </div>

    <div class="card">
      <h3>Sécurité des compartiments</h3>
      <form method="post">
        <button name="compartiment" value="verrouiller">Verrouiller tous</button>
        <button name="compartiment" value="deverrouiller">Déverrouiller tous</button>
      </form>
    </div>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>
</body>
</html>
