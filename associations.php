<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

if ($_SESSION['role'] !== 'aide_soignant') {
    header("Location: index.php");
    exit;
}

$idUtilisateur = $_SESSION['idU'] ?? 0;

// Récupération de l'IdA du soignant connecté
$stmtIdA = $connexion->prepare("SELECT IdA FROM Aide_Soignant WHERE IdU = ?");
$stmtIdA->bind_param("i", $idUtilisateur);
$stmtIdA->execute();
$resIdA = $stmtIdA->get_result();
$idA = $resIdA->num_rows > 0 ? $resIdA->fetch_assoc()['IdA'] : 0;

// Récupération des associations
$stmt = $connexion->prepare("
  SELECT p.NomP, p.PrenomP, m.NomM, m.PrenomM, m.MailM, m.TelephoneM, a.Lien_Parente
  FROM suivre s
  JOIN Patient p ON s.IdP = p.IdP
  JOIN associe a ON a.IdP = p.IdP
  JOIN Membre_Famille m ON a.IdM = m.IdM
  WHERE s.IdA = ?
  ORDER BY p.NomP, m.NomM
");
$stmt->bind_param("i", $idA);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Associations Patient-Famille - SmartCare</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    tr.highlight { background-color: #e8f4ff; border-left: 4px solid #2196f3; }
  </style>
  <script>
    function filtrerTableau() {
      const filtre = document.getElementById("filtre").value.toLowerCase();
      document.querySelectorAll("table tbody tr").forEach(row => {
        const texte = row.innerText.toLowerCase();
        row.style.display = texte.includes(filtre) ? "" : "none";
        row.classList.toggle("highlight", texte.includes(filtre) && filtre !== "");
      });
    }

    document.addEventListener("keydown", function(e) {
      if (e.key === "Enter") {
        const ligne = Array.from(document.querySelectorAll("table tbody tr"))
          .find(row => row.style.display !== "none");
        if (ligne) {
          ligne.scrollIntoView({ behavior: "smooth", block: "center" });
          ligne.classList.add("highlight");
          setTimeout(() => ligne.classList.remove("highlight"), 2000);
          e.preventDefault();
        }
      }
    });
  </script>
</head>
<body>

<header>
  <h1>SmartCare</h1>
  <nav>
    <ul>
      <li><a href="dashboard.php">Tableau de bord</a></li>
      <li><a href="associer_famille.php">Associer Famille</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="card fade-in">
    <h2>Associations entre patients et familles</h2>
    <input type="text" id="filtre" onkeyup="filtrerTableau()" placeholder="Rechercher un patient ou un membre..." style="width:100%;padding:8px;margin-bottom:10px;">

    <?php if ($res->num_rows === 0): ?>
      <p>Aucune association trouvée.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Membre de la famille</th>
            <th>Lien</th>
            <th>Email</th>
            <th>Téléphone</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['PrenomP'] . ' ' . $row['NomP']) ?></td>
              <td><?= htmlspecialchars($row['PrenomM'] . ' ' . $row['NomM']) ?></td>
              <td><?= htmlspecialchars($row['Lien_Parente']) ?></td>
              <td><a href="mailto:<?= htmlspecialchars($row['MailM']) ?>"><?= htmlspecialchars($row['MailM']) ?></a></td>
              <td><?= htmlspecialchars($row['TelephoneM']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</main>

<footer>
  <p>&copy; 2025 SmartCare - Tous droits réservés.</p>
</footer>

</body>
</html>
