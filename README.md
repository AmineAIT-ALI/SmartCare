Rôle
Identifiant
Mot de passe

Patient
patient1
abcd

Famille
famille1
azerty

Aide-soignant
soignant1
1234

Ces utilisateurs sont pré-enregistrés dans la base de données avec un mot de passe hashé via password_hash().

Lancer le projet en local. Démarrage PHP (dans le dossier du projet)

php -S localhost:8001

Accès via navigateur : http://localhost:8001

Connexion à la base :

Modifiez includes/db.php selon votre configuration :

$connexion = new mysqli('localhost', 'root', '', 'smartcare');
