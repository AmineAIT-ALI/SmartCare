<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

if ($_SESSION['role'] !== 'aide_soignant') {
  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Alertes - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .alert-critique { color: red; font-weight: bold; }
    .alert-normal { color: #555; }
  </style>
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
<section class="card fade-in slide-in-right">
  <h2>Alertes en Temps Réel</h2>
  <p>Surveillez les urgences médicales : oubli de prise, ouverture anormale, pression du bouton, etc.</p>

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Heure</th>
        <th>Type d'alerte</th>
        <th>Message</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $stmt = $connexion->query("SELECT DateA, HeureA, TypeA, Message FROM Alerte ORDER BY DateA DESC, HeureA DESC LIMIT 20");
        if ($stmt->num_rows === 0) {
          echo "<tr><td colspan='4'>Aucune alerte enregistrée pour le moment.</td></tr>";
        } else {
          while ($row = $stmt->fetch_assoc()) {
            $classe = stripos($row['TypeA'], 'urgence') !== false ? 'alert-critique' : 'alert-normal';
            echo "<tr class='{$classe}'>";
            echo "<td>" . htmlspecialchars($row['DateA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['HeureA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TypeA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Message']) . "</td>";
            echo "</tr>";
          }
        }
      ?>
    </tbody>
  </table>
</section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
