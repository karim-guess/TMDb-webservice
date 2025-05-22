<?php
/**
 * Classe pour gérer les logs des recherches
 */

require_once 'config.php';

class Logger
{
    private $logFile;

    public function __construct()
    {
        $this->logFile = LOG_FILE;
    }

    /**
     * Enregistre une recherche dans le fichier de log
     * 
     * @param string $searchQuery Terme de recherche
     * @param int $resultsCount Nombre de résultats trouvés
     */
    public function logSearch($searchQuery, $resultsCount = 0)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] Recherche: '%s' - %d résultat(s) trouvé(s)\n",
            $timestamp,
            $searchQuery,
            $resultsCount
        );

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Récupère les dernières recherches depuis le fichier de log
     * 
     * @param int $limit Nombre maximum de lignes à récupérer
     * @return array Dernières recherches
     */
    public function getRecentSearches($limit = 10)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (!$lines) {
            return [];
        }

        // Récupère les dernières lignes
        $recentLines = array_slice($lines, -$limit);
        
        // Inverse l'ordre pour avoir les plus récentes en premier
        return array_reverse($recentLines);
    }
}
?>