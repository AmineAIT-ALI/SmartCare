<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

$roles = ['patient' => 'P', 'aide_soignant' => 'A', 'membre_famille' => 'M'];
$message = '';
$utilisateur = [];

$role = $_GET['role'] ?? $_POST['role'] ?? '';
$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if (!isset($roles[$role]) || $id <= 0) {
    $message = "Paramètres manquants ou invalides.";
} else {
    verifierRole($_SESSION['role']);

    if ($role === 'aide_soignant' && $_SESSION['role'] !== 'admin') {
        header("Location: index.php");
        exit;
    }

    if ($_SESSION['role'] === 'aide_soignant') {
        $idU = $_SESSION['idU'];
        $stmtCheck = null;

        if ($role === 'patient') {
            $stmtCheck = $connexion->prepare("
                SELECT 1 FROM suivre s
                JOIN Aide_Soignant a ON a.IdA = s.IdA
                WHERE s.IdP = ? AND a.IdU = ?
            ");
        } elseif ($role === 'membre_famille') {
            $stmtCheck = $connexion->prepare("
                SELECT 1 FROM Membre_Famille mf
                JOIN Aide_Soignant a ON mf.IdA = a.IdA
                WHERE mf.IdM = ? AND a.IdU = ?
            ");
        }

        if ($stmtCheck) {
            $stmtCheck->bind_param("ii", $id, $idU);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            if ($resCheck->num_rows === 0) {
                die("<h2>Accès refusé</h2><p>Vous ne pouvez pas modifier cet utilisateur.</p>");
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = trim($_POST['login'] ?? '');
        $mdp = $_POST['mdp'] ?? '';
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $tel = trim($_POST['telephone'] ?? '');
        $mail = trim($_POST['mail'] ?? '');
        $adresse = $_POST['adresse'] ?? null;
        $date = $_POST['date_naissance'] ?? null;
        $lien = $_POST['lien_parente'] ?? null;

        $alias = $roles[$role];
        $params = [];

        if ($role === 'patient') {
            $stmt = $connexion->prepare("UPDATE Patient SET NomP=?, PrenomP=?, Date_naissance=?, TelephoneP=?, MailP=?, Adresse=? WHERE IdP=?");
            $params = [$nom, $prenom, $date, $tel, $mail, $adresse, $id];
        } elseif ($role === 'membre_famille') {
            $stmt = $connexion->prepare("UPDATE Membre_Famille SET NomM=?, PrenomM=?, TelephoneM=?, MailM=?, Lien_Parente=? WHERE IdM=?");
            $params = [$nom, $prenom, $tel, $mail, $lien, $id];
        } else {
            $stmt = $connexion->prepare("UPDATE Aide_Soignant SET NomA=?, PrenomA=?, TelephoneA=?, MailA=? WHERE IdA=?");
            $params = [$nom, $prenom, $tel, $mail, $id];
        }

        $stmt->bind_param(str_repeat('s', count($params) - 1) . 'i', ...$params);
        $ok = $stmt->execute();

        $table = $role === 'membre_famille' ? 'Membre_Famille' : ($role === 'aide_soignant' ? 'Aide_Soignant' : 'Patient');
        $stmtU = $connexion->prepare("UPDATE Utilisateur SET Login=?" . ($mdp ? ", Mdp=?" : "") . " WHERE IdU = (SELECT IdU FROM $table WHERE Id{$alias} = ?)");
        if ($mdp) {
            $mdp_hache = password_hash($mdp, PASSWORD_DEFAULT);
            $stmtU->bind_param("ssi", $login, $mdp_hache, $id);
        } else {
            $stmtU->bind_param("si", $login, $id);
        }
        $okU = $stmtU->execute();

        $message = ($ok && $okU) ? "Modification réussie." : "Erreur lors de la mise à jour.";
    }

    $alias = $roles[$role];
    $table = $role === 'membre_famille' ? 'Membre_Famille' : ($role === 'aide_soignant' ? 'Aide_Soignant' : 'Patient');
    $stmt = $connexion->prepare("SELECT * FROM $table WHERE Id$alias=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $utilisateur = $stmt->get_result()->fetch_assoc();

    $stmtL = $connexion->prepare("SELECT Login FROM Utilisateur WHERE IdU = ?");
    $stmtL->bind_param("i", $utilisateur["IdU"]);
    $stmtL->execute();
    $utilisateur["Login"] = $stmtL->get_result()->fetch_assoc()['Login'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier un utilisateur</title>
  <link rel="stylesheet" href="styles.css">
  <script>
    function toggleFields() {
      const role = '<?= $role ?>';
      document.getElementById('date-field').style.display = (role === 'patient') ? 'block' : 'none';
      document.getElementById('adresse-field').style.display = (role === 'patient') ? 'block' : 'none';
      document.getElementById('lien-field').style.display = (role === 'membre_famille') ? 'block' : 'none';
    }
  </script>
</head>
<body onload="toggleFields()">

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
    <h2>Modifier un utilisateur</h2>
    <p><?= htmlspecialchars($message) ?></p>

    <?php if ($utilisateur): ?>
    <form method="post">
      <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
      <input type="hidden" name="id" value="<?= $id ?>">

      <label>Nom : <input type="text" name="nom" value="<?= htmlspecialchars($utilisateur["Nom{$roles[$role]}"]) ?>" required></label>
      <label>Prénom : <input type="text" name="prenom" value="<?= htmlspecialchars($utilisateur["Prenom{$roles[$role]}"]) ?>" required></label>

      <div id="date-field">
        <label>Date de naissance : <input type="date" name="date_naissance" value="<?= htmlspecialchars($utilisateur['Date_naissance'] ?? '') ?>"></label>
      </div>

      <label>Téléphone : <input type="text" name="telephone" value="<?= htmlspecialchars($utilisateur["Telephone{$roles[$role]}"]) ?>" required></label>
      <label>Email : <input type="email" name="mail" value="<?= htmlspecialchars($utilisateur["Mail{$roles[$role]}"]) ?>" required></label>

      <div id="adresse-field">
        <label>Adresse : <input type="text" name="adresse" value="<?= htmlspecialchars($utilisateur['Adresse'] ?? '') ?>"></label>
      </div>

      <div id="lien-field">
        <label>Lien de parenté : <input type="text" name="lien_parente" value="<?= htmlspecialchars($utilisateur['Lien_Parente'] ?? '') ?>"></label>
      </div>

      <label>Login : <input type="text" name="login" value="<?= htmlspecialchars($utilisateur["Login"] ?? '') ?>" required></label>
      <label>Nouveau mot de passe : <input type="password" name="mdp" placeholder="Laisser vide pour conserver"></label>

      <div style="display: flex; gap: 10px; margin-top: 15px;">
        <button type="submit" class="btn-primary" style="flex:1; text-align:center;">Mettre à jour</button>
        <?php if ($_SESSION['role'] === 'admin' && $role === 'aide_soignant'): ?>
        <a href="admin_aide_soignant.php" class="btn-primary" style="flex:1; text-align:center;">Retour</a>
        <?php endif; ?>
      </div>
    </form>
    <?php endif; ?>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>
</body>
</html>
