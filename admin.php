<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

$confirmation = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !empty($_POST['patient']) &&
        !empty($_POST['medic']) &&
        !empty($_POST['nb_jours']) &&
        !empty($_POST['frequence']) &&
        !empty($_POST['heures']) &&
        is_array($_POST['heures']) &&
        !empty($_POST['debut'])
    ) {
        $nomPatient = trim($_POST['patient']);
        $nomMedicament = trim($_POST['medic']);
        $nb_jours = (int) $_POST['nb_jours'];
        $frequence = (int) $_POST['frequence'];
        $heures = $_POST['heures'];
        $start = $_POST['debut'];
        $tmin = isset($_POST['temp_min']) ? floatval($_POST['temp_min']) : null;
        $tmax = isset($_POST['temp_max']) ? floatval($_POST['temp_max']) : null;

        if (count($heures) !== $frequence) {
            $erreur = "Vous devez renseigner exactement $frequence heure(s) de prise.";
        } else {
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
                $med = $stmt->get_result()->fetch_assoc();

                if ($med) {
                    $idMed = $med['IdMed'];
                    $stmt = $connexion->prepare("UPDATE Medicament SET Temp_Min=?, Temp_Max=? WHERE IdMed=?");
                    $stmt->bind_param("ddi", $tmin, $tmax, $idMed);
                    $stmt->execute();
                } else {
                    $stmt = $connexion->prepare("INSERT INTO Medicament (NomMed, Conseil_conservation, Temp_Min, Temp_Max) VALUES (?, 'Température ambiante', ?, ?)");
                    $stmt->bind_param("sdd", $nomMedicament, $tmin, $tmax);
                    $stmt->execute();
                    $idMed = $stmt->insert_id;
                }

                for ($j = 0; $j < $nb_jours; $j++) {
                    $dateJour = date('Y-m-d', strtotime("+$j day", strtotime($start)));
                    foreach ($heures as $heure) {
                        $datetime = "$dateJour $heure:00";
                        $stmt = $connexion->prepare("INSERT INTO Prise_Medicament (HeurePrise, Confirme, IdP) VALUES (?, 0, ?)");
                        $stmt->bind_param("si", $datetime, $idP);
                        $stmt->execute();
                        $idPrise = $stmt->insert_id;

                        $stmt = $connexion->prepare("INSERT INTO concerne (IdPrise, IdMed) VALUES (?, ?)");
                        $stmt->bind_param("ii", $idPrise, $idMed);
                        $stmt->execute();
                    }
                }

                $confirmation = "Traitement de $nomMedicament ajouté pour $nomPatient à partir du $start.";
            } else {
                $erreur = "Patient introuvable.";
            }
        }
    } else {
        $erreur = "Tous les champs obligatoires doivent être remplis.";
    }
}

$patients = $connexion->query("SELECT NomP FROM Patient")->fetch_all(MYSQLI_ASSOC);
$medicaments = $connexion->query("SELECT NomMed, Temp_Min, Temp_Max FROM Medicament")->fetch_all(MYSQLI_ASSOC);

// Préparer les données des médicaments pour JavaScript
$medicamentData = [];
foreach ($medicaments as $m) {
    $medicamentData[$m['NomMed']] = [
        'Temp_Min' => $m['Temp_Min'],
        'Temp_Max' => $m['Temp_Max']
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Administration - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const medicamentData = <?php echo json_encode($medicamentData); ?>;

      const medicInput = document.querySelector('input[name="medic"]');
      const tempMinInput = document.querySelector('input[name="temp_min"]');
      const tempMaxInput = document.querySelector('input[name="temp_max"]');
      const frequenceInput = document.querySelector('input[name="frequence"]');
      const heuresContainer = document.getElementById("heures-container");

      function genererChampsHeures() {
        const freq = parseInt(frequenceInput.value);
        heuresContainer.innerHTML = '';
        if (!isNaN(freq) && freq > 0) {
          for (let i = 0; i < freq; i++) {
            const input = document.createElement("input");
            input.type = "time";
            input.name = "heures[]";
            input.required = true;
            heuresContainer.appendChild(input);
          }
        }
      }

      frequenceInput.addEventListener('input', genererChampsHeures);
      genererChampsHeures();

      medicInput.addEventListener('input', () => {
        const nom = medicInput.value.trim();
        if (medicamentData[nom]) {
          tempMinInput.value = medicamentData[nom].Temp_Min;
          tempMaxInput.value = medicamentData[nom].Temp_Max;
        } else {
          tempMinInput.value = '';
          tempMaxInput.value = '';
        }
      });
    });
  </script>
</head>
<body>
<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <li><a href="dashboard.php">Tableau de bord</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>
<main>
  <section class="card fade-in">
    <h2>Espace Administration</h2>
    <?php if ($confirmation): ?><p class="ok"><?= $confirmation ?></p><?php endif; ?>
    <?php if ($erreur): ?><p class="alert"><?= $erreur ?></p><?php endif; ?>

    <div class="card">
      <h3>Planification d’un traitement</h3>
      <form method="post">
        <label>Nom du patient :</label>
        <input list="patients" name="patient" required>
        <datalist id="patients">
          <?php foreach ($patients as $p): ?>
            <option value="<?= htmlspecialchars($p['NomP']) ?>">
          <?php endforeach; ?>
        </datalist>

        <label>Nom du médicament :</label>
        <input list="medics" name="medic" required>
        <datalist id="medics">
          <?php foreach ($medicaments as $m): ?>
            <option value="<?= htmlspecialchars($m['NomMed']) ?>">
          <?php endforeach; ?>
        </datalist>

        <label>Début du traitement :</label>
        <input type="date" name="debut" value="<?= date('Y-m-d') ?>" required>

        <label>Durée (en jours) :</label>
        <input type="number" name="nb_jours" min="1" value="7" required>

        <label>Fréquence quotidienne (nombre de prises) :</label>
        <input type="number" name="frequence" min="1" value="3" required>

        <label>Heures de prise :</label>
        <div id="heures-container"></div>

        <label>Température min (°C) :</label>
        <input type="number" name="temp_min" step="0.1" placeholder="ex : 2.0">

        <label>Température max (°C) :</label>
        <input type="number" name="temp_max" step="0.1" placeholder="ex : 25.0">

        <button type="submit" class="btn-primary">Valider le traitement</button>
      </form>
    </div>
  </section>
</main>
<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>
</body>
</html>
