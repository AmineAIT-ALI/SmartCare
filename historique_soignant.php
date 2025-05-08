<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

$idA = $_SESSION['id'];

$query = "
    SELECT pa.NomP, pa.PrenomP, m.NomMed, pr.DatePres, p.HeurePrise, p.Confirme
    FROM suivre s
    JOIN Patient pa ON pa.IdP = s.IdP
    JOIN Prescription pr ON pr.IdP = pa.IdP
    JOIN prescris ps ON ps.IdPres = pr.IdPres
    JOIN Medicament m ON m.IdMed = ps.IdMed
    JOIN concerne c ON c.IdMed = m.IdMed
    JOIN Prise_Medicament p ON p.IdPrise = c.IdPrise
    WHERE s.IdA = ?
    ORDER BY pr.DatePres DESC, p.HeurePrise DESC
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
      <li><a href="historique_soignant.php">Historique</a></li>
      <li><a href="alertes.php">Alertes</a></li>
      <li><a href="admin.php">Administration</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main>
<section id="historique-soignant" class="card fade-in slide-in-right">
  <h2>Historique des Patients</h2>
  <p>Suivi global de la prise de médicaments pour l’ensemble des patients suivis :</p>
  <p class="info">Nombre total de prises listées : <strong><?= $result->num_rows ?></strong></p>

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
          <td><?= htmlspecialchars($row['DatePres']) ?></td>
          <td><?= htmlspecialchars(substr($row['HeurePrise'], 0, 5)) ?></td>
          <td class="<?= $row['Confirme'] ? 'ok' : 'alert' ?>">
            <?= $row['Confirme'] ? '✅' : '❌' ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
