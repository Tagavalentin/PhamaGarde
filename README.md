# PharmaGarde

PharmaGarde est une application web et mobile qui permet de localiser les pharmacies ouvertes ou de garde et de vérifier la disponibilité d'un médicament avant de se déplacer. Développée pour le contexte du Togo, optimisée pour l'usage en urgence et les connexions faibles.

## Stack Technique

- **Backend** : Laravel 12, PHP 8.3, API REST, Laravel Sanctum
- **Base de données** : MySQL ou PostgreSQL (index sur latitude, longitude, nom de médicament)
- **Frontend Web** : Vue.js 3 (Composition API) + Inertia.js + Tailwind CSS, Vite
- **Carte** : Leaflet + OpenStreetMap
- **Cache** : Cache Laravel

## Rôles

- **Visiteur** : Sans compte, peut rechercher des médicaments et pharmacies
- **Utilisateur** : Peut créer des favoris et des réservations
- **Pharmacien** : Peut gérer sa fiche pharmacie et son stock (après validation par l'admin)
- **Administrateur** : Peut valider les pharmacies et gérer les signalements

## Installation Locale

### Prérequis

- PHP 8.3 ou supérieur
- Composer
- Node.js et npm
- MySQL ou PostgreSQL

### Étapes

1. Cloner le dépôt :
```bash
git clone https://github.com/votre-username/pharmagarde.git
cd pharmagarde
```

2. Installer les dépendances PHP :
```bash
composer install
```

3. Installer les dépendances JavaScript :
```bash
npm install
```

4. Configurer l'environnement :
```bash
cp .env.example .env
php artisan key:generate
```

5. Configurer la base de données dans le fichier `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pharmagarde
DB_USERNAME=root
DB_PASSWORD=
```

6. Exécuter les migrations et les seeders :
```bash
php artisan migrate --seed
```

7. Compiler les assets :
```bash
npm run build
```

8. Démarrer le serveur de développement :
```bash
php artisan serve
```

L'application sera accessible sur `http://localhost:8000`.

## Comptes de Test

Les comptes suivants sont créés automatiquement lors du seeding :

### Administrateur
- **Email** : admin@pharmagarde.tg
- **Mot de passe** : password
- **Rôle** : admin

### Utilisateur
- **Email** : user@pharmagarde.tg
- **Mot de passe** : password
- **Rôle** : user

### Pharmacien
- **Email** : pharmacist@pharmagarde.tg
- **Mot de passe** : password
- **Rôle** : pharmacist
- **Pharmacie** : Pharmacie Centrale Lomé (validée)

## API Endpoints

### Authentification

- `POST /api/pharma/register` - Inscription d'un nouvel utilisateur
- `POST /api/pharma/login` - Connexion
- `POST /api/pharma/logout` - Déconnexion (nécessite authentification)
- `GET /api/pharma/me` - Profil utilisateur (nécessite authentification)

### Exemple d'utilisation

```bash
# Inscription
curl -X POST http://localhost:8000/api/pharma/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123","password_confirmation":"password123"}'

# Connexion
curl -X POST http://localhost:8000/api/pharma/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```

## Tests

Exécuter les tests avec PHPUnit :

```bash
php artisan test
```

Les tests couvrent :
- L'inscription et la connexion des utilisateurs
- La validation des formulaires
- Les middlewares de rôles
- L'accès aux routes protégées

## Déploiement

### Render

Le projet est configuré pour être déployé sur Render avec le fichier `render.yaml`.

1. Connectez-vous sur [Render](https://render.com)
2. Créez un nouveau service web
3. Connectez votre dépôt GitHub
4. Render détectera automatiquement le fichier `render.yaml`
5. Déployez

### Railway

Le projet est configuré pour être déployé sur Railway avec le fichier `railway.toml`.

1. Connectez-vous sur [Railway](https://railway.app)
2. Créez un nouveau projet
3. Connectez votre dépôt GitHub
4. Railway détectera automatiquement le fichier `railway.toml`
5. Déployez

### Docker

Le projet inclut un `Dockerfile` pour le déploiement avec Docker :

```bash
docker build -t pharmagarde .
docker run -p 9000:9000 pharmagarde
```

## CI/CD

Le projet utilise GitHub Actions pour les tests automatiques. Le workflow se trouve dans `.github/workflows/tests.yml`.

Les tests s'exécutent automatiquement sur chaque push et pull request vers les branches `main` et `develop`.

## Structure de la Base de Données

### Tables principales

- `users` - Utilisateurs avec rôles
- `pharmacies` - Informations sur les pharmacies
- `opening_hours` - Horaires d'ouverture des pharmacies
- `duty_schedules` - Plannings de garde des pharmacies
- `medicines` - Catalogue de médicaments
- `pharmacy_medicines` - Stock de médicaments par pharmacie
- `favorites` - Favoris des utilisateurs
- `reports` - Signalements d'erreurs
- `reservations` - Réservations de médicaments

## Règles de Gestion

1. Une pharmacie est ouverte si l'heure actuelle est dans ses horaires habituels OU si elle est de garde à cette date.
2. Seul un pharmacien validé par l'admin peut publier sa fiche et son stock.
3. Un pharmacien ne modifie que le stock de sa propre pharmacie.
4. Un stock non mis à jour depuis plus de 48 h est marqué "à confirmer".
5. Les infos sont indicatives : toujours inviter à appeler la pharmacie.
6. Un signalement validé par l'admin abaisse la fiabilité de la ligne concernée.
7. La recherche de médicament ne demande aucun compte.
8. Les distances sont calculées à vol d'oiseau (Haversine).
9. La position de l'utilisateur n'est jamais stockée.

## Contribuer

Les contributions sont les bienvenues ! Veuillez créer une branche par fonctionnalité (feature/pX-nom) et une pull request avec une description.

## Licence

Ce projet est sous licence MIT.
