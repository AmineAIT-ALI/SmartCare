<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

$role = $_GET['role'] ?? '';
$id = intval($_GET['id'] ?? 0);
$nom = $_GET['nom'] ?? '';
$prenom = $_GET['prenom'] ?? '';

if (!$role || $id <= 0) {
    die("<h2>Paramètres invalides</h2>");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Confirmer la suppression</title>
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
    <h2>Confirmation</h2>
    <p>Souhaitez-vous vraiment supprimer l'utilisateur suivant ?</p>
    <ul>
      <li><strong>Nom :</strong> <?= htmlspecialchars($nom) ?></li>
      <li><strong>Prénom :</strong> <?= htmlspecialchars($prenom) ?></li>
      <li><strong>Rôle :</strong> <?= htmlspecialchars($role) ?></li>
    </ul>

    <form method="get" action="supprimer_utilisateur.php">
      <input type="hidden" name="id" value="<?= $id ?>">
      <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
      <button type="submit" class="btn alert">Oui, supprimer</button>
      <a href="liste_utilisateurs.php" class="btn-primary">Annuler</a>
    </form>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
