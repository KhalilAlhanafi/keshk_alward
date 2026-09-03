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
        'medium' => [600, 600, 85],
        'large' => [1200, 1200, 90],
    ];

    /**
     * Store a product image and return all variant paths (WebP + fallback).
     *
     * @param UploadedFile $file
     * @param string $directory Base storage directory (e.g. 'products')
     * @return array Array of variant paths keyed by size name
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
            // Fallback: store raw file directly to disk
            $path = $file->store($directory, 'public');
            return [
                'original' => $path,
                'original_fallback' => $path,
                'thumbnail' => $path,
                'thumbnail_fallback' => $path,
                'medium' => $path,
                'medium_fallback' => $path,
                'large' => $path,
                'large_fallback' => $path,
            ];
        }
    }

    /**
     * Delete all variants for a given base path.
     *
     * @param string $path
     * @return void
     */
    public function delete(string $path): void
    {
        if (empty($path)) return;

        // Clean up direct file
        Storage::disk('public')->delete($path);

        // $path may be "products/uuid_medium.webp" - clean variants
        $withoutExt = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
        $baseName = preg_replace('/_(thumbnail|medium|large|original)(_fallback)?$/', '', $withoutExt);

        $extensions = ['webp', 'jpg', 'jpeg', 'png'];
        $variants = ['', '_thumbnail', '_medium', '_large'];

        foreach ($variants as $v) {
            foreach ($extensions as $ext) {
                Storage::disk('public')->delete("{$baseName}{$v}.{$ext}");
            }
        }
    }

    /**
     * Build an array of srcset-ready public URLs from variant paths.
     *
     * @param array $paths
     * @return array
     */
    public function buildSrcSet(array $paths): array
    {
        $srcset = [];
        foreach ($paths as $key => $path) {
            $srcset[$key] = Storage::disk('public')->url($path);
        }

        return [
            'original' => $srcset['original'] ?? null,
            'original_fallback' => $srcset['original_fallback'] ?? null,
            'thumbnail' => $srcset['thumbnail'] ?? null,
            'thumbnail_fallback' => $srcset['thumbnail_fallback'] ?? null,
            'medium' => $srcset['medium'] ?? null,
            'medium_fallback' => $srcset['medium_fallback'] ?? null,
            'large' => $srcset['large'] ?? null,
            'large_fallback' => $srcset['large_fallback'] ?? null,
            'srcset_webp' => implode(', ', array_filter([
                isset($srcset['thumbnail']) ? $srcset['thumbnail'] . ' 150w' : null,
                isset($srcset['medium']) ? $srcset['medium'] . ' 600w' : null,
                isset($srcset['large']) ? $srcset['large'] . ' 1200w' : null,
            ])),
            'srcset_fallback' => implode(', ', array_filter([
                isset($srcset['thumbnail_fallback']) ? $srcset['thumbnail_fallback'] . ' 150w' : null,
                isset($srcset['medium_fallback']) ? $srcset['medium_fallback'] . ' 600w' : null,
                isset($srcset['large_fallback']) ? $srcset['large_fallback'] . ' 1200w' : null,
            ])),
        ];
    }
}
