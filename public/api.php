<?php
/**
 * API pour Cinema Explorer - VERSION CORRIGÉE
 * Recherche : toujours API | Détails : BDD puis API si nécessaire
 */

// Configuration de base pour éviter les erreurs dans les réponses JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Fonction pour envoyer des réponses JSON propres
function sendJsonResponse($data, $status = 200) {
    ob_clean();
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Gestion des erreurs pour éviter les plantages
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Erreur API: $message dans $file:$line");
    return true;
});

set_exception_handler(function($exception) {
    error_log("Exception API: " . $exception->getMessage());
    sendJsonResponse(['error' => 'Erreur interne du serveur'], 500);
});

try {
    // Vérification que tous les fichiers nécessaires existent
    $files = [
        '../config/config.php',
        '../src/Models/DatabaseManager.php',
        '../src/Services/TMDbService.php',
        '../src/Utils/Utils.php'
    ];

    foreach ($files as $file) {
        if (!file_exists($file)) {
            sendJsonResponse(['error' => "Fichier manquant: $file"], 500);
        }
    }

    // Chargement des dépendances
    require_once '../config/config.php';
    require_once '../src/Models/DatabaseManager.php';
    require_once '../src/Services/TMDbService.php';
    require_once '../src/Utils/Utils.php';

    // Vérification de la clé API TMDb
    if (!defined('TMDB_API_KEY') || TMDB_API_KEY === 'YOUR_API_KEY_HERE') {
        sendJsonResponse(['error' => 'Clé API TMDb non configurée'], 500);
    }

    // Initialiser les services
    $db = DatabaseManager::getInstance();
    $tmdbService = new TMDbService();

    // Routing simple basé sur le paramètre action
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'search':
            searchMovies();
            break;
        case 'movie':
            getMovieDetails($db, $tmdbService);
            break;
        case 'stats':
            getStats($db);
            break;
        case 'health':
            healthCheck();
            break;
        default:
            sendJsonResponse(['error' => 'Action non supportée'], 400);
    }

} catch (Exception $e) {
    error_log("Erreur API principale: " . $e->getMessage());
    sendJsonResponse(['error' => 'Erreur interne du serveur'], 500);
}

/**
 * Recherche de films - TOUJOURS via l'API (avec logs)
 */
