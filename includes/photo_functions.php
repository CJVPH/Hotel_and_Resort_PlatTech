<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Get photos for a specific section
 */
function getPhotosForSection($section, $limit = null) {
    try {
        $pdo = getPDOConnection();
        
        $sql = "SELECT * FROM website_photos WHERE section = ? AND is_active = 1 ORDER BY sort_order ASC, upload_date DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$section]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching photos for section $section: " . $e->getMessage());
        return [];
    }
}

/**
 * Get default photos if no uploaded photos exist
 */
function getDefaultPhotos($section) {
    $defaults = [
        'carousel' => [
            'uploads/carousel/carousel_1.jpg',
            'uploads/carousel/carousel_2.jpg',
            'uploads/carousel/carousel_3.jpg',
            'uploads/carousel/carousel_4.jpg',
            'uploads/carousel/carousel_5.jpg'
        ],
        'pool' => [
            'uploads/pool/pool_1.jpg',
            'uploads/pool/pool_2.jpg',
            'uploads/pool/pool_3.jpg'
        ],
        'spa' => [
            'uploads/spa/spa_1.jpg',
            'uploads/spa/spa_2.jpg',
            'uploads/spa/spa_3.jpg'
        ],
        'restaurant' => [
            'uploads/restaurant/restaurant_1.jpg',
            'uploads/restaurant/restaurant_2.jpg',
            'uploads/restaurant/restaurant_3.jpg'
        ]
    ];
    
    return $defaults[$section] ?? [];
}

/**
 * Get photos with fallback to defaults
 */
function getPhotosWithFallback($section, $limit = null) {
    $photos = getPhotosForSection($section, $limit);
    
    if (empty($photos)) {
        $defaultPaths = getDefaultPhotos($section);
        $photos = [];
        foreach ($defaultPaths as $path) {
            $photos[] = [
                'file_path' => $path,
                'original_name' => basename($path),
                'filename' => basename($path)
            ];
        }
        
        if ($limit) {
            $photos = array_slice($photos, 0, $limit);
        }
    }
    
    return $photos;
}

/**
 * Get single photo for a section
 */
function getSinglePhoto($section) {
    $photos = getPhotosWithFallback($section, 1);
    return !empty($photos) ? $photos[0] : null;
}

/**
 * Check if photo file exists
 */
function photoExists($filePath) {
    return file_exists(__DIR__ . '/../' . $filePath);
}
?>