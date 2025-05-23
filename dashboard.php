<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

$role = $_SESSION['role'] ?? 'guest';
$nom_utilisateur = $_SESSION['utilisateur'] ?? 'Anonyme';
$idUtilisateur = $_SESSION['idU'] ?? 0;

$nomRole = [
  'patient' => 'Patient',
  'membre_famille' => 'Famille',
  'aide_soignant' => 'Soignant',
  'admin' => 'Administrateur',
  'guest' => 'Invité'
];

$liens = [
  'patient' => [
    'Accueil' => 'index.php',
    'Ma prise du jour' => 'prise_du_jour.php',
    'Mon historique' => 'historique_patient.php',
    'Supervision' => 'supervision.php'
  ],
  'membre_famille' => [
    'Accueil' => 'index.php',
    'Suivi des proches' => 'famille.php',
    'Alertes' => 'alertes.php',
    'Supervision' => 'supervision.php'
  ],
  'aide_soignant' => [
    'Accueil' => 'index.php',
    'Historique global' => 'historique_soignant.php',
    'Alertes' => 'alertes.php',
    'Gestion des utilisateurs' => 'liste_utilisateurs.php',
    'Associer familles' => 'associer_famille.php',
    'Supervision' => 'supervision.php'
  ],
  'admin' => [
    'Accueil' => 'index.php',
    'Gestion des soignants' => 'admin_aide_soignant.php',
    'Ajouter une armoire' => 'ajouter_armoire.php',
    'Ajouter un périphérique' => 'ajouter_peripherique.php',
    'Lier une armoire' => 'lier_armoire.php'
  ]
];

function fetchCount($conn, $query) {
  $res = $conn->query($query);
  return $res ? $res->fetch_row()[0] : '-';
}

$idAideSoignant = 0;
if ($role === 'aide_soignant') {
  $stmt = $connexion->prepare("SELECT IdA FROM Aide_Soignant WHERE IdU = ?");
  $stmt->bind_param("i", $idUtilisateur);
  $stmt->execute();
  $res = $stmt->get_result();
  $idAideSoignant = $res->num_rows > 0 ? $res->fetch_assoc()['IdA'] : 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tableau de bord - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <?php foreach ($liens[$role] ?? [] as $label => $url): ?>
        <li><a href="<?= $url ?>"><?= $label ?></a></li>
      <?php endforeach; ?>
      <?php if ($role !== 'guest'): ?>
        <li><a href="logout.php">Déconnexion</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>

<main>
  <section class="card fade-in">
    <h2>Bienvenue <?= $nomRole[$role] ?> sur SmartCare</h2>
    <p>Connecté en tant que <strong><?= htmlspecialchars($nom_utilisateur) ?></strong> (<?= $nomRole[$role] ?>)</p>
  </section>

  <?php if ($role === 'aide_soignant'): ?>
    <div class="grid-3">
      <div class="card">
        <h3>Nombre de patients</h3>
        <p class="info"><?= fetchCount($connexion, "SELECT COUNT(*) FROM suivre WHERE IdA = $idAideSoignant") ?></p>
      </div>
      <div class="card">
        <h3>Prises aujourd'hui</h3>
        <p class="ok"><?= fetchCount($connexion, "
          SELECT COUNT(*) FROM Prise_Medicament pm
          JOIN suivre s ON s.IdP = pm.IdP
          WHERE DATE(pm.HeurePrise) = CURDATE() AND s.IdA = $idAideSoignant
        ") ?></p>
      </div>
      <div class="card">
        <h3>Alertes actives</h3>
        <p class="alert"><?= fetchCount($connexion, "
          SELECT COUNT(*) FROM Prise_Medicament pm
          JOIN suivre s ON s.IdP = pm.IdP
          WHERE pm.Confirme = 0 AND DATE(pm.HeurePrise) = CURDATE() AND s.IdA = $idAideSoignant
        ") ?></p>
      </div>
    </div>

    <section class="card fade-in">
      <h2>Vos patients & familles associées</h2>
      <?php
      $stmtP = $connexion->prepare("
        SELECT p.IdP, p.NomP, p.PrenomP FROM suivre s
        JOIN Patient p ON p.IdP = s.IdP
        WHERE s.IdA = ?
      ");
      $stmtP->bind_param("i", $idAideSoignant);
      $stmtP->execute();
      $patients = $stmtP->get_result();

      $stmtF = $connexion->prepare("
        SELECT m.PrenomM, m.NomM, m.MailM, m.TelephoneM, a.Lien_Parente
        FROM associe a
        JOIN Membre_Famille m ON m.IdM = a.IdM
        WHERE a.IdP = ?
      ");

      if ($patients->num_rows === 0) {
        echo "<p>Aucun patient suivi actuellement.</p>";
      } else {
        while ($p = $patients->fetch_assoc()) {
          echo "<div class='card'>";
          echo "<h3>" . htmlspecialchars($p['PrenomP'] . ' ' . $p['NomP']) . "</h3>";

          $stmtF->bind_param("i", $p['IdP']);
          $stmtF->execute();
          $familles = $stmtF->get_result();

          if ($familles->num_rows > 0) {
            echo "<ul>";
            while ($f = $familles->fetch_assoc()) {
              echo "<li>" . htmlspecialchars("{$f['PrenomM']} {$f['NomM']}") . " – " .
                   htmlspecialchars($f['Lien_Parente']) . " – " .
                   "<a href='mailto:" . htmlspecialchars($f['MailM']) . "'>" . htmlspecialchars($f['MailM']) . "</a> – " .
                   htmlspecialchars($f['TelephoneM']) . "</li>";
            }
            echo "</ul>";
          } else {
            echo "<p>Aucun membre de famille associé.</p>";
          }
          echo "</div>";
        }
        echo '<div style="text-align:right; margin-top:10px;"><a href="associations.php" class="btn-primary">Voir plus</a></div>';
      }
      ?>
    </section>
  <?php endif; ?>

  <section class="grid-3 slide-in-right">
    <article class="card"><h3>Vision & Impact</h3><p><strong>SmartCare</strong> transforme le suivi médicamenteux en EHPAD grâce à une solution connectée fiable et centrée sur la sécurité du patient.</p></article>
    <article class="card"><h3>Soin Humain Augmenté</h3><p>La technologie au service des soignants pour préserver la dignité des patients. Un outil éthique et intuitif.</p></article>
    <article class="card"><h3>Pérennité & Croissance</h3><p>Interopérabilité, coût maîtrisé, ouverture à l’innovation : une solution durable pensée pour le terrain.</p></article>
    <article class="card"><h3>Prêt à l’Usage</h3><p>Déploiement rapide, interface simple, compatibilité Domoticz et Raspberry Pi : tout est prêt pour les établissements.</p></article>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
