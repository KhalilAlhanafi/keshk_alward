<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    /**
     * Defined variant sizes: [key => [width, height, quality]]
     */
    protected array $sizes = [
        'thumbnail' => [150, 150, 80],
        'medium'    => [600, 600, 85],
        'large'     => [1200, 1200, 90],
    ];

    /**
     * Store an image on disk and return all variant storage paths.
     * Generates optimized WebP files with JPEG fallbacks.
     *
     * @param UploadedFile $file
     * @param string $directory Base storage directory (e.g. 'products')
     * @return array Array of variant relative storage paths keyed by size name
     */
    public function store(UploadedFile $file, string $directory = 'products'): array
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            $baseName = Str::uuid()->toString();
            $paths = [];

            // Store original WebP (full-size, no resize)
            $originalWebP = $image->toWebp(90)->toString();
            $originalPath = "{$directory}/{$baseName}.webp";
            Storage::disk('public')->put($originalPath, $originalWebP);
            $paths['original'] = $originalPath;

            // Store fallback original JPEG
            $originalJpeg = $image->toJpeg(90)->toString();
            $originalFallbackPath = "{$directory}/{$baseName}.jpg";
            Storage::disk('public')->put($originalFallbackPath, $originalJpeg);
            $paths['original_fallback'] = $originalFallbackPath;

            // Generate and store each variant
            foreach ($this->sizes as $sizeName => [$width, $height, $quality]) {
                $variant = $manager->read($file->getRealPath());
                $variant->cover($width, $height);

                // Store WebP variant
                $webpContent = $variant->toWebp($quality)->toString();
                $webpPath = "{$directory}/{$baseName}_{$sizeName}.webp";
                Storage::disk('public')->put($webpPath, $webpContent);
                $paths[$sizeName] = $webpPath;

                // Store JPEG fallback variant
                $jpegContent = $variant->toJpeg($quality)->toString();
                $jpegPath = "{$directory}/{$baseName}_{$sizeName}.jpg";
                Storage::disk('public')->put($jpegPath, $jpegContent);
                $paths["{$sizeName}_fallback"] = $jpegPath;
            }

            return $paths;
        } catch (\Throwable $e) {
            // Fallback: store raw file directly to public disk
            $path = $file->store($directory, 'public');
            return [
                'original'           => $path,
                'original_fallback'  => $path,
                'thumbnail'          => $path,
                'thumbnail_fallback' => $path,
                'medium'             => $path,
                'medium_fallback'    => $path,
                'large'              => $path,
                'large_fallback'     => $path,
            ];
        }
    }

    /**
     * Delete all variants for a given storage path from the disk.
     *
     * @param string|null $path
     * @return void
     */
    public function delete(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        // Data URIs are not on the filesystem
        if (str_starts_with($path, 'data:')) {
            return;
        }

        // Delete direct file
        Storage::disk('public')->delete($path);

        // Clean up any other size/format variants
        $withoutExt = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
        $baseName = preg_replace('/_(thumbnail|medium|large|original)(_fallback)?$/', '', $withoutExt);

        $extensions = ['webp', 'jpg', 'jpeg', 'png'];
        $variants   = ['', '_thumbnail', '_medium', '_large'];

        foreach ($variants as $v) {
            foreach ($extensions as $ext) {
                Storage::disk('public')->delete("{$baseName}{$v}.{$ext}");
            }
        }
    }

    /**
     * Build an array of srcset-ready URLs from variant paths.
     *
     * @param array $paths
     * @return array
     */
    public function buildSrcSet(array $paths): array
    {
        $toUrl = function (?string $p) {
            if (!$p) return null;
            if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://') || str_starts_with($p, 'data:')) {
                return $p;
            }
            return asset('storage/' . ltrim($p, '/'));
        };

        $urls = [
            'original'           => $toUrl($paths['original'] ?? null),
            'original_fallback'  => $toUrl($paths['original_fallback'] ?? null),
            'thumbnail'          => $toUrl($paths['thumbnail'] ?? null),
            'thumbnail_fallback' => $toUrl($paths['thumbnail_fallback'] ?? null),
            'medium'             => $toUrl($paths['medium'] ?? null),
            'medium_fallback'    => $toUrl($paths['medium_fallback'] ?? null),
            'large'              => $toUrl($paths['large'] ?? null),
            'large_fallback'     => $toUrl($paths['large_fallback'] ?? null),
        ];

        $urls['srcset_webp'] = implode(', ', array_filter([
            isset($urls['thumbnail']) ? $urls['thumbnail'] . ' 150w' : null,
            isset($urls['medium'])    ? $urls['medium'] . ' 600w'    : null,
            isset($urls['large'])     ? $urls['large'] . ' 1200w'    : null,
        ]));

        $urls['srcset_fallback'] = implode(', ', array_filter([
            isset($urls['thumbnail_fallback']) ? $urls['thumbnail_fallback'] . ' 150w' : null,
            isset($urls['medium_fallback'])    ? $urls['medium_fallback'] . ' 600w'    : null,
            isset($urls['large_fallback'])     ? $urls['large_fallback'] . ' 1200w'    : null,
        ]));

        return $urls;
    }
}
