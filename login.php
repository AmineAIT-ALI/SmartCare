<?php
session_start();
require_once 'includes/db.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = htmlspecialchars(trim($_POST['login'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $connexion->prepare("SELECT * FROM Utilisateur WHERE Login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user && password_verify($password, $user['Mdp'])) {
        $_SESSION['utilisateur'] = $user['Login'];
        $_SESSION['role'] = $user['Role'];
        $_SESSION['idU'] = $user['IdU'];
        $_SESSION['is_admin'] = ($user['Role'] === 'admin');

        // Redirection vers un tableau de bord unique
        header('Location: dashboard.php');
        exit;
    } else {
        $erreur = "Identifiants incorrects.";
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <li><a href="index.php">Accueil</a></li>
      <li><a href="login.php" class="active">Connexion</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="card fade-in">
    <h2>Connexion à votre espace</h2>
    <p>Merci de vous authentifier pour accéder à votre espace personnalisé.</p>

    <?php if ($erreur): ?>
      <p class="alert"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form method="post" action="">
      <label for="login">Identifiant :</label>
      <input type="text" id="login" name="login" required>

      <label for="password">Mot de passe :</label>
      <input type="password" id="password" name="password" required>

      <button type="submit" class="btn-primary">Se connecter</button>
    </form>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
