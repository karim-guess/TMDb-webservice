<?php
/**
 * Configuration de l'application Cinema Explorer
 * Optimisé pour un impact environnemental minimal
 */

// Configuration de l'API TMDb
define('TMDB_API_KEY', '16e83367257477dc52e2b01cb02e57e6'); // Remplacez par votre clé API
define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/w500');

// Configuration de la base de données SQLite
define('DB_PATH', dirname(__DIR__) . '/database/cinema.db');

// Configuration des logs
define('LOGS_PATH', dirname(__DIR__) . '/logs');
define('SEARCH_LOG_FILE', LOGS_PATH . '/search.log');

// Configuration générale
define('DEFAULT_LANGUAGE', 'fr-FR');
define('CACHE_DURATION', 3600 * 24 * 7); // 7 jours en secondes
define('MAX_RESULTS_PER_PAGE', 20);

// Configuration pour l'éco-conception
define('ENABLE_IMAGE_OPTIMIZATION', true);
define('LOG_SEARCHES', true);

// Timezone
date_default_timezone_set('Europe/Paris');

// Vérification que les dossiers nécessaires existent
if (!file_exists(dirname(DB_PATH))) {
    mkdir(dirname(DB_PATH), 0755, true);
}

if (!file_exists(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0755, true);
}

// Créer le fichier de log s'il n'existe pas
if (!file_exists(SEARCH_LOG_FILE)) {
    $initialLog = date('Y-m-d H:i:s') . " - Initialisation du système de logs" . PHP_EOL;
    file_put_contents(SEARCH_LOG_FILE, $initialLog, LOCK_EX);
    chmod(SEARCH_LOG_FILE, 0666); // Permissions d'écriture pour le serveur web
}
?>