<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Alertes - SmartCare</title>
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
<section id="alertes" class="card fade-in slide-in-right">
  <h2>Alertes en Temps Réel</h2>
  <p>Liste des dernières alertes médicales (oubli de prise, bouton urgence, etc.)</p>

  <ul class="alert-list">
    <?php
      $res = $connexion->query("SELECT * FROM Alerte ORDER BY DateA DESC, HeureA DESC LIMIT 10");
      while ($row = $res->fetch_assoc()):
    ?>
      <li><strong><?= $row['DateA'] ?> <?= $row['HeureA'] ?></strong> – <?= htmlspecialchars($row['TypeA']) ?></li>
    <?php endwhile; ?>
  </ul>
</section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
