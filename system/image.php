<?php

class Image
{
    private $registry = [];

    public function __construct($registry)
    {
        $this->registry = $registry;

        // clear images in cache older than 

        $cachePath = BASE_DIR . 'resources/cache/images/';
        $files = glob($cachePath . '*.webp');
        if ($files) {
            foreach ($files as $file) {
                if (filemtime($file) < time() - 3600) {
                    unlink($file);
                }
            }
        }

    }

    public function image($path, $width, $height = null)
    {
        //return null;
        // echo $path, ' ', $width, ' ', $height;
        $cachePath = BASE_DIR . 'resources/cache/images/';

        if (empty($path))
            return null;

        //file_put_contents('log.log', $path . "\n\n", FILE_APPEND);

        // check if path already has resources/images/
        if (strpos(realpath($path), realpath(BASE_DIR . 'resources/images/')) === false) {
            $path = BASE_DIR . 'resources/images/' . $path;
        }

        //file_put_contents('log.log', $path . "\n\n", FILE_APPEND);


        // Ensure the cache directory exists
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        if (!file_exists($path)) {
            return null;
        }

        // Calculate target height if only width is provided
        list($originalWidth, $originalHeight) = getimagesize($path);
        if ($height === null) {
            $aspectRatio = $originalHeight / $originalWidth;
            $height = (int) ($width * $aspectRatio);
        }

        // Generate cache file name
        $cachedImagePath = $cachePath . md5($path . $width . $height) . '.webp';


        // Check if cached file already exists
        if (!file_exists($cachedImagePath)) {
            // Load original image
            $sourceImage = imagecreatefromstring(file_get_contents($path));
            $resizedImage = imagescale($sourceImage, $width, $height);

            // Convert to WebP and save in cache
            imagewebp($resizedImage, $cachedImagePath);
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
        }

        // remove base path from image path
        $cachedImagePath = str_replace(BASE_DIR, '/', $cachedImagePath);


        return $cachedImagePath;
    }

    public function replaceImages($content)
    {
        $html = preg_replace_callback(
            '/<img\s+[^>]*src\s*=\s*"([^"]+)"[^>]*(?:width\s*=\s*"(\d+)")?[^>]*(?:height\s*=\s*"(\d+)")?[^>]*>/i',
            function ($matches) {
                $oldSrc = $matches[1];

                // Check if the image is from the same server (relative path or specific domain)
                if (strpos($oldSrc, 'http') !== 0 || strpos($oldSrc, 'rotak.') !== false) {
                    // Set width and height based on the attributes, or use defaults if not set
                    $set_width = !empty($matches[2]) ? (int) $matches[2] : 900;
                    $set_height = !empty($matches[3]) ? (int) $matches[3] : null;

                    // Call $this->image->image method with $oldSrc, $set_height, and $set_width
                    $newSrc = $this->image($oldSrc, $set_width, $set_height);

                    // Replace the old src with the new one
                    return str_replace($oldSrc, $newSrc, $matches[0]);
                }

                // If not on the same server, return the original tag without changes
                return $matches[0];
            },
            $content
        );

        return $html;
    }

    public function getNthImage($content, $index = 0)
    {
        preg_match_all('/<img\s+[^>]*src\s*=\s*"([^"]+)"[^>]*>/i', $content, $matches);
        if (isset($matches[1][$index])) {
            return $matches[1][$index];
        }
        return null;
    }

    public function __get($name)
    {
        // Fetch from the registry if it exists
        if (isset($this->registry->registry[$name])) {
            return $this->registry->registry[$name];
        }
        return null; // Or throw an error if you want strict behavior
    }


}