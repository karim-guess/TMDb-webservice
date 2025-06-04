<?php
/**
 * Page principale de l'application Cinema Explorer
 * Optimisée pour l'éco-conception et les performances
 */

require_once '../config/config.php';
require_once '../src/Utils/Utils.php';

// Initialiser la base de données
try {
    require_once '../src/Models/DatabaseManager.php';
    $db = DatabaseManager::getInstance();
} catch (Exception $e) {
    error_log("Erreur d'initialisation de la base de données: " . $e->getMessage());
}

// Métadonnées pour l'optimisation
$metadata = Utils::generateMetadata();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $metadata['title'] ?></title>
    <meta name="description" content="<?= $metadata['description'] ?>">
    <link rel="canonical" href="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?>">

    <!-- Optimisations pour l'éco-conception -->
    <meta name="theme-color" content="#0a5045">
    <meta name="robots" content="index, follow">
    <link rel="preconnect" href="https://api.themoviedb.org">
    <link rel="preconnect" href="https://image.tmdb.org">

    <!-- CSS optimisé -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Préchargement des ressources critiques -->
    <link rel="preload" href="assets/js/app.min.js" as="script">
</head>
<body>
<div class="page-wrapper">
    <!-- Bannière éco-responsable -->
    <div class="top-banner">
        <div class="container">
            <small>🌱 Application éco-conçue - Cache intelligent pour réduire l'impact environnemental</small>
        </div>
    </div>

    <!-- En-tête optimisé -->
    <header id="mainHeader">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="logo-container">
                        <h1 class="h4 mb-0 logo-text">Cinéma Explorer</h1>
                        <div class="logo-circle"></div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>Recherche de films éco-responsable</small>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="container py-4 flex-grow-1">
        <!-- Section de recherche optimisée -->
        <section class="search-container mb-4">
            <h2 class="h4 mb-3">
                <span class="me-2">🎬</span>Rechercher un film
            </h2>
            <form id="searchForm" class="row g-3" role="search">
                <div class="col-md-8">
                    <label for="searchInput" class="visually-hidden">Titre du film</label>
                    <input
                            type="search"
                            id="searchInput"
                            class="form-control"
                            placeholder="Entrez le titre d'un film..."
                            autocomplete="off"
                            minlength="2"
                            maxlength="100"
                            required
                            aria-describedby="search-help">
                    <div id="search-help" class="form-text text-light">
                        La recherche se lance automatiquement après 2 caractères
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-secondary w-100">
                        <span class="me-1">🔍</span>Rechercher
                    </button>
                </div>
            </form>
        </section>

        <!-- Indicateur de chargement optimisé -->
        <div id="loading" class="loading" role="status" aria-live="polite">
            <div class="spinner-border loading-spinner" role="status">
                <span class="visually-hidden">Recherche en cours...</span>
            </div>
            <p class="mt-2">Recherche en cours...</p>
        </div>

        <!-- Zone des résultats -->
        <section id="searchResults" class="results-container mb-2" role="main" aria-live="polite">
            <div class="text-center py-4">
                <img src="<?= Utils::getPlaceholderImage(300, 200) ?>?text=Recherchez+un+film"
                     alt="Illustration de recherche"
                     class="img-fluid mb-3"
                     style="max-width: 300px; border-radius: 1rem;"
                     loading="lazy">
                <h3 class="h5 text-muted">Commencez par rechercher un film</h3>
                <p class="text-muted small">
                    Tapez le titre d'un film dans la barre de recherche.<br>
                    Les résultats s'afficheront automatiquement.
                </p>

                <?php if (defined('TMDB_API_KEY') && TMDB_API_KEY === 'YOUR_API_KEY_HERE'): ?>
                    <div class="alert alert-warning mt-3" role="alert">
                        <strong>Configuration requise :</strong>
                        Veuillez configurer votre clé API TMDb dans le fichier config.php
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Transition vague vers footer -->
    <div class="wave-divider"></div>

    <!-- Footer optimisé -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <div class="logo-container mb-3">
                        <h2 class="h5 mb-0 text-white">Cinéma Explorer</h2>
                    </div>
                    <p class="small mb-2">
                        Application éco-conçue utilisant l'API TMDb avec cache intelligent
                    </p>
                    <p class="small mb-0">
                        🌱 Optimisée pour un impact environnemental minimal
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <p class="mb-1 small">© <?= date('Y') ?> - Cinema Explorer</p>
                    <p class="small mb-0">Développé avec PHP & Bootstrap</p>
                    <p class="small">
                        <a href="https://www.themoviedb.org/" class="text-white text-decoration-none"
                           target="_blank" rel="noopener">
                            Données fournies par TMDb
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
</div>

<!-- Modal pour les détails optimisée -->
<div class="modal fade" id="movieDetailsModal" tabindex="-1"
     aria-labelledby="movieDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="movieDetailsModalLabel">
                    <span class="me-2">🎬</span>Détails du film
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="movieDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border loading-spinner" role="status">
                        <span class="visually-hidden">Chargement des détails...</span>
                    </div>
                    <p class="mt-2">Chargement des détails...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bouton retour en haut -->
<button id="backToTop" title="Retour en haut" aria-label="Retour en haut de la page">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z"/>
    </svg>
</button>

<!-- Scripts optimisés -->
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/app.min.js"></script>

<!-- Schema.org pour le SEO -->
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Cinéma Explorer",
        "description": "<?= $metadata['description'] ?>",
    "url": "<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?>",
    "applicationCategory": "Entertainment",
    "operatingSystem": "All",
    "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "EUR"
    }
}
</script>

</body>
</html>