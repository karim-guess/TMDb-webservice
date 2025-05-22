<?php
/**
 * Service pour interagir avec l'API TMDb - VERSION ÉTENDUE
 * Optimisé avec accès à toutes les pages disponibles
 */

class TMDbService
{
    private string $apiKey;
    private string $baseUrl;
    private string $imageBaseUrl;
    private ?DatabaseManager $db;

    public function __construct()
    {
        $this->apiKey = TMDB_API_KEY;
        $this->baseUrl = TMDB_BASE_URL;
        $this->imageBaseUrl = TMDB_IMAGE_BASE_URL;
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Recherche de films avec pagination étendue
     */
    public function searchMoviesExtended($query, $page = 1)
    {
        // Limiter les pages selon les contraintes TMDb
        $page = max(1, min(500, $page)); // TMDb permet jusqu'à 500 pages

        // Vérifier le cache d'abord
        $cacheKey = $query . '_page_' . $page;
        $cachedResults = $this->db->getSearchCache($cacheKey);

        if ($cachedResults !== null) {
            return $cachedResults;
        }

        // Si pas en cache, faire l'appel API
        $url = $this->baseUrl . '/search/movie?' . http_build_query([
                'api_key' => $this->apiKey,
                'query' => $query,
                'language' => DEFAULT_LANGUAGE,
                'page' => $page,
                'include_adult' => 'false'
            ]);

        $response = $this->makeApiCall($url);

        if ($response && isset($response['results'])) {
            // Optimiser les données pour réduire la taille mais garder les infos de pagination
            $optimizedResults = $this->optimizeSearchResultsExtended($response);

            // Sauvegarder en cache
            $this->db->saveSearchCache($cacheKey, $optimizedResults);

            // Log de la recherche
            $this->logSearch($query, $page);

            return $optimizedResults;
        }

        return ['results' => [], 'total_results' => 0, 'total_pages' => 0, 'page' => $page];
    }

    /**
     * Version standard pour compatibilité
     */
    public function searchMovies($query, $page = 1)
    {
        return $this->searchMoviesExtended($query, $page);
    }

    public function getMovieDetails($movieId)
    {
        // Vérifier si le film est déjà en base
        $movie = $this->db->getMovie($movieId);

        if ($movie && $this->isCacheValid($movie['updated_at'])) {
            return $this->formatMovieFromDb($movie);
        }

        // Récupérer les détails depuis l'API
        $url = $this->baseUrl . '/movie/' . $movieId . '?' . http_build_query([
                'api_key' => $this->apiKey,
                'language' => DEFAULT_LANGUAGE,
                'append_to_response' => 'credits'
            ]);

        $movieData = $this->makeApiCall($url);

        if ($movieData) {
            // Enrichir avec les informations du casting et réalisateur
            $movieData = $this->enrichMovieData($movieData);

            // Sauvegarder en base
            $this->db->saveMovie($movieData);

            return $this->formatMovieData($movieData);
        }

        return null;
    }

    private function makeApiCall($url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 15, // Augmenté pour les pages lointaines
                'user_agent' => 'Cinema Explorer/1.1',
                'method' => 'GET',
                'header' => [
                    'Accept: application/json',
                    'Accept-Encoding: gzip, deflate'
                ]
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log("Erreur lors de l'appel API TMDb : " . $url);
            return null;
        }

        $decodedResponse = json_decode($response, true);

        // Vérifier les erreurs TMDb
        if (isset($decodedResponse['status_code']) && $decodedResponse['status_code'] !== 1) {
            error_log("Erreur TMDb: " . ($decodedResponse['status_message'] ?? 'Erreur inconnue'));
            return null;
        }

        return $decodedResponse;
    }

    private function optimizeSearchResultsExtended($response)
    {
        $optimized = [
            'results' => [],
            'total_results' => min($response['total_results'], 10000), // TMDb limite pratique
            'total_pages' => min($response['total_pages'], 500), // TMDb limite à 500 pages
            'page' => $response['page']
        ];

        foreach ($response['results'] as $movie) {
            $optimized['results'][] = [
                'id' => $movie['id'],
                'title' => $movie['title'],
                'original_title' => $movie['original_title'] ?? '',
                'overview' => $this->truncateText($movie['overview'] ?? '', 150),
                'release_date' => $movie['release_date'] ?? '',
                'poster_path' => $movie['poster_path'] ? $this->imageBaseUrl . $movie['poster_path'] : '',
                'vote_average' => round($movie['vote_average'], 1),
                'genre_ids' => $movie['genre_ids'] ?? [],
                'popularity' => $movie['popularity'] ?? 0
            ];
        }

        return $optimized;
    }

    private function enrichMovieData($movieData)
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

        return $movieData;
    }

    private function formatMovieData($movie)
    {
        return [
            'id' => $movie['id'],
            'title' => $movie['title'],
            'original_title' => $movie['original_title'] ?? '',
            'overview' => $movie['overview'] ?? '',
            'release_date' => $movie['release_date'] ?? '',
            'poster_path' => $movie['poster_path'] ? $this->imageBaseUrl . $movie['poster_path'] : '',
            'backdrop_path' => $movie['backdrop_path'] ? $this->imageBaseUrl . $movie['backdrop_path'] : '',
            'vote_average' => round($movie['vote_average'], 1),
            'vote_count' => $movie['vote_count'],
            'runtime' => $movie['runtime'] ?? 0,
            'genres' => isset($movie['genres']) ? array_column($movie['genres'], 'name') : [],
            'director' => $movie['director'] ?? 'Non spécifié',
            'cast' => $movie['cast'] ?? [],
            'year' => $movie['release_date'] ? date('Y', strtotime($movie['release_date'])) : 'N/A'
        ];
    }

    private function formatMovieFromDb($movie)
    {
        return [
            'id' => $movie['tmdb_id'],
            'title' => $movie['title'],
            'original_title' => $movie['original_title'],
            'overview' => $movie['overview'],
            'release_date' => $movie['release_date'],
            'poster_path' => $movie['poster_path'],
            'backdrop_path' => $movie['backdrop_path'],
            'vote_average' => $movie['vote_average'],
            'vote_count' => $movie['vote_count'],
            'runtime' => $movie['runtime'],
            'genres' => json_decode($movie['genres'], true) ?: [],
            'director' => $movie['director'],
            'cast' => json_decode($movie['cast'], true) ?: [],
            'year' => $movie['release_date'] ? date('Y', strtotime($movie['release_date'])) : 'N/A'
        ];
    }

    private function isCacheValid($updatedAt)
    {
        $updateTime = strtotime($updatedAt);
        return (time() - $updateTime) < CACHE_DURATION;
    }

    private function truncateText($text, $length)
    {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }

    private function logSearch($query, $page = 1)
    {
        if (!LOG_SEARCHES) {
            return;
        }

        $logEntry = date('Y-m-d H:i:s') . " - Recherche: " . $query . " (page " . $page . ")" . PHP_EOL;
        file_put_contents(SEARCH_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    }

}