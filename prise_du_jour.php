<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('patient');

// Récupération de l'identifiant patient à partir de l'utilisateur connecté
$idU = $_SESSION['idU'] ?? 0;
$stmt = $connexion->prepare("SELECT IdP FROM Patient WHERE IdU = ?");
$stmt->bind_param("i", $idU);
$stmt->execute();
$res = $stmt->get_result();
$idP = $res->fetch_assoc()['IdP'] ?? 0;

// Récupération des prises du jour non encore confirmées
$query = "
    SELECT p.IdPrise, p.HeurePrise, m.NomMed
    FROM Prise_Medicament p
    JOIN concerne c ON p.IdPrise = c.IdPrise
    JOIN Medicament m ON m.IdMed = c.IdMed
    WHERE p.IdP = ? AND DATE(p.HeurePrise) = CURDATE() AND p.Confirme = 0
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
      <li><a href="dashboard.php">Tableau de bord</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="card fade-in">
    <h2>Mes prises de médicaments aujourd’hui</h2>

    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
      <p class="success">Prise confirmée avec succès !</p>
    <?php elseif (isset($_GET['success']) && $_GET['success'] == '0'): ?>
      <p class="alert">Échec lors de la confirmation.</p>
    <?php endif; ?>

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
              <td><?= htmlspecialchars(substr($row['HeurePrise'], 11, 5)) ?></td>
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
