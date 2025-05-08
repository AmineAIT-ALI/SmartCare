<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

$role = $_SESSION['role'] ?? 'guest';
$nom_utilisateur = $_SESSION['utilisateur'] ?? 'Anonyme';

$nomRole = [
  'patient' => 'Patient',
  'membre_famille' => 'Famille',
  'aide_soignant' => 'Soignant',
  'guest' => 'Invité'
];

$liens = [
  'patient' => [
    'Mon historique' => 'historique_patient.php',
    'Confirmer ma prise' => 'prise_du_jour.php'
  ],
  'membre_famille' => [
    'Suivi des proches' => 'famille.php'
  ],
  'aide_soignant' => [
    'Historique global' => 'historique_soignant.php',
    'Alertes en temps réel' => 'alertes.php',
    'Panneau d’administration' => 'admin.php'
  ]
];

// Fonctions statistiques pour aide-soignant
function nombrePatients($conn) {
  $res = $conn->query("SELECT COUNT(*) FROM Patient");
  return $res ? $res->fetch_row()[0] : '-';
}

function prisesAujourdhui($conn) {
  $res = $conn->query("SELECT COUNT(*) FROM Prise_Medicament WHERE DATE(HeurePrise) = CURDATE()");
  return $res ? $res->fetch_row()[0] : '-';
}

function alertesActives($conn) {
  $res = $conn->query("SELECT COUNT(*) FROM Prise_Medicament WHERE Confirme = 0 AND DATE(HeurePrise) = CURDATE()");
  return $res ? $res->fetch_row()[0] : '-';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <li><a href="index.php">Accueil</a></li>
      <?php if ($role === 'patient'): ?>
        <li><a href="historique_patient.php">Historique</a></li>
        <li><a href="prise_du_jour.php">Ma prise</a></li>
      <?php elseif ($role === 'membre_famille'): ?>
        <li><a href="famille.php">Suivi famille</a></li>
      <?php elseif ($role === 'aide_soignant'): ?>
        <li><a href="historique_soignant.php">Historique</a></li>
        <li><a href="alertes.php">Alertes</a></li>
        <li><a href="admin.php">Administration</a></li>
      <?php endif; ?>
      <?php if ($role !== 'guest'): ?>
        <li><a href="logout.php">Déconnexion</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>

<main>
  <section id="dashboard">
    <h2>Bienvenue <?= $nomRole[$role] ?> sur SmartCare</h2>
    <p>Connecté en tant que <strong><?= $nomRole[$role] ?></strong> (<?= $nom_utilisateur ?>)</p>

    <?php if ($role !== 'guest'): ?>
      <div class="card">
        <h3>Accès rapides : </h3>
        <ul class="horizontal-menu">
          <?php foreach ($liens[$role] as $label => $url): ?>
            <li><a href="<?= $url ?>"><?= $label ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($role === 'aide_soignant'): ?>
      <div class="grid-3">
        <div class="card">
          <h3>Nombre de patients</h3>
          <p class="info"><?= nombrePatients($connexion) ?></p>
        </div>
        <div class="card">
          <h3>Prises aujourd'hui</h3>
          <p class="ok"><?= prisesAujourdhui($connexion) ?></p>
        </div>
        <div class="card">
          <h3>Alertes actives</h3>
          <p class="alert"><?= alertesActives($connexion) ?></p>
        </div>
      </div>
    <?php endif; ?>

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
