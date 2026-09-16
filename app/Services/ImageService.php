<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
     * Store a product image and return all variant Base64 data URIs.
     *
     * على Wasmer وبيئات الـ Ephemeral Filesystem، لا يمكن الاعتماد على القرص
     * لتخزين الصور — لذا نخزنها كـ Base64 data URIs مباشرةً في قاعدة البيانات.
     *
     * @param UploadedFile $file
     * @param string $directory (ignored — kept for API compatibility)
     * @return array Array of variant Base64 data URIs keyed by size name
     */
    public function store(UploadedFile $file, string $directory = 'products'): array
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            $paths = [];

            // Original WebP (full-size, no resize) → Base64 data URI
            $originalWebP = $image->toWebp(90)->toString();
            $paths['original'] = 'data:image/webp;base64,' . base64_encode($originalWebP);

            // Fallback original JPEG → Base64 data URI
            $originalJpeg = $image->toJpeg(90)->toString();
            $paths['original_fallback'] = 'data:image/jpeg;base64,' . base64_encode($originalJpeg);

            // Generate and store each variant as Base64 data URI
            foreach ($this->sizes as $sizeName => [$width, $height, $quality]) {
                $variant = $manager->read($file->getRealPath());
                $variant->cover($width, $height);

                // WebP variant → Base64
                $webpContent = $variant->toWebp($quality)->toString();
                $paths[$sizeName] = 'data:image/webp;base64,' . base64_encode($webpContent);

                // JPEG fallback variant → Base64
                $jpegContent = $variant->toJpeg($quality)->toString();
                $paths["{$sizeName}_fallback"] = 'data:image/jpeg;base64,' . base64_encode($jpegContent);
            }

            return $paths;
        } catch (\Throwable $e) {
            // Fallback: encode raw file directly as Base64
            $mimeType = $file->getMimeType() ?: 'image/jpeg';
            $base64 = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
            return [
                'original'           => $base64,
                'original_fallback'  => $base64,
                'thumbnail'          => $base64,
                'thumbnail_fallback' => $base64,
                'medium'             => $base64,
                'medium_fallback'    => $base64,
                'large'              => $base64,
                'large_fallback'     => $base64,
            ];
        }
    }

    /**
     * Delete all variants for a given path.
     *
     * مع تخزين Base64 في قاعدة البيانات، لا توجد ملفات على القرص لحذفها.
     * هذه الدالة تحتفظ بالدعم القديم للمسارات الحقيقية للتوافق.
     *
     * @param string $path
     * @return void
     */
    public function delete(string $path): void
    {
        if (empty($path)) return;

        // Base64 data URIs are stored in DB — no filesystem cleanup needed
        if (str_starts_with($path, 'data:')) return;

        // Legacy support: if still a file path, delete from storage disk
        Storage::disk('public')->delete($path);

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
     * With Base64 storage, paths ARE the URLs (data URIs) — returned as-is.
     *
     * @param array $paths
     * @return array
     */
    public function buildSrcSet(array $paths): array
    {
        return [
            'original'           => $paths['original'] ?? null,
            'original_fallback'  => $paths['original_fallback'] ?? null,
            'thumbnail'          => $paths['thumbnail'] ?? null,
            'thumbnail_fallback' => $paths['thumbnail_fallback'] ?? null,
            'medium'             => $paths['medium'] ?? null,
            'medium_fallback'    => $paths['medium_fallback'] ?? null,
            'large'              => $paths['large'] ?? null,
            'large_fallback'     => $paths['large_fallback'] ?? null,
            'srcset_webp'        => implode(', ', array_filter([
                isset($paths['thumbnail']) ? $paths['thumbnail'] . ' 150w' : null,
                isset($paths['medium'])    ? $paths['medium'] . ' 600w'    : null,
                isset($paths['large'])     ? $paths['large'] . ' 1200w'    : null,
            ])),
            'srcset_fallback'    => implode(', ', array_filter([
                isset($paths['thumbnail_fallback']) ? $paths['thumbnail_fallback'] . ' 150w' : null,
                isset($paths['medium_fallback'])    ? $paths['medium_fallback'] . ' 600w'    : null,
                isset($paths['large_fallback'])     ? $paths['large_fallback'] . ' 1200w'    : null,
            ])),
        ];
    }
}
