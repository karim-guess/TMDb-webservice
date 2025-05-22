<?php
/**
 * Gestionnaire de base de données SQLite
 * Optimisé pour les performances et l'éco-conception
 */

class DatabaseManager
{
    private $pdo;
    private static ?DatabaseManager $instance = null;

    private function __construct()
    {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->createTables();
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données : " . $e->getMessage());
            throw new Exception("Impossible de se connecter à la base de données");
        }
    }

    public static function getInstance(): DatabaseManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function createTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS movies (
                id INTEGER PRIMARY KEY,
                tmdb_id INTEGER UNIQUE,
                title TEXT NOT NULL,
                original_title TEXT,
                overview TEXT,
                release_date TEXT,
                poster_path TEXT,
                backdrop_path TEXT,
                vote_average REAL,
                vote_count INTEGER,
                genres TEXT,
                runtime INTEGER,
                director TEXT,
                cast TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );


            CREATE INDEX IF NOT EXISTS idx_movies_tmdb_id ON movies(tmdb_id);
        ";

        $this->pdo->exec($sql);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getMovie($tmdbId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM movies WHERE tmdb_id = ?");
        $stmt->execute([$tmdbId]);
        return $stmt->fetch();
    }

    public function saveMovie($movieData): bool
    {
        $sql = "
            INSERT OR REPLACE INTO movies 
            (tmdb_id, title, original_title, overview, release_date, poster_path, 
             backdrop_path, vote_average, vote_count, genres, runtime, director, cast, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $movieData['id'],
            $movieData['title'],
            $movieData['original_title'] ?? '',
            $movieData['overview'] ?? '',
            $movieData['release_date'] ?? '',
            $movieData['poster_path'] ?? '',
            $movieData['backdrop_path'] ?? '',
            $movieData['vote_average'] ?? 0,
            $movieData['vote_count'] ?? 0,
            isset($movieData['genres']) ? json_encode($movieData['genres']) : '',
            $movieData['runtime'] ?? 0,
            $movieData['director'] ?? '',
            isset($movieData['cast']) ? json_encode($movieData['cast']) : ''
        ]);
    }

}