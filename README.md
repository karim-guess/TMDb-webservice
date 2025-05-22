# Application de Recherche de Films

Une application web PHP permettant de rechercher des informations sur des films en utilisant l'API The Movie Database (TMDb).

## 📋 Description

Cette application permet aux utilisateurs de rechercher des films et d'afficher leurs informations détaillées telles que le titre, le réalisateur, l'année de sortie, le synopsis, et l'affiche. L'interface est responsive et utilise Bootstrap pour un design moderne et adaptatif.

## 🚀 Fonctionnalités

- **Recherche de films** : Recherche par titre de film
- **Affichage détaillé** : Titre, réalisateur, année, synopsis, note moyenne et affiche
- **Interface responsive** : Compatible desktop, tablette et mobile
- **Historique des recherches** : Enregistrement automatique dans un fichier log
- **Gestion d'erreurs** : Messages d'erreur informatifs pour l'utilisateur

## 🛠️ Technologies utilisées

- **Backend** : PHP 7.4+ (sans framework)
- **Frontend** : HTML5, CSS3, Bootstrap 5
- **API** : The Movie Database (TMDb) API
- **Versioning** : Git

## 📦 Installation

### Prérequis

- PHP 7.4 ou supérieur
- Extension PHP cURL activée
- Serveur web (Apache, Nginx, ou serveur de développement PHP)
- Clé API TMDb

### Configuration

1. **Cloner le repository**
   ```bash
   git clone [URL_DU_REPOSITORY]
   cd movie-search-app
   ```

2. **Configuration de l'API**
   
   Créer un fichier `config.php` à la racine du projet :
   ```php
   <?php
   return [
       'tmdb_api_key' => 'VOTRE_CLE_API_TMDB',
       'tmdb_base_url' => 'https://api.themoviedb.org/3',
       'tmdb_image_base_url' => 'https://image.tmdb.org/t/p/w500'
   ];
   ?>
   ```

3. **Obtenir une clé API TMDb**
   - Créer un compte sur [The Movie Database](https://www.themoviedb.org/)
   - Aller dans les paramètres de votre compte → API
   - Demander une clé API et suivre les instructions
   - Copier la clé dans le fichier `config.php`

4. **Permissions**
   ```bash
   chmod 755 logs/
   chmod 664 logs/search.log
   ```

## 🖥️ Utilisation

### Démarrage du serveur

**Option 1 : Serveur de développement PHP**
```bash
php -S localhost:8000
```

**Option 2 : Serveur web traditionnel**
Placer les fichiers dans le répertoire web de votre serveur (htdocs, www, etc.)

### Utilisation de l'application

1. Accéder à l'application via votre navigateur
2. Saisir le titre d'un film dans le champ de recherche
3. Cliquer sur "Rechercher" ou appuyer sur Entrée
4. Consulter les résultats affichés avec toutes les informations du film

## 📁 Structure du projet

```
movie-search-app/
│
├── config/
│   ├── config.php           # Configuration (clé API, URLs)
│   └── Logger.php           # Classe pour la gestion des logs
├── logs/
│   └── search.log           # Fichier de log des recherches
├── public/
│   ├── assets/
│   ├── css/
│   │   └── style.css        # Styles personnalisés
│   │   └── boostrap.min.css # Styles personnalisés
│   └── js/
│       └── app.js           # Scripts JavaScript
│   │   └── boostrap.min.js  # Styles personnalisés
│   └──index.php                 # Page principale de l'application
└── README.md                # Documentation
```

## 🏗️ Choix de conception

### Architecture

- **Approche orientée objet** : Utilisation de classes pour organiser le code
- **Séparation des responsabilités** : Service API séparé de la logique d'affichage
- **Configuration centralisée** : Toutes les configurations dans un fichier dédié

### Sécurité

- **Validation des entrées** : Filtrage et validation des données utilisateur
- **Gestion des erreurs** : Messages d'erreur sécurisés sans exposition d'informations sensibles
- **Protection XSS** : Échappement des données avant affichage

### Performance

- **Cache des images** : Utilisation du cache navigateur pour les affiches
- **Requêtes optimisées** : Une seule requête API par recherche
- **Code léger** : Pas de dépendances externes lourdes

## 📊 Système de logs

Toutes les recherches sont automatiquement enregistrées dans `logs/search.log` avec le format :
```
[2024-01-15 14:30:25] Recherche: "Inception" - Résultats: 1 film(s) trouvé(s)
[2024-01-15 14:32:10] Recherche: "Matrix" - Résultats: 3 film(s) trouvé(s)
```

## 🔧 API TMDb

### Endpoints utilisés

- **Recherche** : `/search/movie`
- **Crédits** : `/movie/{id}/credits`
- **Images** : Configuration d'images via `/configuration`

### Limites de l'API

- 1000 requêtes par jour pour les comptes gratuits
- Certaines informations peuvent être manquantes selon les films
- Les images peuvent ne pas être disponibles pour tous les films

## 🐛 Limitations connues

1. **Dépendance internet** : L'application nécessite une connexion internet pour fonctionner
2. **Limite API** : Soumise aux limitations de l'API TMDb gratuite
3. **Langue** : Les résultats sont principalement en anglais (configurable)
4. **Réalisateur** : Parfois indisponible selon les données TMDb

## 🚀 Améliorations possibles

- Ajout d'un système de cache local
- Implémentation de la pagination pour les résultats multiples
- Ajout de filtres avancés (genre, année, note)
- Sauvegarde des favoris en session/cookie
- Interface d'administration pour consulter les logs
- Support multilingue
- Ajout d'un système de suggestion de recherche

## 🤝 Contribution

1. Fork le project
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit vos changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Ouvrir une Pull Request

## 📝 Commits Git

Le développement suit une approche par étapes avec des commits logiques :

1. Initial commit - Structure de base
2. Configuration API et service TMDb
3. Interface utilisateur Bootstrap
4. Système de recherche
5. Gestion des erreurs
6. Système de logs
7. Responsive design
8. Documentation

## 📞 Support

Pour toute question ou problème, veuillez créer une issue dans le repository Git.

## 📄 Licence

Ce projet est développé dans le cadre d'un test technique pour Lemon Interactive.