<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('membre_famille');

// Récupération de l'identifiant utilisateur depuis la session
$idUtilisateur = $_SESSION['idU'] ?? 0;

// Récupération de l'identifiant du membre de la famille
$stmtIdM = $connexion->prepare("SELECT IdM FROM Membre_Famille WHERE IdU = ?");
$stmtIdM->bind_param("i", $idUtilisateur);
$stmtIdM->execute();
$res = $stmtIdM->get_result();
$idFamille = ($res->num_rows > 0) ? $res->fetch_assoc()['IdM'] : 0;

// Récupération des patients associés au membre connecté
$stmtP = $connexion->prepare("
  SELECT DISTINCT p.IdP, p.PrenomP, p.NomP
  FROM associe a
  JOIN Patient p ON p.IdP = a.IdP
  WHERE a.IdM = ?
  ORDER BY p.NomP
");
$stmtP->bind_param("i", $idFamille);
$stmtP->execute();
$patients = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);

// Détermination du patient sélectionné (via formulaire POST ou par défaut)
$selectedIdP = intval($_POST['patient'] ?? ($patients[0]['IdP'] ?? 0));

// Récupération des prises du patient sélectionné
$result = false;
if ($selectedIdP > 0) {
    $stmt = $connexion->prepare("
        SELECT m.NomMed, p.HeurePrise, p.Confirme
        FROM Prise_Medicament p
        JOIN concerne c ON c.IdPrise = p.IdPrise
        JOIN Medicament m ON m.IdMed = c.IdMed
        WHERE p.IdP = ?
        ORDER BY p.HeurePrise ASC
    ");
    $stmt->bind_param("i", $selectedIdP);
    $stmt->execute();
    $result = $stmt->get_result();
}
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
      <li><a href="dashboard.php">Tableau de bord</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main>
<section class="card fade-in">
  <h2>Suivi de vos proches</h2>

  <?php if (empty($patients)): ?>
    <p class="alert">Aucun patient ne vous est associé.</p>
  <?php else: ?>
    <form method="post" style="margin-bottom: 10px;">
      <label for="patient">Choisir un patient :</label>
      <select name="patient" id="patient" onchange="this.form.submit()">
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['IdP'] ?>" <?= $p['IdP'] == $selectedIdP ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['PrenomP'] . ' ' . $p['NomP']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <p>Prises pour : <strong>
      <?php
        foreach ($patients as $p) {
          if ($p['IdP'] == $selectedIdP) {
            echo htmlspecialchars($p['PrenomP'] . ' ' . $p['NomP']);
            break;
          }
        }
      ?>
    </strong></p>

    <?php if (!$result || $result->num_rows === 0): ?>
      <p class="alert">Aucune prise enregistrée pour ce patient.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Médicament</th>
            <th>Date</th>
            <th>Heure</th>
            <th>Confirmée</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
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
  <?php endif; ?>
</section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>
</body>
</html>
