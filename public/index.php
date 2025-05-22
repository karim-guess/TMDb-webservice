<?php

require_once('../src/Services/MovieService.php');
require_once(__DIR__ . '/../config/logger.php');

// Initialisation des services
$movieService = new MovieService();
$logger = new Logger();

// Variables pour l'affichage
$searchQuery = '';
$movies = [];
$errorMessage = '';
$hasSearched = false;

// Traitement de la recherche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchQuery = trim($_POST['search']);
    $hasSearched = true;
    
    if (!empty($searchQuery)) {
        try {
            $movies = $movieService->searchMovies($searchQuery);
            
            // Debug des résultats
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("Nombre de films trouvés: " . count($movies));
                if (!empty($movies)) {
                    error_log("Premier film: " . print_r($movies[0], true));
                }
            }
            
            // Log de la recherche
            $logger->logSearch($searchQuery, count($movies));
            
            if (empty($movies)) {
                $errorMessage = "Aucun film trouvé pour votre recherche.";
                // Message de debug si activé
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $errorMessage .= " <br><small>Vérifiez les logs du serveur pour plus de détails.</small>";
                }
            }
        } catch (Exception $e) {
            $errorMessage = "Une erreur est survenue lors de la recherche.";
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $errorMessage .= "<br><small>Erreur: " . htmlspecialchars($e->getMessage()) . "</small>";
                error_log("Exception lors de la recherche: " . $e->getMessage());
            }
        }
    } else {
        $errorMessage = "Veuillez saisir un titre de film à rechercher.";
    }
}


