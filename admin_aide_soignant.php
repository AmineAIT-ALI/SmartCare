<?php
require_once 'includes/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

// Suppression
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $idA = intval($_GET['delete']);
    $stmt = $connexion->prepare("SELECT IdU FROM Aide_Soignant WHERE IdA = ?");
    $stmt->bind_param("i", $idA);
    $stmt->execute();
    $res = $stmt->get_result();
    $soignant = $res->fetch_assoc();

    if ($soignant) {
        $idU = $soignant['IdU'];

        $stmt = $connexion->prepare("SELECT IdAlerte FROM Alerte WHERE IdA = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();
        $alertes = $stmt->get_result();

        $delRecevoir = $connexion->prepare("DELETE FROM recevoir WHERE IdAlerte = ?");
        $delDeclenche = $connexion->prepare("DELETE FROM declenche WHERE IdAlerte = ?");
        $delEmettre = $connexion->prepare("DELETE FROM emettre WHERE IdAlerte = ?");

        while ($alerte = $alertes->fetch_assoc()) {
            $idAlerte = $alerte['IdAlerte'];

            $delRecevoir->bind_param("i", $idAlerte);
            $delRecevoir->execute();

            $delDeclenche->bind_param("i", $idAlerte);
            $delDeclenche->execute();

            $delEmettre->bind_param("i", $idAlerte);
            $delEmettre->execute();
        }

        $stmt = $connexion->prepare("DELETE FROM Alerte WHERE IdA = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();

        $stmt = $connexion->prepare("DELETE FROM suivre WHERE IdA = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();

        $stmt = $connexion->prepare("DELETE FROM Aide_Soignant WHERE IdA = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();

        $stmt = $connexion->prepare("DELETE FROM Utilisateur WHERE IdU = ?");
        $stmt->bind_param("i", $idU);
        $stmt->execute();

        $message = "Aide-soignant et toutes les données liées supprimés.";
    }
}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = $_POST['nom']; $prenom = $_POST['prenom']; $tel = $_POST['telephone'];
    $mail = $_POST['mail']; $login = $_POST['login']; $mdp = $_POST['mdp'];

    if ($nom && $prenom && $tel && $mail && $login && $mdp) {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $stmtU = $connexion->prepare("INSERT INTO Utilisateur (Login, Mdp, Role) VALUES (?, ?, 'aide_soignant')");
        $stmtU->bind_param("ss", $login, $hash);
        $stmtU->execute();
        $idU = $connexion->insert_id;

        $stmt = $connexion->prepare("INSERT INTO Aide_Soignant (NomA, PrenomA, TelephoneA, MailA, IdU) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $nom, $prenom, $tel, $mail, $idU);
        $stmt->execute();
        $message = "Aide-soignant ajouté.";
    } else {
        $message = "Tous les champs sont requis.";
    }
}

// Affichage
$res = $connexion->query("SELECT A.IdA, A.NomA, A.PrenomA, U.Login, A.MailA
                          FROM Aide_Soignant A
                          JOIN Utilisateur U ON A.IdU = U.IdU
                          WHERE U.Role = 'aide_soignant'");
?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Admin - Aides-Soignants</title>
  <link rel="stylesheet" href="styles.css">
  <script>
    function filtrerTableau() {
      let input = document.getElementById("filtre");
      let filter = input.value.toLowerCase();
      let rows = document.querySelectorAll("table tbody tr");
      rows.forEach(row => {
        let texte = row.innerText.toLowerCase();
        row.style.display = texte.includes(filter) ? "" : "none";
      });
    }
  </script>
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
    <h2>Créer un Aide-Soignant</h2>
    <?php if ($message): ?><p class="info"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <form method="post">
      <label>Nom : <input type="text" name="nom" required></label>
      <label>Prénom : <input type="text" name="prenom" required></label>
      <label>Téléphone : <input type="text" name="telephone" required></label>
      <label>Email : <input type="email" name="mail" required></label>
      <label>Login : <input type="text" name="login" required></label>
      <label>Mot de passe : <input type="password" name="mdp" required></label>
      <button type="submit" name="ajouter" class="btn-primary">Ajouter</button>
    </form>
  </section>

  <section class="card slide-in-right">
    <h2>Liste des Aides-Soignants</h2>
    <input type="text" id="filtre" onkeyup="filtrerTableau()" placeholder="Rechercher un soignant..." style="width:100%;padding:8px;margin-bottom:10px;">
    <table>
      <thead>
        <tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Login</th><th>Email</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php while ($a = $res->fetch_assoc()): ?>
        <tr>
          <td><?= $a['IdA'] ?></td>
          <td><?= htmlspecialchars($a['NomA']) ?></td>
          <td><?= htmlspecialchars($a['PrenomA']) ?></td>
          <td><?= htmlspecialchars($a['Login']) ?></td>
          <td><?= htmlspecialchars($a['MailA']) ?></td>
          <td>
            <a href="modifier_utilisateur.php?role=aide_soignant&id=<?= $a['IdA'] ?>" class="btn-primary">Modifier</a>
            <a href="?delete=<?= $a['IdA'] ?>" class="btn-primary" onclick="return confirm('Supprimer ce soignant ?')">Supprimer</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
