<?php
/**
 * Service pour interagir avec l'API TMDb - VERSION COMPLÈTE
 * Toute la logique métier centralisée ici
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
     * Recherche de films - TOUJOURS via l'API
     */
    public function searchMovies(string $query, int $page = 1): array
    {
        // Validation des paramètres
        $query = trim($query);
        if (empty($query)) {
            throw new InvalidArgumentException('Veuillez saisir un terme de recherche');
        }

        if (strlen($query) < 2) {
            throw new InvalidArgumentException('Minimum 2 caractères requis');
        }

        if (strlen($query) > 100) {
            throw new InvalidArgumentException('Recherche trop longue (max 100 caractères)');
        }

        $page = max(1, min(500, $page)); // TMDb accepte max 500 pages

        // Logger la recherche SEULEMENT si c'est une nouvelle recherche (page 1)
        if ($page === 1) {
            $this->logSearch($query);
        }

        // Faire l'appel API
        $params = [
            'api_key' => $this->apiKey,
            'query' => $query,
            'language' => DEFAULT_LANGUAGE,
            'page' => $page,
            'include_adult' => 'false'
        ];

        $url = $this->baseUrl . '/search/movie?' . http_build_query($params);
        $response = $this->makeApiCall($url);

        // Gestion des erreurs TMDb
        if (isset($response['status_code'])) {
            $errorMsg = $response['status_message'] ?? 'Erreur inconnue';
            throw new Exception('TMDb: ' . $errorMsg);
        }

        // Préparation de la réponse
        $results = [
            'success' => true,
            'query' => htmlspecialchars($query, ENT_QUOTES, 'UTF-8'),
            'page' => $page,
            'total_results' => min($response['total_results'] ?? 0, 10000),
            'total_pages' => min($response['total_pages'] ?? 0, 500),
            'results' => []
        ];

        if (isset($response['results']) && is_array($response['results'])) {
            // Tri par date de sortie (plus récent en premier)
            usort($response['results'], function($a, $b) {
                $dateA = $a['release_date'] ?? '0000-00-00';
                $dateB = $b['release_date'] ?? '0000-00-00';

                if (empty($dateA)) $dateA = '0000-00-00';
                if (empty($dateB)) $dateB = '0000-00-00';

                return strcmp($dateB, $dateA);
            });

            // Formatage des résultats pour l'affichage
            foreach ($response['results'] as $movie) {
                $overview = $movie['overview'] ?? '';
                if (strlen($overview) > 150) {
                    $overview = substr($overview, 0, 150) . '...';
                }

                $results['results'][] = [
                    'id' => $movie['id'] ?? 0,
                    'title' => htmlspecialchars($movie['title'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'year' => $this->getYearFromDate($movie['release_date'] ?? ''),
                    'overview' => htmlspecialchars($overview, ENT_QUOTES, 'UTF-8'),
                    'poster' => $this->getPosterUrl($movie['poster_path'] ?? null),
                    'rating' => number_format($movie['vote_average'] ?? 0, 1),
                    'genres' => $this->getGenreLabels($movie['genre_ids'] ?? [])
                ];
            }
        }

        return $results;
    }

    /**
     * Détails d'un film - D'ABORD BDD puis API si nécessaire
     */
    public function getMovieDetails(int $movieId): array
    {
        if ($movieId <= 0) {
            throw new InvalidArgumentException('ID de film invalide');
        }

        // 1. D'ABORD vérifier en BDD
        $movieFromDb = $this->db->getMovie($movieId);

        if ($movieFromDb && $this->isCacheValid($movieFromDb['updated_at'])) {
            // Film trouvé en BDD et cache valide
            $movie = $this->formatMovieFromDb($movieFromDb);

            return [
                'success' => true,
                'source' => 'database',
                'movie' => [
                    'id' => $movie['id'],
                    'title' => htmlspecialchars($movie['title'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'original_title' => htmlspecialchars($movie['original_title'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'year' => $movie['year'],
                    'overview' => htmlspecialchars($movie['overview'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'poster' => $movie['poster_path'] ?: $this->getPosterUrl(null),
                    'backdrop' => $movie['backdrop_path'] ?: '',
                    'rating' => number_format($movie['vote_average'] ?? 0, 1),
                    'vote_count' => number_format($movie['vote_count'] ?? 0),
                    'runtime' => $this->formatDuration($movie['runtime'] ?? 0),
                    'release_date' => $this->formatReleaseDate($movie['release_date'] ?? ''),
                    'genres' => $this->formatGenresFromDb($movie['genres']),
                    'director' => htmlspecialchars($movie['director'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8'),
                    'cast' => $this->formatCastFromDb($movie['cast'])
                ]
            ];
        }

        // 2. SINON faire l'appel API et sauvegarder
        $url = $this->baseUrl . '/movie/' . $movieId . '?' . http_build_query([
                'api_key' => $this->apiKey,
                'language' => DEFAULT_LANGUAGE,
                'append_to_response' => 'credits'
            ]);

        $movie = $this->makeApiCall($url);

        if (!$movie || isset($movie['status_code'])) {
            throw new Exception('Film non trouvé');
        }

        // Enrichir les données
        $movie = $this->enrichMovieData($movie);

        // Nettoyer les URLs d'images avant sauvegarde
        $movieToSave = $this->cleanImageUrlsForSave($movie);

        // Sauvegarder en BDD
        $this->db->saveMovie($movieToSave);

        // Formater pour la réponse
        return [
            'success' => true,
            'source' => 'api',
            'movie' => [
                'id' => $movie['id'],
                'title' => htmlspecialchars($movie['title'] ?? '', ENT_QUOTES, 'UTF-8'),
                'original_title' => htmlspecialchars($movie['original_title'] ?? '', ENT_QUOTES, 'UTF-8'),
                'year' => $this->getYearFromDate($movie['release_date'] ?? ''),
                'overview' => htmlspecialchars($movie['overview'] ?? '', ENT_QUOTES, 'UTF-8'),
                'poster' => $this->getPosterUrl($movie['poster_path'] ?? null),
                'backdrop' => $this->getBackdropUrl($movie['backdrop_path'] ?? null),
                'rating' => number_format($movie['vote_average'] ?? 0, 1),
                'vote_count' => number_format($movie['vote_count'] ?? 0),
                'runtime' => $this->formatDuration($movie['runtime'] ?? 0),
                'release_date' => $this->formatReleaseDate($movie['release_date'] ?? ''),
                'genres' => $this->formatGenresFromApi($movie['genres'] ?? []),
                'director' => htmlspecialchars($movie['director'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8'),
                'cast' => $this->formatCastFromApi($movie['cast'] ?? [])
            ]
        ];
    }


    // ========== MÉTHODES PRIVÉES UTILITAIRES ==========

    /**
     * Effectuer un appel API avec gestion d'erreurs
     */
    private function makeApiCall(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Cinema Explorer/1.1'
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception('Impossible de contacter TMDb');
        }

        $data = json_decode($response, true);

        if (!$data) {
            throw new Exception('Réponse invalide de TMDb');
        }

        return $data;
    }

    /**
     * Logger les recherches
     */
    private function logSearch(string $query): void
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
    private function isCacheValid(string $updatedAt): bool
    {
        $updateTime = strtotime($updatedAt);
        return (time() - $updateTime) < CACHE_DURATION;
    }

    /**
     * Enrichir les données du film (réalisateur + cast)
     */
    private function enrichMovieData(array $movieData): array
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
            $movieData['genres'] = array_map(function($genre) {
                return is_array($genre) && isset($genre['name']) ? $genre['name'] : $genre;
            }, $movieData['genres']);
        }

        return $movieData;
    }

    /**
     * Nettoyer les URLs d'images pour la sauvegarde
     */
    private function cleanImageUrlsForSave(array $movie): array
    {
        if (isset($movie['poster_path']) && strpos($movie['poster_path'], 'http') === 0) {
            $movie['poster_path'] = parse_url($movie['poster_path'], PHP_URL_PATH);
        }
        if (isset($movie['backdrop_path']) && strpos($movie['backdrop_path'], 'http') === 0) {
            $movie['backdrop_path'] = parse_url($movie['backdrop_path'], PHP_URL_PATH);
        }

        return $movie;
    }

    /**
     * Formater un film depuis la BDD
     */
    private function formatMovieFromDb(array $movie): array
    {
        // Formater correctement l'URL du poster depuis la BDD
        $posterUrl = '';
        if (!empty($movie['poster_path'])) {
            if (strpos($movie['poster_path'], 'http') === 0) {
                $posterUrl = $movie['poster_path'];
            } else {
                $posterUrl = $this->imageBaseUrl . $movie['poster_path'];
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
            'genres' => $genres,
            'director' => $movie['director'] ?: 'Non spécifié',
            'cast' => $cast,
            'year' => $movie['release_date'] ? date('Y', strtotime($movie['release_date'])) : 'N/A'
        ];
    }

    /**
     * Obtenir les labels des genres depuis les IDs
     */
    private function getGenreLabels(array $genreIds): string
    {
        if (empty($genreIds)) {
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

    /**
     * Formater la durée
     */
    private function formatDuration(int $minutes): string
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

    /**
     * Formater la date de sortie
     */
    private function formatReleaseDate(string $date): string
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

    /**
     * Extraire l'année d'une date
     */
    private function getYearFromDate(string $date): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        $timestamp = strtotime($date);
        return $timestamp ? date('Y', $timestamp) : 'N/A';
    }

    /**
     * Obtenir l'URL du poster
     */
    private function getPosterUrl(?string $posterPath): string
    {
        if (empty($posterPath)) {
            return 'https://placehold.co/300x450/a7e3d0/0a5045?text=Aucune+image';
        }

        return $this->imageBaseUrl . $posterPath;
    }

    /**
     * Obtenir l'URL du backdrop
     */
    private function getBackdropUrl(?string $backdropPath): string
    {
        if (empty($backdropPath)) {
            return '';
        }

        return 'https://image.tmdb.org/t/p/w780' . $backdropPath;
    }

    /**
     * Formater le cast depuis l'API TMDb
     */
    private function formatCastFromApi(array $cast): string
    {
        if (empty($cast)) {
            return 'Non spécifié';
        }

        $validCast = array_filter($cast, function($actor) {
            return !empty($actor) && is_string($actor);
        });

        return empty($validCast) ? 'Non spécifié' : implode(', ', array_slice($validCast, 0, 5));
    }

    /**
     * Formater les genres depuis l'API TMDb
     */
    private function formatGenresFromApi(array $genres): string
    {
        if (empty($genres)) {
            return 'Non classé';
        }

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
    private function formatGenresFromDb($genres): string
    {
        if (empty($genres)) {
            return 'Non classé';
        }

        if (is_string($genres)) {
            return $genres;
        }

        if (is_array($genres)) {
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
    private function formatCastFromDb($cast): string
    {
        if (empty($cast)) {
            return 'Non spécifié';
        }

        if (is_string($cast)) {
            return $cast;
        }

        if (is_array($cast)) {
            $validCast = array_filter($cast, function($actor) {
                return !empty($actor) && $actor !== 'Array';
            });

            return empty($validCast) ? 'Non spécifié' : implode(', ', array_slice($validCast, 0, 5));
        }

        return 'Non spécifié';
    }
}