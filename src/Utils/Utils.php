<?php

use JetBrains\PhpStorm\NoReturn;

/**
 * Classe utilitaire pour l'application
 * Fonctions d'aide et optimisations éco-responsables
 */

class Utils
{
    /**
     * Nettoie et sécurise une chaîne pour l'affichage
     */
    public static function sanitize($string): string
    {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Génère une image placeholder éco-responsable
     */
    public static function getPlaceholderImage($width = 300, $height = 450): string
    {
        $color1 = 'a7e3d0'; // Vert clair du thème
        $color2 = '0a5045'; // Vert foncé du thème

        return "https://placehold.co/{$width}x{$height}/{$color1}/{$color2}";
    }

    /**
     * Génère des métadonnées pour l'optimisation SEO et éco-conception
     */
    public static function generateMetadata($title = 'Cinéma Explorer', $description = null): array
    {
        $defaultDescription = 'Application éco-responsable de recherche de films utilisant l\'API TMDb. Optimisée pour un impact environnemental minimal.';

        return [
            'title' => self::sanitize($title),
            'description' => self::sanitize($description ?? $defaultDescription),
            'canonical' => $_SERVER['REQUEST_URI'] ?? '/',
            'cache_duration' => 3600 // 1 heure
        ];
    }
}
