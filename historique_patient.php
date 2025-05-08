<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('patient');

$idP = $_SESSION['id'];

$query = "
    SELECT p.HeurePrise, p.Confirme, m.NomMed, pr.DatePres
    FROM Prise_Medicament p
    JOIN concerne c ON p.IdPrise = c.IdPrise
    JOIN Medicament m ON m.IdMed = c.IdMed
    JOIN prescris prx ON c.IdMed = prx.IdMed
    JOIN Prescription pr ON prx.IdPres = pr.IdPres
    WHERE pr.IdP = ?
    ORDER BY pr.DatePres DESC, p.HeurePrise DESC
";

$stmt = $connexion->prepare($query);
$stmt->bind_param("i", $idP);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mon Historique - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <li><a href="dashboard.php">Accueil</a></li>
      <li><a href="historique_patient.php">Historique</a></li>
      <li><a href="prise_du_jour.php">Ma prise</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="card fade-in">
    <h2>Mon Historique de Prise</h2>
    <p>Voici l’historique de vos dernières prises de médicaments :</p>

    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Heure</th>
          <th>Médicament</th>
          <th>Confirmée</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['DatePres']) ?></td>
            <td><?= htmlspecialchars(substr($row['HeurePrise'], 0, 5)) ?></td>
            <td><?= htmlspecialchars($row['NomMed']) ?></td>
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
