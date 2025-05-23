<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

// Récupération de l'IdA du soignant connecté
$idUtilisateur = $_SESSION['idU'] ?? 0;
$idAideSoignant = 0;
$stmt = $connexion->prepare("SELECT IdA FROM Aide_Soignant WHERE IdU = ?");
$stmt->bind_param("i", $idUtilisateur);
$stmt->execute();
$res = $stmt->get_result();
$idAideSoignant = $res->num_rows > 0 ? $res->fetch_assoc()['IdA'] : 0;

$message = '';

// Association
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['associer'])) {
    $nomPatient = trim($_POST['patient'] ?? '');
    $nomFamille = trim($_POST['famille'] ?? '');
    $lien = trim($_POST['lien'] ?? '');

    if ($nomPatient && $nomFamille && $lien) {
        $stmt = $connexion->prepare("SELECT IdP FROM Patient WHERE CONCAT(PrenomP, ' ', NomP) = ? AND IdA = ?");
        $stmt->bind_param("si", $nomPatient, $idAideSoignant);
        $stmt->execute();
        $resP = $stmt->get_result()->fetch_assoc();

        $stmt = $connexion->prepare("SELECT IdM FROM Membre_Famille WHERE CONCAT(PrenomM, ' ', NomM) = ? AND IdA = ?");
        $stmt->bind_param("si", $nomFamille, $idAideSoignant);
        $stmt->execute();
        $resM = $stmt->get_result()->fetch_assoc();

        if ($resP && $resM) {
            $idP = $resP['IdP'];
            $idM = $resM['IdM'];
            $stmt = $connexion->prepare("INSERT INTO associe (IdP, IdM, Lien_Parente) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Lien_Parente = VALUES(Lien_Parente)");
            $stmt->bind_param("iis", $idP, $idM, $lien);
            $stmt->execute();
            $message = "Association réussie.";
        } else {
            $message = "Patient ou membre introuvable ou non créé par vous.";
        }
    } else {
        $message = "Tous les champs sont obligatoires.";
    }
}

// Suppression
if (isset($_GET['delete_idp']) && isset($_GET['delete_idm'])) {
    $idP = intval($_GET['delete_idp']);
    $idM = intval($_GET['delete_idm']);
    $stmt = $connexion->prepare("DELETE FROM associe WHERE IdP = ? AND IdM = ?");
    $stmt->bind_param("ii", $idP, $idM);
    $stmt->execute();
    $message = "Association supprimée.";
}

// Chargement des noms limités au soignant
$patients = $connexion->prepare("SELECT CONCAT(PrenomP, ' ', NomP) AS nom FROM Patient WHERE IdA = ?");
$patients->bind_param("i", $idAideSoignant);
$patients->execute();
$patients = $patients->get_result()->fetch_all(MYSQLI_ASSOC);

$familles = $connexion->prepare("SELECT CONCAT(PrenomM, ' ', NomM) AS nom FROM Membre_Famille WHERE IdA = ?");
$familles->bind_param("i", $idAideSoignant);
$familles->execute();
$familles = $familles->get_result()->fetch_all(MYSQLI_ASSOC);

$associations = $connexion->prepare("
    SELECT a.IdP, a.IdM, CONCAT(p.PrenomP, ' ', p.NomP) AS patient, CONCAT(m.PrenomM, ' ', m.NomM) AS famille, a.Lien_Parente
    FROM associe a
    JOIN Patient p ON p.IdP = a.IdP
    JOIN Membre_Famille m ON m.IdM = a.IdM
    WHERE p.IdA = ? AND m.IdA = ?
");
$associations->bind_param("ii", $idAideSoignant, $idAideSoignant);
$associations->execute();
$associations = $associations->get_result()->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Associer Famille - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    input.valid { border: 2px solid green; }
    input.invalid { border: 2px solid red; }
  </style>
  <script>
    const patients = <?= json_encode(array_column($patients, 'nom')) ?>;
    const familles = <?= json_encode(array_column($familles, 'nom')) ?>;

    function verifierChamp(id, liste) {
        const champ = document.getElementById(id);
        if (liste.includes(champ.value.trim())) {
            champ.classList.add('valid');
            champ.classList.remove('invalid');
            return true;
        } else {
            champ.classList.add('invalid');
            champ.classList.remove('valid');
            return false;
        }
    }

    function verifierFormulaire() {
        const ok1 = verifierChamp('patient', patients);
        const ok2 = verifierChamp('famille', familles);
        const lien = document.getElementById('lien').value.trim();
        document.getElementById('associer').disabled = !(ok1 && ok2 && lien.length > 0);
    }

    window.onload = () => {
        document.getElementById('patient').addEventListener('input', () => verifierFormulaire());
        document.getElementById('famille').addEventListener('input', () => verifierFormulaire());
        document.getElementById('lien').addEventListener('input', () => verifierFormulaire());
    }
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
    <h2>Associer un membre de la famille à un patient</h2>
    <?php if ($message): ?><p class="info"><?= htmlspecialchars($message) ?></p><?php endif; ?>

    <form method="post">
      <label>Nom complet du patient :</label>
      <input type="text" name="patient" id="patient" list="listPatients" required autocomplete="off">
      <datalist id="listPatients">
        <?php foreach ($patients as $p): ?>
          <option value="<?= htmlspecialchars($p['nom']) ?>">
        <?php endforeach; ?>
      </datalist>

      <label>Nom complet du membre de la famille :</label>
      <input type="text" name="famille" id="famille" list="listFamilles" required autocomplete="off">
      <datalist id="listFamilles">
        <?php foreach ($familles as $f): ?>
          <option value="<?= htmlspecialchars($f['nom']) ?>">
        <?php endforeach; ?>
      </datalist>

      <label>Lien de parenté :</label>
      <input type="text" name="lien" id="lien" required>

      <button type="submit" name="associer" id="associer" class="btn-primary" disabled>Associer</button>
    </form>
  </section>

  <section class="card fade-in">
    <h3>Associations existantes</h3>
    <table>
      <thead>
        <tr><th>Patient</th><th>Famille</th><th>Lien</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($associations as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['patient']) ?></td>
            <td><?= htmlspecialchars($a['famille']) ?></td>
            <td><?= htmlspecialchars($a['Lien_Parente']) ?></td>
            <td><a href="?delete_idp=<?= $a['IdP'] ?>&delete_idm=<?= $a['IdM'] ?>" onclick="return confirm('Supprimer cette association ?')" class="btn-primary">Supprimer</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare</p>
</footer>
</body>
</html>
