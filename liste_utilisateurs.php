<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
verifierRole('aide_soignant');

$idUtilisateur = $_SESSION['idU'] ?? 0;

// Récupérer l'IdA du soignant connecté
$idAideSoignant = 0;
$stmt = $connexion->prepare("SELECT IdA FROM Aide_Soignant WHERE IdU = ?");
$stmt->bind_param("i", $idUtilisateur);
$stmt->execute();
$res = $stmt->get_result();
$idAideSoignant = $res->num_rows > 0 ? $res->fetch_assoc()['IdA'] : 0;

// Patients créés par ce soignant
$patients = $connexion->prepare("
  SELECT p.IdP AS id, p.NomP AS nom, p.PrenomP AS prenom, 'patient' AS role,
         CONCAT(a.PrenomA, ' ', a.NomA) AS createur
  FROM Patient p
  JOIN suivre s ON p.IdP = s.IdP
  JOIN Aide_Soignant a ON s.IdA = a.IdA
  WHERE s.IdA = ?
");
$patients->bind_param("i", $idAideSoignant);
$patients->execute();
$patients = $patients->get_result()->fetch_all(MYSQLI_ASSOC);

// Familles créées par ce soignant
$familles = $connexion->prepare("
  SELECT m.IdM AS id, m.NomM AS nom, m.PrenomM AS prenom, 'membre_famille' AS role,
         CONCAT(a.PrenomA, ' ', a.NomA) AS createur
  FROM Membre_Famille m
  JOIN Aide_Soignant a ON m.IdA = a.IdA
  WHERE m.IdA = ?
");
$familles->bind_param("i", $idAideSoignant);
$familles->execute();
$familles = $familles->get_result()->fetch_all(MYSQLI_ASSOC);

// Soignant actuel uniquement
$soignants = $connexion->prepare("
  SELECT IdA AS id, NomA AS nom, PrenomA AS prenom, 'aide_soignant' AS role, NULL AS createur
  FROM Aide_Soignant
  WHERE IdA = ?
");
$soignants->bind_param("i", $idAideSoignant);
$soignants->execute();
$soignants = $soignants->get_result()->fetch_all(MYSQLI_ASSOC);

// Fusionner et trier
$utilisateurs = array_merge($patients, $soignants, $familles);
usort($utilisateurs, fn($a, $b) => strcmp($a['nom'], $b['nom']));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des utilisateurs - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
  <script>
    function filtrerUtilisateurs() {
      const filtre = document.getElementById("filtre").value.toLowerCase();
      const lignes = document.querySelectorAll("table tbody tr");
      lignes.forEach(ligne => {
        const texte = ligne.innerText.toLowerCase();
        ligne.style.display = texte.includes(filtre) ? "" : "none";
      });
    }
  </script>
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
    <h2>Gestion des Utilisateurs</h2>
    <p>Consultez, modifiez ou supprimez les profils enregistrés.</p>

    <a href="inscription.php" class="btn-primary">Ajouter un utilisateur</a>

    <input type="text" id="filtre" onkeyup="filtrerUtilisateurs()" placeholder="Rechercher un utilisateur..." style="width:100%;padding:8px;margin:15px 0;">

    <table>
      <thead>
        <tr>
          <th>Nom</th>
          <th>Prénom</th>
          <th>Rôle</th>
          <th>Créé par</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilisateurs as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['nom']) ?></td>
            <td><?= htmlspecialchars($u['prenom']) ?></td>
            <td><?= htmlspecialchars($u['role']) ?></td>
            <td><?= htmlspecialchars($u['createur'] ?? '-') ?></td>
            <td>
              <a href="modifier_utilisateur.php?role=<?= urlencode($u['role']) ?>&id=<?= $u['id'] ?>" class="btn-primary">Modifier</a>
              <a href="confirmer_suppression.php?role=<?= urlencode($u['role']) ?>&id=<?= $u['id'] ?>&nom=<?= urlencode($u['nom']) ?>&prenom=<?= urlencode($u['prenom']) ?>" class="btn-primary alert" onclick="return confirm('Confirmer la suppression ?')">Supprimer</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
