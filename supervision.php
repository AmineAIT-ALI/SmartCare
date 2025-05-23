<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="10">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Supervision Temps Réel</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header>
    <h1>SmartCare - Supervision</h1>
    <nav>
      <ul>
        <li><a href="dashboard.php">Tableau de bord</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <section class="fade-in">
      <h2>Données en temps réel</h2>
      <p class="info">Page actualisée automatiquement toutes les 10 secondes</p>

      <table>
        <thead>
          <tr>
            <th>Périphérique</th>
            <th>Type</th>
            <th>Valeur</th>
            <th>Date</th>
            <th>Heure</th>
          </tr>
        </thead>
        <tbody>
          <?php
          require_once 'includes/session.php';
          require_once 'includes/db.php';
          $idU = $_SESSION['idU'] ?? 0;
          $role = $_SESSION['role'] ?? '';
          if (!$idU || !in_array($role, ['patient', 'aide_soignant', 'membre_famille'])) {
              header("Location: login.php");
              exit;
          }

          $idsP = [];
          if ($role === 'patient') {
              $stmt = $connexion->prepare("SELECT IdP FROM Patient WHERE IdU = ?");
              $stmt->bind_param("i", $idU);
              $stmt->execute();
              $res = $stmt->get_result();
              while ($row = $res->fetch_assoc()) $idsP[] = $row['IdP'];
          } elseif ($role === 'aide_soignant') {
              $stmt = $connexion->prepare("SELECT IdP FROM suivre WHERE IdA = (SELECT IdA FROM Aide_Soignant WHERE IdU = ?)");
              $stmt->bind_param("i", $idU);
              $stmt->execute();
              $res = $stmt->get_result();
              while ($row = $res->fetch_assoc()) $idsP[] = $row['IdP'];
          } elseif ($role === 'membre_famille') {
              $stmt = $connexion->prepare("SELECT IdP FROM associe WHERE IdM = (SELECT IdM FROM Membre_Famille WHERE IdU = ?)");
              $stmt->bind_param("i", $idU);
              $stmt->execute();
              $res = $stmt->get_result();
              while ($row = $res->fetch_assoc()) $idsP[] = $row['IdP'];
          }

          if (!empty($idsP)) {
              $in = implode(',', $idsP);
              $query = "
                  SELECT r.*, p.TypeP, p.Description 
                  FROM Armoire a
                  JOIN Peripherique p ON p.IdArm = a.IdArm
                  JOIN (
                      SELECT r1.*
                      FROM Releve r1
                      INNER JOIN (
                          SELECT IdPeriph, MAX(CONCAT(DateHeure)) AS MaxDate
                          FROM Releve GROUP BY IdPeriph
                      ) r2 ON r1.IdPeriph = r2.IdPeriph AND CONCAT(r1.DateHeure) = r2.MaxDate
                  ) r ON r.IdPeriph = p.IdPeriph
                  WHERE a.IdP IN ($in)
                  ORDER BY r.DateHeure DESC
              ";
              $result = $connexion->query($query);
              while ($row = $result->fetch_assoc()) {
                  echo "<tr>
                      <td>{$row['Description']}</td>
                      <td>{$row['TypeP']}</td>
                      <td>{$row['ValeurR']}</td>
                      <td>{$row['DateHeure']}</td>
                      <td>" . date('H:i:s', strtotime($row['DateHeure'])) . "</td>
                  </tr>";
              }
          } else {
              echo "<tr><td colspan='5'>Aucun patient associé.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </section>
  </main>

  <footer>
    &copy; 2025 SmartCare - Supervision Domotique Médicale
  </footer>
</body>
</html>
