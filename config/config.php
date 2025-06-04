<?php
/**
 * Configuration de l'application Cinema Explorer
 * Optimisé pour un impact environnemental minimal
 */

// Configuration de l'API TMDb
const TMDB_API_KEY = '16e83367257477dc52e2b01cb02e57e6'; // Remplacez par votre clé API
const TMDB_BASE_URL = 'https://api.themoviedb.org/3';
const TMDB_IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/w500';

// Configuration de la base de données SQLite
define('DB_PATH', dirname(__DIR__) . '/database/cinema.db');

// Configuration des logs
define('LOGS_PATH', dirname(__DIR__) . '/logs');
const SEARCH_LOG_FILE = LOGS_PATH . '/search.log';

// Configuration générale
const DEFAULT_LANGUAGE = 'fr-FR';
const CACHE_DURATION = 3600 * 24 * 7; // 7 jours en secondes

// Configuration pour l'éco-conception
const LOG_SEARCHES = true;

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
    chmod(SEARCH_LOG_FILE, 0666); // Permissions d'écriture pour le serveur web
}
?>