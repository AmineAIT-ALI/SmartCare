<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('admin');

$message = $_GET['msg'] ?? '';

// Lier une armoire à un patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lier'])) {
    $idArm = intval($_POST['armoire'] ?? 0);
    $idP = intval($_POST['patient'] ?? 0);

    if ($idArm && $idP) {
        $stmt = $connexion->prepare("SELECT Adresse FROM Patient WHERE IdP = ?");
        $stmt->bind_param("i", $idP);
        $stmt->execute();
        $adresse = $stmt->get_result()->fetch_assoc()['Adresse'] ?? '';

        if ($adresse) {
            $stmtUpdate = $connexion->prepare("UPDATE Armoire SET IdP = ?, Localisation = ? WHERE IdArm = ?");
            $stmtUpdate->bind_param("isi", $idP, $adresse, $idArm);
            $stmtUpdate->execute();

            $stmtIdA = $connexion->prepare("SELECT IdA FROM suivre WHERE IdP = ?");
            $stmtIdA->bind_param("i", $idP);
            $stmtIdA->execute();
            $resIdA = $stmtIdA->get_result()->fetch_assoc();

            if ($resIdA) {
                $idA = $resIdA['IdA'];
                $stmtUpdateIdA = $connexion->prepare("UPDATE Armoire SET IdA = ? WHERE IdArm = ?");
                $stmtUpdateIdA->bind_param("ii", $idA, $idArm);
                $stmtUpdateIdA->execute();
            }

            header("Location: lier_armoire.php?msg=Armoire liée avec succès.");
            exit;
        } else {
            $message = "Adresse du patient introuvable.";
        }
    } else {
        $message = "Veuillez sélectionner une armoire et un patient.";
    }
}

// Supprimer une liaison armoire-patient
if (isset($_GET['unlink'])) {
    $idArm = intval($_GET['unlink']);
    $stmt = $connexion->prepare("UPDATE Armoire SET IdP = NULL, IdA = NULL, Localisation = NULL WHERE IdArm = ?");
    $stmt->bind_param("i", $idArm);
    $stmt->execute();
    header("Location: lier_armoire.php?msg=Liaison supprimée.");
    exit;
}

// Armoires libres
$armoires = $connexion->query("SELECT IdArm FROM Armoire WHERE IdP IS NULL")->fetch_all(MYSQLI_ASSOC);

// Patients sans armoire
$patients = $connexion->query("
    SELECT p.IdP, CONCAT(p.PrenomP, ' ', p.NomP) AS nom 
    FROM Patient p 
    WHERE NOT EXISTS (SELECT 1 FROM Armoire a WHERE a.IdP = p.IdP)
")->fetch_all(MYSQLI_ASSOC);

// Liste des associations
$liste = $connexion->query("
    SELECT a.IdArm, CONCAT(p.PrenomP, ' ', p.NomP) AS patient, a.Localisation
    FROM Armoire a
    JOIN Patient p ON p.IdP = a.IdP
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lier une armoire</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
  <h1>SmartCare - Administration</h1>
  <nav>
    <ul>
      <li><a href="dashboard.php">Tableau de bord</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>
<main class="card fade-in">
    <h2>Associer une armoire à un patient</h2>
    <?php if ($message): ?><p class="info"><?= htmlspecialchars($message) ?></p><?php endif; ?>

    <form method="post">
        <label>Armoire :
            <select name="armoire" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($armoires as $a): ?>
                    <option value="<?= $a['IdArm'] ?>">Armoire<?= $a['IdArm'] ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Patient :
            <select name="patient" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?= $p['IdP'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" name="lier" class="btn-primary">Lier</button>
    </form>

    <h3>Armoires liées</h3>
    <table>
        <thead><tr><th>Armoire</th><th>Patient</th><th>Localisation</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($liste as $l): ?>
            <tr>
                <td>Armoire<?= $l['IdArm'] ?></td>
                <td><?= htmlspecialchars($l['patient']) ?></td>
                <td><?= htmlspecialchars($l['Localisation']) ?></td>
                <td><a href="?unlink=<?= $l['IdArm'] ?>" onclick="return confirm('Détacher cette armoire ?')" class="btn-primary alert">Détacher</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