$recentSearches = $logger->getRecentSearches(5);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinéma Explorer - Recherche de Films</title>
    
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Styles optimisés avec la palette Lemon Interactive */
        :root {
            --lemon-green-dark: #164b38;  /* Vert foncé du footer */
            --lemon-green-light: #a7e3d0; /* Vert clair du bandeau supérieur */
            --lemon-yellow: #fef8df;      /* Jaune très pâle du fond principal */
            --lemon-pink: #f2b5c9;        /* Rose du bouton contact */
            --lemon-dark: #21433d;        /* Variante plus foncée pour contraste */
        }

        
        html, body {
            height: 100%;
            background-color: var(--lemon-yellow);
            color: var(--lemon-dark);
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .page-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1 0 auto;
        }

       
        .top-banner {
            background-color: var(--lemon-green-light);
            color: #103729;
            font-size: 1rem;
                    height: 1.4375rem;
 ;
            text-align: center;
        }
        header {
            background-color: var(--lemon-yellow);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(10, 80, 69, 0.1);
        }
        header.header-scrolled {
            box-shadow: 0 4px 6px -1px rgba(10, 80, 69, 0.1);
        }
        header.header-hidden {
            transform: translateY(-100%);
        }
        .logo-text {
            color: var(--lemon-dark);
            font-weight: 600;
        }
        .logo-container {
            color: #fffbd8;
            display: flex;
            align-items: center;
        }
        .logo-circle {
            width: 16px;
            height: 16px;
            background-color: var(--lemon-dark);
            border-radius: 50%;
            margin-left: 4px;
        }

        
        .search-container {
            background-color: var(--lemon-green-dark);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: var(--lemon-yellow);
        }
        .btn-primary {
            background-color: var(--lemon-green-dark);
            border-color: var(--lemon-green-dark);
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--lemon-dark);
            border-color: var(--lemon-dark);
        }

        
        .btn-secondary {
            background-color: var(--lemon-yellow);
            border-color: var(--lemon-yellow);
            color: var(--lemon-green-dark);
            font-weight: 500;
        }
        .btn-secondary:hover, .btn-secondary:focus {
            background-color: #f5efc8;
            border-color: #f5efc8;
            color: var(--lemon-dark);
        }

        
        :focus-visible {
            outline-color: var(--lemon-green-dark) !important;
        }
        .form-control:focus, .btn:focus {
            border-color: var(--lemon-green-dark) !important;
            box-shadow: 0 0 0 0.25rem rgba(10, 80, 69, 0.25) !important;
        }
        .btn-close:focus {
            box-shadow: 0 0 0 0.25rem rgba(10, 80, 69, 0.25) !important;
        }

      
        .movie-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 1rem;
            overflow: hidden;
            border: none;
        }
        .movie-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px -5px rgba(10, 80, 69, 0.2);
        }
        .card-footer {
            border-top: none;
            background-color: white;
        }
        .btn-outline-primary {
            color: var(--lemon-green-dark);
            border-color: var(--lemon-green-dark);
        }
        .btn-outline-primary:hover {
            background-color: var(--lemon-green-dark);
            border-color: var(--lemon-green-dark);
            color: white;
        }

      
        .poster-container { height: 300px; overflow: hidden; }
        .poster-container img { object-fit: cover; height: 100%; width: 100%; }
        .movie-title { font-weight: 600; }
        .badge-year { background-color: var(--lemon-green-dark); }
        .loading { display: none; text-align: center; padding: 2rem 0; }
        .loading-spinner { width: 3rem; height: 3rem; color: var(--lemon-green-dark) !important; }
        #searchResults { min-height: 200px; }
        .modal-poster { max-height: 400px; object-fit: contain; }

        /* Personnalisation de la modal */
        .modal-content {
            border-radius: 1rem;
            border: none;
            overflow: hidden;
        }
        .modal-header {
            background-color: var(--lemon-green-light);
            border-bottom: none;
            color: var(--lemon-green-dark);
        }
        .modal-footer {
            border-top: none;
        }

        
        footer {
            flex-shrink: 0;
            background-color: var(--lemon-green-dark);
            color: #fffbd8;
            padding: 1rem 0;
            font-size: .875rem;
        }


        .wave-divider {
            height: 80px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%230a5045' fill-opacity='1' d='M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,224C672,213,768,171,864,165.3C960,160,1056,192,1152,197.3C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            background-repeat: no-repeat;
            margin-top: 1.5rem;
            position: relative;
        }

        /* Bouton retour en haut */
        #backToTop {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: none;
            z-index: 99;
            background-color: var(--lemon-pink);
            color: var(--lemon-dark);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(10, 80, 69, 0.15);
            transition: transform 0.2s ease;
        }
        #backToTop:hover {
            transform: scale(1.1);
        }

        /* Styles pour les alertes d'erreur */
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffecb5;
            color: var(--lemon-green-dark);
        }

        @media (max-width: 768px) {
            .poster-container { height: 200px; }
            .modal-poster { max-height: 300px; }
            .wave-divider { height: 50px; }
        }
    </style>