function searchMovies(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendJsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $query = $_GET['q'] ?? '';
    $page = max(1, min(500, intval($_GET['page'] ?? 1))); // TMDb accepte max 500 pages

    // Validation des paramètres
    $query = trim($query);
    if (empty($query)) {
        sendJsonResponse(['error' => 'Veuillez saisir un terme de recherche'], 400);
    }

    if (strlen($query) < 2) {
        sendJsonResponse(['error' => 'Minimum 2 caractères requis'], 400);
    }

    if (strlen($query) > 100) {
        sendJsonResponse(['error' => 'Recherche trop longue (max 100 caractères)'], 400);
    }

    try {
        // TOUJOURS faire l'appel API pour la recherche
        $params = [
            'api_key' => TMDB_API_KEY,
            'query' => $query,
            'language' => DEFAULT_LANGUAGE,
            'page' => $page,
            'include_adult' => 'false'
        ];

        $url = TMDB_BASE_URL . '/search/movie?' . http_build_query($params);

        // Configuration du contexte HTTP
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Cinema Explorer/1.1'
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            sendJsonResponse(['error' => 'Impossible de contacter TMDb'], 500);
        }

        $data = json_decode($response, true);

        if (!$data) {
            sendJsonResponse(['error' => 'Réponse invalide de TMDb'], 500);
        }

        // Gestion des erreurs TMDb
        if (isset($data['status_code'])) {
            $errorMsg = $data['status_message'] ?? 'Erreur inconnue';
            sendJsonResponse(['error' => 'TMDb: ' . $errorMsg], 400);
        }

        // IMPORTANT: Logger la recherche SEULEMENT si c'est une nouvelle recherche (page 1)
        if ($page === 1) {
            logSearch($query, $page);
        }

        // Préparation de la réponse
        $results = [
            'success' => true,
            'query' => htmlspecialchars($query, ENT_QUOTES, 'UTF-8'),
            'page' => $page,
            'total_results' => min($data['total_results'] ?? 0, 10000),
            'total_pages' => min($data['total_pages'] ?? 0, 500),
            'results' => []
        ];

        if (isset($data['results']) && is_array($data['results'])) {
            // Tri par date de sortie (plus récent en premier)
            usort($data['results'], function($a, $b) {
                $dateA = $a['release_date'] ?? '0000-00-00';
                $dateB = $b['release_date'] ?? '0000-00-00';

                if (empty($dateA)) $dateA = '0000-00-00';
                if (empty($dateB)) $dateB = '0000-00-00';

                return strcmp($dateB, $dateA);
            });

            // Formatage des résultats pour l'affichage
            foreach ($data['results'] as $movie) {
                $overview = $movie['overview'] ?? '';
                if (strlen($overview) > 150) {
                    $overview = substr($overview, 0, 150) . '...';
                }

                $results['results'][] = [
                    'id' => $movie['id'] ?? 0,
                    'title' => htmlspecialchars($movie['title'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'year' => getYearFromDate($movie['release_date'] ?? ''),
                    'overview' => htmlspecialchars($overview, ENT_QUOTES, 'UTF-8'),
                    'poster' => getPosterUrl($movie['poster_path'] ?? null),
                    'rating' => number_format($movie['vote_average'] ?? 0, 1),
                    'genres' => getGenreLabels($movie['genre_ids'] ?? [])
                ];
            }
        }

        sendJsonResponse($results);

    } catch (Exception $e) {
        error_log("Erreur recherche: " . $e->getMessage());
        sendJsonResponse(['error' => 'Erreur lors de la recherche'], 500);
    }
}

/**
 * Détails d'un film - D'ABORD BDD puis API si nécessaire
 */
function getMovieDetails($db, $tmdbService): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendJsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $movieId = intval($_GET['id'] ?? 0);

    if ($movieId <= 0) {
        sendJsonResponse(['error' => 'ID de film invalide'], 400);
    }

    try {
        // 1. D'ABORD vérifier en BDD
        $movieFromDb = $db->getMovie($movieId);

        if ($movieFromDb && isCacheValid($movieFromDb['updated_at'])) {
            // Film trouvé en BDD et cache valide
            $movie = formatMovieFromDb($movieFromDb);

            $movieData = [
                'success' => true,
                'source' => 'database', // Pour debug
                'movie' => [
                    'id' => $movie['id'],
                    'title' => htmlspecialchars($movie['title'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'original_title' => htmlspecialchars($movie['original_title'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'year' => $movie['year'],
                    'overview' => htmlspecialchars($movie['overview'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'poster' => $movie['poster_path'] ?: getPosterUrl(null),
                    'backdrop' => $movie['backdrop_path'] ?: '',
                    'rating' => number_format($movie['vote_average'] ?? 0, 1),
                    'vote_count' => number_format($movie['vote_count'] ?? 0),
                    'runtime' => formatDuration($movie['runtime'] ?? 0),
                    'release_date' => formatReleaseDate($movie['release_date'] ?? ''),
                    // CORRECTION: Gérer correctement les genres depuis la BDD
                    'genres' => formatGenresFromDb($movie['genres']),
                    'director' => htmlspecialchars($movie['director'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8'),
                    // CORRECTION: Gérer correctement le cast depuis la BDD
                    'cast' => formatCastFromDb($movie['cast'])
                ]
            ];

            sendJsonResponse($movieData);
            return;
        }

        // 2. SINON faire l'appel API et sauvegarder
        $url = TMDB_BASE_URL . '/movie/' . $movieId . '?' . http_build_query([
                'api_key' => TMDB_API_KEY,
                'language' => DEFAULT_LANGUAGE,
                'append_to_response' => 'credits'
            ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Cinema Explorer/1.1'
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            sendJsonResponse(['error' => 'Impossible de contacter TMDb'], 500);
        }

        $movie = json_decode($response, true);

        if (!$movie || isset($movie['status_code'])) {
            sendJsonResponse(['error' => 'Film non trouvé'], 404);
        }

        // Enrichir les données comme dans TMDbService
        $movie = enrichMovieData($movie);

        // IMPORTANT: Nettoyer les URLs d'images avant sauvegarde
        // Sauvegarder seulement les chemins relatifs, pas les URLs complètes
        $movieToSave = $movie;
        if (isset($movieToSave['poster_path']) && strpos($movieToSave['poster_path'], 'http') === 0) {
            // Extraire seulement le chemin depuis l'URL complète
            $movieToSave['poster_path'] = parse_url($movieToSave['poster_path'], PHP_URL_PATH);
        }
        if (isset($movieToSave['backdrop_path']) && strpos($movieToSave['backdrop_path'], 'http') === 0) {
            $movieToSave['backdrop_path'] = parse_url($movieToSave['backdrop_path'], PHP_URL_PATH);
        }

        // IMPORTANT: Sauvegarder en BDD
        $db->saveMovie($movieToSave);

        // Formater pour la réponse
        $movieData = [
            'success' => true,
            'source' => 'api', // Pour debug
            'movie' => [
                'id' => $movie['id'],
                'title' => htmlspecialchars($movie['title'] ?? '', ENT_QUOTES, 'UTF-8'),
                'original_title' => htmlspecialchars($movie['original_title'] ?? '', ENT_QUOTES, 'UTF-8'),
                'year' => getYearFromDate($movie['release_date'] ?? ''),
                'overview' => htmlspecialchars($movie['overview'] ?? '', ENT_QUOTES, 'UTF-8'),
                'poster' => getPosterUrl($movie['poster_path'] ?? null),
                'backdrop' => getBackdropUrl($movie['backdrop_path'] ?? null),
                'rating' => number_format($movie['vote_average'] ?? 0, 1),
                'vote_count' => number_format($movie['vote_count'] ?? 0),
                'runtime' => formatDuration($movie['runtime'] ?? 0),
                'release_date' => formatReleaseDate($movie['release_date'] ?? ''),
                'genres' => formatGenresFromApi($movie['genres'] ?? []),
                'director' => htmlspecialchars($movie['director'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8'),
                'cast' => formatCastFromApi($movie['cast'] ?? [])
            ]
        ];

        sendJsonResponse($movieData);

    } catch (Exception $e) {
        error_log("Erreur détails film: " . $e->getMessage());
        sendJsonResponse(['error' => 'Erreur lors de la récupération'], 500);
    }
}

/**
 * Statistiques réelles de l'application
 */
function getStats($db): void
{
    try {
        // Statistiques depuis la BDD
        $stmt = $db->pdo->query("SELECT COUNT(*) as count FROM movies");
        $movieCount = $stmt->fetch()['count'] ?? 0;

        $stats = [
            'movies_in_db' => $movieCount,
            'logs_enabled' => LOG_SEARCHES,
            'log_file_exists' => file_exists(SEARCH_LOG_FILE),
            'db_file_exists' => file_exists(DB_PATH)
        ];

        sendJsonResponse([
            'success' => true,
            'stats' => $stats
        ]);

    } catch (Exception $e) {
        error_log("Erreur stats: " . $e->getMessage());
        sendJsonResponse(['error' => 'Erreur lors de la récupération des statistiques'], 500);
    }
}

/**
 * Vérification du statut de l'API
 */
function healthCheck(): void
{
    $health = [
        'status' => 'ok',
        'timestamp' => date('c'),
        'version' => '1.1.0',
        'api_configured' => defined('TMDB_API_KEY') && TMDB_API_KEY !== 'YOUR_API_KEY_HERE',
        'files' => [
            'database' => file_exists(DB_PATH) ? 'exists' : 'missing',
            'logs' => file_exists(SEARCH_LOG_FILE) ? 'exists' : 'missing'
        ]
    ];

    sendJsonResponse($health);
}

// ========== FONCTIONS UTILITAIRES ==========

/**
 * Logger les recherches
 */
function logSearch($query, $page = 1): void
{
    if (!LOG_SEARCHES) {
        return;
    }

    $logEntry = date('d/m/Y H:i:s') . " - " . $query . PHP_EOL;
    file_put_contents(SEARCH_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Vérifier si le cache est valide
 */
function isCacheValid($updatedAt): bool
{
    $updateTime = strtotime($updatedAt);
    return (time() - $updateTime) < CACHE_DURATION;
}

/**
 * Enrichir les données du film (réalisateur + cast)
 */
function enrichMovieData($movieData): array
{
    // Extraire le réalisateur du casting
    if (isset($movieData['credits']['crew'])) {
        foreach ($movieData['credits']['crew'] as $person) {
            if ($person['job'] === 'Director') {
                $movieData['director'] = $person['name'];
                break;
            }
        }
    }

    // Extraire les acteurs principaux (max 5)
    if (isset($movieData['credits']['cast'])) {
        $cast = array_slice($movieData['credits']['cast'], 0, 5);
        $movieData['cast'] = array_map(function($actor) {
            return $actor['name'];
        }, $cast);
    }

    // S'assurer que les genres sont bien formatés
    if (isset($movieData['genres']) && is_array($movieData['genres'])) {
        // Transformer les objets genres en simple array de noms
        $movieData['genres'] = array_map(function($genre) {
            return is_array($genre) && isset($genre['name']) ? $genre['name'] : $genre;
        }, $movieData['genres']);
    }

    return $movieData;
}

/**
 * Formater un film depuis la BDD
 */
function formatMovieFromDb($movie): array
{
    // Formater correctement l'URL du poster depuis la BDD
    $posterUrl = '';
    if (!empty($movie['poster_path'])) {
        // Si c'est déjà une URL complète, la garder
        if (strpos($movie['poster_path'], 'http') === 0) {
            $posterUrl = $movie['poster_path'];
        } else {
            // Sinon, ajouter la base URL TMDb
            $posterUrl = TMDB_IMAGE_BASE_URL . $movie['poster_path'];
        }
    }

    // Formater correctement l'URL du backdrop depuis la BDD
    $backdropUrl = '';
    if (!empty($movie['backdrop_path'])) {
        if (strpos($movie['backdrop_path'], 'http') === 0) {
            $backdropUrl = $movie['backdrop_path'];
        } else {
            $backdropUrl = 'https://image.tmdb.org/t/p/w780' . $movie['backdrop_path'];
        }
    }

    // Décoder et nettoyer les genres
    $genres = [];
    if (!empty($movie['genres'])) {
        $decodedGenres = json_decode($movie['genres'], true);
        if (is_array($decodedGenres)) {
            $genres = $decodedGenres;
        }
    }

    // Décoder et nettoyer le cast
    $cast = [];
    if (!empty($movie['cast'])) {
        $decodedCast = json_decode($movie['cast'], true);
        if (is_array($decodedCast)) {
            $cast = $decodedCast;
        }
    }

    return [
        'id' => $movie['tmdb_id'],
        'title' => $movie['title'],
        'original_title' => $movie['original_title'],
        'overview' => $movie['overview'],
        'release_date' => $movie['release_date'],
        'poster_path' => $posterUrl,
        'backdrop_path' => $backdropUrl,
        'vote_average' => $movie['vote_average'],
        'vote_count' => $movie['vote_count'],
        'runtime' => $movie['runtime'],
        'genres' => $genres, // Array propre
        'director' => $movie['director'] ?: 'Non spécifié',
        'cast' => $cast, // Array propre
        'year' => $movie['release_date'] ? date('Y', strtotime($movie['release_date'])) : 'N/A'
    ];
}

function getGenreLabels($genreIds): string
{
    if (empty($genreIds) || !is_array($genreIds)) {
        return 'Non classé';
    }

    $genreMap = [
        28 => 'Action', 12 => 'Aventure', 16 => 'Animation', 35 => 'Comédie',
        80 => 'Crime', 99 => 'Documentaire', 18 => 'Drame', 10751 => 'Familial',
        14 => 'Fantastique', 36 => 'Histoire', 27 => 'Horreur', 10402 => 'Musique',
        9648 => 'Mystère', 10749 => 'Romance', 878 => 'Science-Fiction',
        10770 => 'Téléfilm', 53 => 'Thriller', 10752 => 'Guerre', 37 => 'Western'
    ];

    $genreNames = [];
    foreach (array_slice($genreIds, 0, 3) as $id) {
        if (isset($genreMap[$id])) {
            $genreNames[] = $genreMap[$id];
        }
    }

    return empty($genreNames) ? 'Non classé' : implode(', ', $genreNames);
}

function formatDuration($minutes): string
{
    if ($minutes <= 0) {
        return 'Non spécifié';
    }

    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($hours > 0) {
        return sprintf('%dh%02d', $hours, $mins);
    }

    return $minutes . ' min';
}

function formatReleaseDate($date): string
{
    if (empty($date)) {
        return 'Date inconnue';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'Date invalide';
    }

    return date('d/m/Y', $timestamp);
}

function getYearFromDate($date): string
{
    if (empty($date)) {
        return 'N/A';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('Y', $timestamp) : 'N/A';
}

function getPosterUrl($posterPath): string
{
    if (empty($posterPath)) {
        return 'https://placehold.co/300x450/a7e3d0/0a5045?text=Aucune+image';
    }

    return TMDB_IMAGE_BASE_URL . $posterPath;
}

function getBackdropUrl($backdropPath): string
{
    if (empty($backdropPath)) {
        return '';
    }

    return 'https://image.tmdb.org/t/p/w780' . $backdropPath;
}

/**
 * Formater le cast depuis l'API TMDb
 */
function formatCastFromApi($cast): string
{
    if (empty($cast) || !is_array($cast)) {
        return 'Non spécifié';
    }

    // Cast est déjà un array de noms depuis enrichMovieData()
    $validCast = array_filter($cast, function($actor) {
        return !empty($actor) && is_string($actor);
    });

    return empty($validCast) ? 'Non spécifié' : implode(', ', array_slice($validCast, 0, 5));
}

/**
 * Formater les genres depuis l'API TMDb
 */
function formatGenresFromApi($genres): string
{
    if (empty($genres) || !is_array($genres)) {
        return 'Non classé';
    }

    // Extraire les noms des genres depuis la structure API
    $genreNames = [];
    foreach (array_slice($genres, 0, 3) as $genre) {
        if (is_array($genre) && isset($genre['name'])) {
            $genreNames[] = $genre['name'];
        } elseif (is_string($genre)) {
            $genreNames[] = $genre;
        }
    }

    return empty($genreNames) ? 'Non classé' : implode(', ', $genreNames);
}

/**
 * Formater les genres depuis la BDD
 */
function formatGenresFromDb($genres): string
{
    if (empty($genres)) {
        return 'Non classé';
    }

    // Si c'est déjà un string, le retourner
    if (is_string($genres)) {
        return $genres;
    }

    // Si c'est un array, le formater
    if (is_array($genres)) {
        // Filtrer les valeurs vides et prendre les 3 premiers
        $validGenres = array_filter($genres, function($genre) {
            return !empty($genre) && $genre !== 'Array';
        });

        return empty($validGenres) ? 'Non classé' : implode(', ', array_slice($validGenres, 0, 3));
    }

    return 'Non classé';
}

/**
 * Formater le cast depuis la BDD
 */
function formatCastFromDb($cast): string
{
    if (empty($cast)) {
        return 'Non spécifié';
    }

    // Si c'est déjà un string, le retourner
    if (is_string($cast)) {
        return $cast;
    }

    // Si c'est un array, le formater
    if (is_array($cast)) {
        // Filtrer les valeurs vides et prendre les 5 premiers
        $validCast = array_filter($cast, function($actor) {
            return !empty($actor) && $actor !== 'Array';
        });

        return empty($validCast) ? 'Non spécifié' : implode(', ', array_slice($validCast, 0, 5));
    }

    return 'Non spécifié';
}

?>