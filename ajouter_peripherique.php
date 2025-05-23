<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('admin');

$message = '';

// Récupérer les armoires existantes
$armoires = $connexion->query("SELECT IdArm, Localisation FROM Armoire")->fetch_all(MYSQLI_ASSOC);

// Ajouter un périphérique
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $etat = isset($_POST['etat']) ? 1 : 0;
    $bouton = trim($_POST['bouton'] ?? '');
    $idArm = intval($_POST['armoire'] ?? 0);
    $idxDomoticz = intval($_POST['idx'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($type && $idArm && $idxDomoticz) {
        $stmt = $connexion->prepare("INSERT INTO Peripherique (TypeP, Etat, Bouton, IdArm, IdxDomoticz, Description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisiss", $type, $etat, $bouton, $idArm, $idxDomoticz, $description);
        if ($stmt->execute()) {
            $message = "Périphérique ajouté avec succès.";
        } else {
            $message = "Erreur : " . $stmt->error;
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Ajouter un périphérique - SmartCare</title>
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
  <section class="card fade-in">
    <h2>Ajouter un périphérique Domoticz</h2>
    <?php if ($message): ?><p class="info"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <form method="post">
      <label>Type de périphérique :
        <input type="text" name="type" required placeholder="Ex : Capteur Température">
      </label>
      <label>Bouton / Action (facultatif) :
        <input type="text" name="bouton" placeholder="Ex : On/Off, Toggle, ...">
      </label>
      <label>Idx Domoticz :
        <input type="number" name="idx" required placeholder="Ex : 35">
      </label>
      <label>Description (facultatif) :
        <input type="text" name="description">
      </label>
      <label>Armoire liée :
        <select name="armoire" required>
          <option value="">-- Choisir --</option>
          <?php foreach ($armoires as $a): ?>
            <option value="<?= $a['IdArm'] ?>">Armoire<?= $a['IdArm'] ?> - <?= htmlspecialchars($a['Localisation']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <input type="checkbox" name="etat"> Actif par défaut
      </label>
      <button type="submit" class="btn-primary">Ajouter le périphérique</button>
    </form>
  </section>
</main>
<footer><p>&copy; 2025 SmartCare</p></footer>
</body>
</html>