</head>
<body>
<!-- Structure globale pour maintenir la vague en bas -->
<div class="page-wrapper">
    <!-- Bannière supérieure -->
    <div class="top-banner">
        <div class="container">
            Découvrez notre sélection de films éco-conçue - Impact environnemental réduit
        </div>
    </div>

    <!-- En-tête -->
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
                    <small>Votre guide de films éco-responsable</small>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteneur principal avec moins d'espace en bas -->
    <main class="container py-4 flex-grow-1">

        <!-- Section de recherche -->
        <section class="search-container mb-4">
            <h2 class="h4 mb-3">Rechercher un film</h2>
            <form method="POST" action="" id="searchForm" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" id="searchInput" class="form-control search-input" placeholder="Entrez le titre d'un film..."
                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                    required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-secondary w-100">Rechercher</button>
                </div>
            </form>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                    <?php echo $errorMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </section>

        <!-- Résultats de recherche avec moins de padding -->
        <section id="searchResults" class="results-container mb-2">
            <?php if (!$hasSearched): ?>
                <div class="text-center py-4">
                    <img src="https://placehold.co/300x200/a7e3d0/0a5045?text=Recherchez+un+film" alt="Illustration" class="img-fluid mb-3" style="max-width: 300px; border-radius: 1rem;">
                    <h3 class="h5 text-muted">Commencez par rechercher un film</h3>
                    <p class="text-muted small">Les résultats apparaîtront ici</p>
                </div>
            <?php elseif (!empty($movies)): ?>
                <h2 class="h4 mb-3">Résultats de recherche pour "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
                <div class="row g-4 mb-2">
                    <?php foreach ($movies as $movie): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm movie-card">
                                <div class="poster-container">
                                    <img src="<?php echo $movieService->getImageUrl($movie['poster_path'] ?? ''); ?>" 
                                         alt="Affiche de <?php echo htmlspecialchars($movie['title'] ?? 'Film sans titre'); ?>" 
                                         class="card-img-top"
                                         loading="lazy">
                                </div>
                                <div class="card-body">
                                    <h3 class="card-title h5 movie-title">
                                        <?php echo htmlspecialchars($movie['title'] ?? 'Titre non disponible'); ?>
                                    </h3>
                                    <div class="mb-2">
                                        <?php if (!empty($movie['release_date'])): ?>
                                            <span class="badge bg-secondary badge-year">
                                                <?php echo date('Y', strtotime($movie['release_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($movie['vote_average'])): ?>
                                            <span class="ms-2 text-muted">
                                                ⭐ <?php echo number_format($movie['vote_average'], 1); ?>/10
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($movie['overview'])): ?>
                                        <p class="card-text small">
                                            <?php 
                                                $overview = $movie['overview'];
                                                echo htmlspecialchars(strlen($overview) > 150 ? substr($overview, 0, 150) . '...' : $overview); 
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-white">
                                    <button class="btn btn-sm btn-outline-primary w-100 view-details" 
                                            data-movie='<?php echo htmlspecialchars(json_encode($movie), ENT_QUOTES); ?>'>
                                        Voir les détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($hasSearched && empty($movies) && empty($errorMessage)): ?>
                <div class="text-center py-4">
                    <h3 class="h5 text-muted">Aucun résultat trouvé</h3>
                    <p class="text-muted small">Essayez avec un autre titre de film</p>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Recherches récentes (bonus) -->
        <?php if (!empty($recentSearches)): ?>
            <section class="mt-4">
                <div class="card" style="background-color: rgba(167, 227, 208, 0.1); border: none; border-radius: 1rem;">
                    <div class="card-body">
                        <h4 class="h6 mb-3">Recherches récentes</h4>
                        <div class="small text-muted">
                            <?php foreach ($recentSearches as $search): ?>
                                <div class="mb-1"><?php echo htmlspecialchars($search); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Transition vers le footer - version originale avec background-image -->
    <div class="wave-divider"></div>

    <!-- Pied de page -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="logo-container mb-3">
                        <h2 class="h5 mb-0 ">Cinéma Explorer</h2>
                    </div>
                    <p class="small">Application éco-conçue pour rechercher et découvrir des films</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 small">© 2025 - Développé avec PHP et Bootstrap</p>
                    <p class="small">Optimisé pour un impact environnemental minimal</p>
                </div>
            </div>
        </div>
    </footer>
</div>

<!-- Modal pour les détails du film -->
<div class="modal fade" id="movieDetailsModal" tabindex="-1" aria-labelledby="movieDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="movieDetailsModalLabel">Détails du film</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="movieDetailsContent">
                <div class="text-center py-3">
                    <div class="spinner-border loading-spinner" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des détails...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Bouton retour en haut -->
<button id="backToTop" title="Retour en haut">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z"/>
    </svg>
</button>

<!-- Bootstrap JS (uniquement les composants nécessaires) -->
<script src="./assets/js/bootstrap.min.js"></script>
<!-- Script JavaScript modifié pour les vraies données API -->
<script>
    // Script optimisé pour SPA légère avec données API réelles
    document.addEventListener("DOMContentLoaded", function() {
        // Éléments DOM
        const backToTopBtn = document.getElementById('backToTop');
        const movieDetailsModal = new bootstrap.Modal(document.getElementById('movieDetailsModal'));
        const movieDetailsContent = document.getElementById('movieDetailsContent');
        const header = document.getElementById('mainHeader');

        // Variables pour le header auto-hide
        let lastScrollTop = 0;
        const scrollThreshold = 5; // Seuil de défilement en pixels

        // Gestionnaire de défilement pour le header
        window.addEventListener('scroll', function() {
            let currentScroll = window.pageYOffset || document.documentElement.scrollTop;

            // Ajouter la classe scrolled lorsqu'on défile
            if (currentScroll > 10) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }

            // Header auto-hide
            if (currentScroll > lastScrollTop && currentScroll > 100) {
                // Défilement vers le bas au-delà de 100px, masquer le header
                header.classList.add('header-hidden');
            } else if (currentScroll < lastScrollTop - scrollThreshold) {
                // Défilement vers le haut d'au moins 5px, afficher le header
                header.classList.remove('header-hidden');
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;

            // Bouton retour en haut
            if (currentScroll > 300) {
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.style.display = 'none';
            }
        });

        // Gestionnaires d'événements pour les boutons de détails
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const movieData = JSON.parse(this.getAttribute('data-movie'));
                showMovieDetails(movieData);
            });
        });

        // Afficher les détails d'un film dans la modal
        function showMovieDetails(movie) {
            // Réinitialiser le contenu de la modal
            movieDetailsContent.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border loading-spinner" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des détails...</p>
                </div>
            `;

            // Afficher la modal
            movieDetailsModal.show();

            // Simuler le chargement des détails (remplacé par les vraies données API)
            setTimeout(() => {
                renderMovieDetails(movie);
            }, 300);
        }

        // Afficher les détails du film dans la modal avec les vraies données API
        function renderMovieDetails(movie) {
            const posterUrl = movie.poster_path 
                ? `<?php echo $movieService->getImageUrl(''); ?>`.replace(/[^\/]*$/, movie.poster_path.replace('/', ''))
                : 'https://placehold.co/600x900/a7e3d0/0a5045?text=Pas+d\'affiche';
            
            const releaseYear = movie.release_date ? new Date(movie.release_date).getFullYear() : 'Année inconnue';
            const rating = movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A';
            const overview = movie.overview || 'Aucun synopsis disponible.';
            const title = movie.title || 'Titre non disponible';

            const html = `
                <div class="row">
                    <div class="col-md-4 mb-4 mb-md-0 text-center">
                        <img src="${posterUrl}" alt="Affiche de ${title}" class="img-fluid rounded modal-poster">
                    </div>
                    <div class="col-md-8">
                        <h2 class="mb-2">${title}</h2>
                        <div class="mb-3">
                            <span class="badge bg-secondary me-2">${releaseYear}</span>
                            <span class="badge bg-info text-dark me-2">${rating}/10</span>
                            ${movie.genre_ids ? '<span class="text-muted">ID genres: ' + movie.genre_ids.join(', ') + '</span>' : ''}
                        </div>
                        <h3 class="h5 mt-3">Synopsis</h3>
                        <p>${overview}</p>

                        ${movie.original_language ? '<h3 class="h5 mt-3">Langue originale</h3><p>' + movie.original_language.toUpperCase() + '</p>' : ''}
                        
                        ${movie.popularity ? '<h3 class="h5 mt-3">Popularité</h3><p>' + Math.round(movie.popularity) + '</p>' : ''}
                        
                        ${movie.vote_count ? '<h3 class="h5 mt-3">Nombre de votes</h3><p>' + movie.vote_count + '</p>' : ''}
                    </div>
                </div>
            `;

            movieDetailsContent.innerHTML = html;
        }

        // Gérer le bouton de retour en haut
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    });
</script>
</body>
</html>