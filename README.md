# Application de Recherche de Films - TMDb API

Une application web PHP permettant de rechercher des informations sur des films via l'API The Movie Database (TMDb).

## 🌐 Démonstration en ligne

**URL de démonstration : https://tmdb.karimguessab.fr/

> L'application est actuellement déployée et fonctionnelle. Vous pouvez tester toutes les fonctionnalités directement en ligne.

## 🎯 Fonctionnalités

- **Recherche de films** : Interface de recherche intuitive par titre
- **Affichage détaillé** : Informations complètes sur les films (titre, réalisateur, année, synopsis, poster)
- **Design responsive** : Compatible mobile, tablette et desktop
- **Logging automatique** : Enregistrement de toutes les recherches effectuées
- **Configuration sécurisée** : Clé API stockée dans un fichier de configuration

## 🛠️ Technologies Utilisées

- **Backend** : PHP 7.4+ (sans framework)
- **Frontend** : HTML5, CSS3, Bootstrap 5
- **API** : The Movie Database (TMDb) API v3
- **Versioning** : Git
- **Logging** : Système de logs personnalisé

## 📋 Prérequis

- PHP 7.4 ou supérieur
- Extension PHP cURL activée
- Serveur web (Apache/Nginx) ou serveur de développement PHP
- Clé API TMDb (gratuite)

## 🚀 Installation

1. **Cloner le repository**
   ```bash
   git clone https://tmdb.karimguessab.fr/
   cd TMDB-webservice
   ```

2. **Configuration de l'API**
   - Créer un compte sur [TMDb](https://www.themoviedb.org/)
   - Obtenir une clé API gratuite
   - Dupliquer le fichier `config/config.php.example` vers `config/config.php`
   - Renseigner votre clé API dans le fichier de configuration

   ```php
   <?php
   return [
       'tmdb_api_key' => 'VOTRE_CLE_API_ICI',
       'tmdb_base_url' => 'https://api.themoviedb.org/3',
       'tmdb_image_base_url' => 'https://image.tmdb.org/t/p/w500'
   ];
   ```

3. **Lancement de l'application**
   
   **Option A : Serveur de développement PHP**
   ```bash
   php -S localhost:8000 -t public
   ```
   
   **Option B : Serveur web traditionnel**
   - Pointer le DocumentRoot vers le dossier `public/`
   - Accéder via votre domaine local

## 📁 Structure du Projet

```
TMDB-webservice/
├── config/
│   ├── config.php              # Configuration de l'API
│   └── logger.php              # Configuration du logging
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── bootstrap.min.css
│   │   │   └── style.min.css
│   │   └── js/
│   │       ├── app.min.js
│   │       └── bootstrap.min.js
│   ├── api.php                 # Point d'entrée API
│   └── index.php               # Page principale
├── src/
│   ├── Models/
│   │   └── DatabaseManager.php # (si base de données)
│   ├── Services/
│   │   └── TMDbService.php     # Service d'intégration TMDb
│   └── Utils/
│       └── Utils.php           # Utilitaires et helpers
├── logs/
│   └── searches.log            # Fichier de logs des recherches
├── .gitignore
└── README.md
```

## 🎨 Choix de Conception

### Architecture
- **Séparation des responsabilités** : Services, Models, Utils dans des dossiers distincts
- **Configuration externalisée** : Clé API et paramètres dans un fichier dédié
- **Logging centralisé** : Toutes les recherches enregistrées avec horodatage

### Interface Utilisateur
- **Bootstrap 5** : Framework CSS moderne et responsive
- **Design mobile-first** : Optimisé pour tous les écrans
- **UX intuitive** : Interface simple et claire
- **Feedback visuel** : Loading states et messages d'erreur

### Sécurité
- **Validation des inputs** : Sanitisation des données utilisateur
- **Gestion d'erreurs** : Messages d'erreur appropriés sans exposition d'informations sensibles
- **Configuration protégée** : Clé API non versionnée dans Git

## 🔍 Utilisation

1. **Recherche simple**
   - Saisir le titre d'un film dans la barre de recherche
   - Cliquer sur "Rechercher" ou appuyer sur Entrée
   - Parcourir les résultats affichés

2. **Détails d'un film**
   - Cliquer sur un film dans les résultats
   - Consulter les informations détaillées (synopsis, date de sortie, etc.)

3. **Consultation des logs**
   - Les recherches sont automatiquement enregistrées dans `logs/searches.log`
   - Format : `[YYYY-MM-DD HH:mm:ss] - Recherche: "terme_recherché"`

## 🚨 Limitations Connues

- **Limite de l'API TMDb** : 1000 requêtes par jour pour les comptes gratuits
- **Pas de cache** : Chaque recherche interroge directement l'API
- **Langue par défaut** : Résultats en français, configurable dans le service
- **Pas d'authentification** : Application accessible publiquement

## 📊 Améliorations Possibles

- **Cache Redis/Memcached** : Réduire les appels API
- **Pagination** : Pour les résultats nombreux
- **Favoris** : Système de films favoris avec base de données
- **Filtres avancés** : Par genre, année, note, etc.
- **API REST** : Transformation en API pour usage mobile

## 🧪 Tests

**Tests en ligne :**
- Accéder à la démo : https://tmdb.karimguessab.fr/
- Tester la recherche avec différents termes : "Inception", "Avengers", "Titanic"
- Vérifier la responsivité sur mobile/tablette

**Tests en local :**
```bash
# Tester l'API directement
curl "http://localhost:8000/api.php?search=Inception"

# Vérifier les logs des recherches
tail -f logs/searches.log
```

## 🤝 Contribution

1. Fork du projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit des changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 Versioning

Utilisation de [Git](https://git-scm.com/) pour le versioning. Voir les [tags](https://github.com/votre-username/TMDB-webservice/tags) pour les versions disponibles.

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE.md](LICENSE.md) pour plus de détails.

## 👨‍💻 Auteur

**Karim Guessab** - *Développement initial*

## 🙏 Remerciements

- [The Movie Database (TMDb)](https://www.themoviedb.org/) pour l'API
- [Bootstrap](https://getbootstrap.com/) pour le framework CSS
- L'équipe Lemon Interactive pour l'opportunité
