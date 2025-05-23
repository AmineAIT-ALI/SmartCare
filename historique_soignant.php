<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

$idUtilisateur = $_SESSION['idU'] ?? 0;

// Récupérer l'IdA du soignant connecté
$stmt = $connexion->prepare("SELECT IdA FROM Aide_Soignant WHERE IdU = ?");
$stmt->bind_param("i", $idUtilisateur);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    die("❌ Utilisateur connecté non reconnu comme aide-soignant.");
}

$idA = $row['IdA'];

// Requête historique des prises
$query = "
    SELECT pa.NomP, pa.PrenomP, m.NomMed, p.HeurePrise, p.Confirme
    FROM suivre s
    JOIN Patient pa ON pa.IdP = s.IdP
    JOIN Prise_Medicament p ON p.IdP = pa.IdP
    JOIN concerne c ON c.IdPrise = p.IdPrise
    JOIN Medicament m ON m.IdMed = c.IdMed
    WHERE s.IdA = ?
    ORDER BY p.HeurePrise DESC
";
$stmt = $connexion->prepare($query);
$stmt->bind_param("i", $idA);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Historique Soignant - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
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
<section id="historique-soignant" class="card fade-in slide-in-right">
  <h2>Historique des Patients</h2>
  <p>Suivi global des prises de médicaments pour les patients que vous suivez.</p>
  <p class="info">Nombre total de prises listées : <strong><?= $result->num_rows ?></strong></p>

  <?php if ($result->num_rows === 0): ?>
    <p class="alert">Aucune prise de médicament trouvée.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Patient</th>
          <th>Médicament</th>
          <th>Date</th>
          <th>Heure</th>
          <th>Confirmée</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['PrenomP'] . ' ' . $row['NomP']) ?></td>
            <td><?= htmlspecialchars($row['NomMed']) ?></td>
            <td><?= htmlspecialchars(substr($row['HeurePrise'], 0, 10)) ?></td>
            <td><?= htmlspecialchars(substr($row['HeurePrise'], 11, 5)) ?></td>
            <td class="<?= $row['Confirme'] ? 'ok' : 'alert' ?>">
              <?= $row['Confirme'] ? '✅' : '❌' ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
