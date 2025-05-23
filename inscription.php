<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

$roles_autorises = ['patient', 'aide_soignant', 'membre_famille'];
$erreur = '';
$confirmation = $_GET['msg'] ?? '';

// Armoires libres
$armoires = $connexion->query("SELECT IdArm FROM Armoire WHERE IdP IS NULL")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    if (!in_array($role, $roles_autorises)) {
        $erreur = "Rôle invalide.";
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $mail = trim($_POST['mail'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $mdp = $_POST['mdp'] ?? '';
        $mdp_hache = password_hash($mdp, PASSWORD_DEFAULT);

        if (!$nom || !$prenom || !$telephone || !$mail || !$login || !$mdp) {
            $erreur = "Tous les champs obligatoires doivent être remplis.";
        } else {
            $check = $connexion->prepare("SELECT IdU FROM Utilisateur WHERE Login = ?");
            $check->bind_param("s", $login);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $erreur = "Ce login est déjà utilisé. Veuillez en choisir un autre.";
            } else {
                $stmtU = $connexion->prepare("INSERT INTO Utilisateur (Login, Mdp, Role) VALUES (?, ?, ?)");
                $stmtU->bind_param("sss", $login, $mdp_hache, $role);
                $stmtU->execute();
                $idU = $connexion->insert_id;

                if ($role === 'patient') {
                    $date = $_POST['date_naissance'] ?? '';
                    $adresse = $_POST['adresse'] ?? '';
                    $idArm = intval($_POST['armoire'] ?? 0);

                    if (!$date || !$adresse || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        $erreur = "La date de naissance et l'adresse sont obligatoires.";
                    } else {
                        $stmt = $connexion->prepare("INSERT INTO Patient (NomP, PrenomP, Date_naissance, TelephoneP, MailP, Adresse, IdU) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssi", $nom, $prenom, $date, $telephone, $mail, $adresse, $idU);
                        if ($stmt->execute()) {
                            $idP = $stmt->insert_id;

                            $stmtS = $connexion->prepare("SELECT IdA FROM Aide_Soignant WHERE IdU = ?");
                            $stmtS->bind_param("i", $_SESSION['idU']);
                            $stmtS->execute();
                            $idA = $stmtS->get_result()->fetch_assoc()['IdA'];

                            $connexion->query("INSERT INTO suivre (IdA, IdP) VALUES ($idA, $idP)");

                            if ($idArm > 0) {
                                $stmtA = $connexion->prepare("UPDATE Armoire SET IdP = ?, IdA = ?, Localisation = ? WHERE IdArm = ?");
                                $stmtA->bind_param("iisi", $idP, $idA, $adresse, $idArm);
                                $stmtA->execute();
                            }

                            header("Location: inscription.php?msg=Patient ajouté" . ($idArm ? " avec armoire." : " sans armoire."));
                            exit;
                        } else {
                            $erreur = "Erreur : " . $stmt->error;
                        }
                    }
                } elseif ($role === 'aide_soignant') {
                    $stmt = $connexion->prepare("INSERT INTO Aide_Soignant (NomA, PrenomA, TelephoneA, MailA, IdU) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssi", $nom, $prenom, $telephone, $mail, $idU);
                    $stmt->execute() ? header("Location: inscription.php?msg=Aide-soignant ajouté.") : $erreur = "Erreur : " . $stmt->error;
                    exit;
                } elseif ($role === 'membre_famille') {
                    $idA = $connexion->query("SELECT IdA FROM Aide_Soignant WHERE IdU = {$_SESSION['idU']}")->fetch_assoc()['IdA'];
                    $stmt = $connexion->prepare("INSERT INTO Membre_Famille (NomM, PrenomM, TelephoneM, MailM, IdU, IdA) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssii", $nom, $prenom, $telephone, $mail, $idU, $idA);
                    $stmt->execute() ? header("Location: inscription.php?msg=Membre de famille ajouté.") : $erreur = "Erreur : " . $stmt->error;
                    exit;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscription - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
  <script>
    function toggleFields() {
      const role = document.querySelector('select[name="role"]').value;
      document.getElementById('patient-fields').style.display = role === 'patient' ? 'block' : 'none';
    }
  </script>
</head>
<body onload="toggleFields()">
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
    <h2>Créer un utilisateur</h2>
    <?php if ($erreur): ?><p class="alert"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <?php if ($confirmation): ?><p class="ok"><?= htmlspecialchars($confirmation) ?></p><?php endif; ?>

    <form method="post">
      <label>Rôle :
        <select name="role" onchange="toggleFields()" required>
          <option value="patient">Patient</option>
          <option value="aide_soignant">Aide-soignant</option>
          <option value="membre_famille">Membre de la famille</option>
        </select>
      </label>

      <label>Nom : <input type="text" name="nom" required></label>
      <label>Prénom : <input type="text" name="prenom" required></label>
      <label>Téléphone : <input type="text" name="telephone" required></label>
      <label>Email : <input type="email" name="mail" required></label>

      <div id="patient-fields" style="display:none;">
        <label>Date de naissance : <input type="date" name="date_naissance"></label>
        <label>Adresse : <input type="text" name="adresse"></label>
        <label>Associer une armoire ?
          <select name="armoire">
            <option value="0">Non</option>
            <?php foreach ($armoires as $a): ?>
              <option value="<?= $a['IdArm'] ?>">Armoire<?= $a['IdArm'] ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <label>Identifiant : <input type="text" name="login" required></label>
      <label>Mot de passe : <input type="password" name="mdp" required></label>

      <button type="submit" class="btn-primary">Créer</button>
    </form>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare</p>
</footer>
</body>
</html>
