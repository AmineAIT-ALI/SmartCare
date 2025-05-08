<?php
session_start();
$connecte = isset($_SESSION['role']) && $_SESSION['role'] !== 'guest';
$nom_utilisateur = $_SESSION['utilisateur'] ?? '';
$role = $_SESSION['role'] ?? 'guest';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accueil - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <li><a href="index.php">Accueil</a></li>
      <?php if ($connecte && $role === 'patient'): ?>
        <li><a href="historique_patient.php">Historique</a></li>
        <li><a href="prise_du_jour.php">Ma prise</a></li>
      <?php elseif ($connecte && $role === 'membre_famille'): ?>
        <li><a href="famille.php">Suivi famille</a></li>
      <?php elseif ($connecte && $role === 'aide_soignant'): ?>
        <li><a href="historique_soignant.php">Historique</a></li>
        <li><a href="alertes.php">Alertes</a></li>
        <li><a href="admin.php">Administration</a></li>
      <?php endif; ?>
      <?php if ($connecte): ?>
        <li><a href="logout.php">Déconnexion</a></li>
      <?php else: ?>
        <li><a href="login.php">Connexion</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>

<main>
  <section class="card fade-in">
    <h2>Bienvenue sur <span style="color:#1e88e5">SmartCare</span></h2>
    <p>La solution domotique intelligente pour la sécurité des patients en EHPAD.</p>

    <?php if (!$connecte): ?>
      <a href="login.php" class="btn-primary">Se connecter</a>
    <?php else: ?>
      <p>Bienvenue <strong><?= htmlspecialchars($nom_utilisateur) ?></strong>, vous êtes connecté !</p>
    <?php endif; ?>
  </section>

  <section class="grid-3 slide-in-right">
  <div class="card">
    <h3>Vision & Impact</h3>
    <p><strong>SmartCare</strong> est une initiative MIAGE Toulouse qui vise à transformer le suivi médicamenteux en EHPAD grâce à une solution connectée fiable, centrée sur la sécurité du patient et la fluidité du parcours de soin.</p>
  </div>

  <div class="card">
    <h3>Soin Humain Augmenté</h3>
    <p>En plaçant la technologie au service du personnel médical, SmartCare valorise le rôle des soignants tout en préservant la dignité des patients. C’est un outil éthique, inclusif, et pensé pour le quotidien des professionnels de santé.</p>
  </div>

  <div class="card">
    <h3>Pérennité & Croissance</h3>
    <p>Notre approche repose sur une vision durable et évolutive : interopérabilité avec les systèmes existants, coût maîtrisé, et ouverture à des financements publics/privés pour une diffusion à grande échelle.</p>
  </div>

  <div class="card">
    <h3>Prêt à l’Usage</h3>
    <p>SmartCare s’intègre facilement dans tous les établissements de santé. Installation rapide, interface intuitive, compatibilité Domoticz et Raspberry Pi : tout est pensé pour un déploiement sans friction.</p>
  </div>
  </section>

</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
