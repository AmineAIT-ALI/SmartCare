<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('patient');

$idP = $_SESSION['id'];

// Récupération des prises du jour non encore confirmées
$query = "
    SELECT p.IdPrise, p.HeurePrise, m.NomMed, pr.DatePres
    FROM Prise_Medicament p
    JOIN concerne c ON p.IdPrise = c.IdPrise
    JOIN Medicament m ON m.IdMed = c.IdMed
    JOIN prescris prx ON c.IdMed = prx.IdMed
    JOIN Prescription pr ON prx.IdPres = pr.IdPres
    WHERE pr.IdP = ? AND DATE(p.HeurePrise) = CURDATE()
    ORDER BY p.HeurePrise ASC
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
  <title>Ma Prise du Jour - SmartCare</title>
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
  <section>
    <h2>Mes prises de médicaments aujourd’hui</h2>
    <?php if ($result->num_rows === 0): ?>
      <p class="info">Aucune prise prévue aujourd’hui ou toutes déjà confirmées.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Heure</th>
            <th>Médicament</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars(substr($row['HeurePrise'], 0, 5)) ?></td>
              <td><?= htmlspecialchars($row['NomMed']) ?></td>
              <td>
                <form action="prise_confirm.php" method="POST">
                  <input type="hidden" name="idPrise" value="<?= $row['IdPrise'] ?>">
                  <button type="submit">Confirmer</button>
                </form>
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
