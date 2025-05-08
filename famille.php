<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('membre_famille');

$idFamille = $_SESSION['id'];

$query = "
  SELECT pa.PrenomP, pa.NomP, m.NomMed, pr.DatePres, p.HeurePrise, p.Confirme
  FROM suivi_famille sf
  JOIN Patient pa ON pa.IdP = sf.IdP
  JOIN Prescription pr ON pr.IdP = pa.IdP
  JOIN prescris ps ON ps.IdPres = pr.IdPres
  JOIN Medicament m ON m.IdMed = ps.IdMed
  JOIN concerne c ON c.IdMed = m.IdMed
  JOIN Prise_Medicament p ON p.IdPrise = c.IdPrise
  WHERE sf.IdM = ?
  ORDER BY pa.NomP, pr.DatePres DESC, p.HeurePrise DESC
";

$stmt = $connexion->prepare($query);
$stmt->bind_param("i", $idFamille);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Suivi Famille - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <li><a href="dashboard.php">Accueil</a></li>
      <li><a href="famille.php">Suivi famille</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main>
  <section id="suivi-famille" class="card fade-in">
    <h2>Suivi des Proches</h2>
    <p>Retrouvez ici l’historique des prises de médicaments de vos proches.</p>
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
