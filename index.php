<?php
session_start();
$connecte = isset($_SESSION['role']) && $_SESSION['role'] !== 'guest';
$nom_utilisateur = $_SESSION['utilisateur'] ?? 'Anonyme';
$role = $_SESSION['role'] ?? 'guest';

$nomRole = [
  'patient' => 'Patient',
  'membre_famille' => 'Famille',
  'aide_soignant' => 'Soignant',
  'admin' => 'Administrateur',
  'guest' => 'Invité'
];

$liens = [
  'patient' => [
    'Tableau de bord' => 'dashboard.php',
    'Ma prise du jour' => 'prise_du_jour.php',
    'Mon historique' => 'historique_patient.php',
    'Supervision' => 'supervision.php'
  ],
  'membre_famille' => [
    'Tableau de bord' => 'dashboard.php',
    'Suivi des proches' => 'famille.php',
    'Alertes' => 'alertes.php',
    'Supervision' => 'supervision.php'
  ],
  'aide_soignant' => [
    'Tableau de bord' => 'dashboard.php',
    'Historique global' => 'historique_soignant.php',
    'Alertes' => 'alertes.php',
    'Gestion des utilisateurs' => 'liste_utilisateurs.php',
    'Associer familles' => 'associer_famille.php',
    'Supervision' => 'supervision.php'
  ],
  'admin' => [
    'Tableau de bord' => 'dashboard.php',
    'Gestion des soignants' => 'admin_aide_soignant.php',
    'Ajouter une armoire' => 'ajouter_armoire.php',
    'Ajouter un périphérique' => 'ajouter_peripherique.php',
    'Lier une armoire' => 'lier_armoire.php'
  ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accueil - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<header>
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
    <h1 style="margin: 0;">SmartCare</h1>
  </div>
  <nav>
    <ul class="horizontal-menu">
      <?php if ($connecte): ?>
        <?php foreach ($liens[$role] ?? [] as $label => $url): ?>
          <li><a href="<?= $url ?>"><?= htmlspecialchars($label) ?></a></li>
        <?php endforeach; ?>
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
      <p>Bienvenue <strong><?= htmlspecialchars($nom_utilisateur) ?></strong>, vous êtes connecté en tant que <strong><?= $nomRole[$role] ?? 'Invité' ?></strong>.</p>
    <?php endif; ?>
  </section>

  <section class="grid-3 slide-in-right">
    <article class="card">
      <h3>Vision & Impact</h3>
      <p><strong>SmartCare</strong> est une initiative MIAGE Toulouse qui vise à transformer le suivi médicamenteux en EHPAD grâce à une solution connectée fiable et centrée sur la sécurité du patient.</p>
    </article>

    <article class="card">
      <h3>Soin Humain Augmenté</h3>
      <p>SmartCare valorise le rôle des soignants tout en préservant la dignité des patients. Une technologie éthique, inclusive, pensée pour le quotidien médical.</p>
    </article>

    <article class="card">
      <h3>Pérennité & Croissance</h3>
      <p>Interopérabilité, coûts maîtrisés, financements publics/privés : une solution durable prête à se diffuser à grande échelle dans le secteur de la santé.</p>
    </article>

    <article class="card">
      <h3>Prêt à l’Usage</h3>
      <p>Compatibilité Domoticz, Raspberry Pi, installation rapide : SmartCare s’intègre aisément dans tous les établissements de santé sans friction.</p>
    </article>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
