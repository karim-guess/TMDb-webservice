<?php
/**
 * API pour Cinema Explorer - VERSION SIMPLIFIÉE
 * Toute la logique métier est dans TMDbService.php
 */

// Configuration de base pour éviter les erreurs dans les réponses JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');

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

    // Initialiser le service TMDb
    $tmdbService = new TMDbService();

    // Routing simple basé sur le paramètre action
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'search':
            handleSearchMovies($tmdbService);
            break;
        case 'movie':
            handleGetMovieDetails($tmdbService);
            break;
        default:
            sendJsonResponse(['error' => 'Action non supportée'], 400);
    }

} catch (Exception $e) {
    error_log("Erreur API principale: " . $e->getMessage());
    sendJsonResponse(['error' => 'Erreur interne du serveur'], 500);
}

/**
 * Gérer la recherche de films
 */
function handleSearchMovies(TMDbService $tmdbService): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendJsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    try {
        $query = $_GET['q'] ?? '';
        $page = max(1, min(500, intval($_GET['page'] ?? 1)));

        $results = $tmdbService->searchMovies($query, $page);
        sendJsonResponse($results);

    } catch (InvalidArgumentException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 400);
    } catch (Exception $e) {
        error_log("Erreur recherche: " . $e->getMessage());
        sendJsonResponse(['error' => 'Erreur lors de la recherche'], 500);
    }
}

/**
 * Gérer la récupération des détails d'un film
 */
function handleGetMovieDetails(TMDbService $tmdbService): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendJsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    try {
        $movieId = intval($_GET['id'] ?? 0);
        $details = $tmdbService->getMovieDetails($movieId);
        sendJsonResponse($details);

    } catch (InvalidArgumentException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 400);
    } catch (Exception $e) {
        if ($e->getMessage() === 'Film non trouvé') {
            sendJsonResponse(['error' => 'Film non trouvé'], 404);
        } else {
            error_log("Erreur détails film: " . $e->getMessage());
            sendJsonResponse(['error' => 'Erreur lors de la récupération'], 500);
        }
    }
}

?>