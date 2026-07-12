<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\Http;

trait FetchesPexelsJewelryPhotos
{
    /**
     * Pexels photo IDs already handed out during this command run, so the
     * same photo is never reused across categories/products/images.
     */
    protected array $usedPexelsPhotoIds = [];

    protected array $jewelryKeywords = [
        'jewel', 'jewelry', 'jewellery', 'necklace', 'earring', 'earrings',
        'ring', 'rings', 'bangle', 'bangles', 'bracelet', 'bracelets',
        'pendant', 'pendants', 'anklet', 'anklets', 'brooch', 'brooches',
        'ornament', 'ornaments', 'accessory', 'accessories', 'gem', 'gems',
        'gold', 'silver', 'diamond', 'tikka', 'mangalsutra', 'nose ring',
        'hairpin', 'hair pin', 'chain', 'chains', 'kada', 'jhumka', 'jhumkas',
        'bridal', 'gemstone',
    ];

    /**
     * Search Pexels for real photos relevant to jewelry and return up to
     * $count unique, not-yet-used image URLs.
     *
     * @return string[]
     */
    protected function searchJewelryPhotos(string $apiKey, string $searchTerm, int $count): array
    {
        $response = Http::withHeaders(['Authorization' => $apiKey])
            ->timeout(15)
            ->get('https://api.pexels.com/v1/search', [
                'query' => $searchTerm,
                'per_page' => min(max($count * 3, 15), 80),
                'orientation' => 'square',
            ]);

        if (! $response->successful()) {
            return [];
        }

        $photos = $response->json('photos') ?? [];

        $relevant = array_values(array_filter($photos, function ($photo) {
            $alt = strtolower($photo['alt'] ?? '');

            foreach ($this->jewelryKeywords as $keyword) {
                if (str_contains($alt, $keyword)) {
                    return true;
                }
            }

            return false;
        }));

        // If the alt-text filter is too strict for this search term, fall
        // back to the raw (still query-matched) results rather than skip.
        $candidates = ! empty($relevant) ? $relevant : $photos;

        $picked = [];

        foreach ($candidates as $photo) {
            $id = $photo['id'] ?? null;
            $url = $photo['src']['large'] ?? null;

            if (! $url || $id === null || in_array($id, $this->usedPexelsPhotoIds, true)) {
                continue;
            }

            $this->usedPexelsPhotoIds[] = $id;
            $picked[] = $url;

            if (count($picked) >= $count) {
                break;
            }
        }

        return $picked;
    }

    /**
     * Download a photo URL into $destDir and return its path relative to
     * the public/uploads directory (e.g. "categories/xyz.jpg").
     */
    protected function downloadPhotoTo(string $url, string $destDir, string $relativeSubDir): ?string
    {
        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            return null;
        }

        $extension = $this->extensionFromUrl($url);
        $filename = time().'_'.uniqid().'.'.$extension;
        file_put_contents($destDir.DIRECTORY_SEPARATOR.$filename, $response->body());

        return $relativeSubDir.'/'.$filename;
    }

    protected function extensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp']) ? $extension : 'jpg';
    }
}
