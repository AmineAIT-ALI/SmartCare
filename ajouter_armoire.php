<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('admin');

$message = '';

// Suppression d'une armoire
if (isset($_GET['supprimer'])) {
    $idArm = intval($_GET['supprimer']);
    $stmt = $connexion->prepare("DELETE FROM Armoire WHERE IdArm = ? AND IdP IS NULL");
    $stmt->bind_param("i", $idArm);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Armoire supprimée.";
    } else {
        $message = "Suppression impossible : armoire liée ou introuvable.";
    }
}

// Création d'une armoire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $connexion->prepare("INSERT INTO Armoire (Localisation) VALUES ('')");
    if ($stmt->execute()) {
        $idArm = $connexion->insert_id;
        $nomAuto = "Armoire" . $idArm;

        $stmt2 = $connexion->prepare("UPDATE Armoire SET Localisation = ? WHERE IdArm = ?");
        $stmt2->bind_param("si", $nomAuto, $idArm);
        $stmt2->execute();

        header("Location: ajouter_armoire.php?msg=" . urlencode("Armoire ajoutée avec succès : $nomAuto"));
        exit;
    } else {
        $message = "Erreur : " . $stmt->error;
    }
}

// Armoires disponibles
$liste = $connexion->query("SELECT IdArm, Localisation, IdP FROM Armoire")->fetch_all(MYSQLI_ASSOC);
$message = $_GET['msg'] ?? $message;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Ajouter une armoire - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
  <h1>SmartCare - Administration</h1>
  <nav>
    <ul>
      <li><a href="dashboard.php">Tableau de bord</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>
<main>
  <section class="card">
    <h2>Ajouter une armoire</h2>
    <?php if ($message): ?><p class="info"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <form method="post">
      <p>Cette opération créera une nouvelle armoire nommée automatiquement.</p>
      <button type="submit" class="btn-primary">Créer une armoire</button>
    </form>
  </section>

  <section class="card">
    <h2>Liste des armoires</h2>
    <table>
      <thead><tr><th>ID</th><th>Nom</th><th>Statut</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($liste as $a): ?>
        <tr>
          <td><?= $a['IdArm'] ?></td>
          <td><?= htmlspecialchars($a['Localisation']) ?></td>
          <td><?= $a['IdP'] ? "Affectée" : "Libre" ?></td>
          <td>
            <?php if (!$a['IdP']): ?>
              <a href="?supprimer=<?= $a['IdArm'] ?>" onclick="return confirm('Supprimer cette armoire ?')" class="btn-primary alert">Supprimer</a>
            <?php else: ?>
              <em>Non supprimable</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>
<footer><p>&copy; 2025 SmartCare</p></footer>
</body>
</html>
