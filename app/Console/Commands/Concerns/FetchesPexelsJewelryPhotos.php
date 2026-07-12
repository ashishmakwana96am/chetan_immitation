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
        'gemstone',
    ];

    /**
     * Any photo whose alt text matches one of these (as a whole word) is
     * dropped, even if it also mentions jewelry — we only want product-only
     * shots, never a person/model wearing the piece.
     */
    protected array $excludedHumanKeywords = [
        'woman', 'women', 'girl', 'girls', 'lady', 'ladies', 'man', 'men',
        'boy', 'boys', 'person', 'people', 'human', 'model', 'models',
        'portrait', 'face', 'hand', 'hands', 'wrist', 'wrists', 'finger',
        'fingers', 'neck', 'ear', 'ears', 'wearing', 'bride', 'bridal',
        'groom', 'she', 'her', 'him', 'his', 'selfie', 'smiling', 'skin',
    ];

    /**
     * Search Pexels for real, people-free photos relevant to jewelry and
     * return up to $count unique, not-yet-used image URLs.
     *
     * @return string[]
     */
    protected function searchJewelryPhotos(string $apiKey, string $searchTerm, int $count, bool $allowDuplicates = false): array
    {
        $response = $this->pexelsSearchWithRetry($apiKey, $searchTerm, min(max($count * 5, 25), 80));

        if (! $response) {
            return [];
        }

        $photos = $response->json('photos') ?? [];

        // Hard filter: never let a photo showing a person through, even if
        // it also matches a jewelry keyword. This never falls back.
        $peopleFree = array_values(array_filter($photos, function ($photo) {
            $alt = strtolower($photo['alt'] ?? '');

            foreach ($this->excludedHumanKeywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'\b/', $alt)) {
                    return false;
                }
            }

            return true;
        }));

        $relevant = array_values(array_filter($peopleFree, function ($photo) {
            $alt = strtolower($photo['alt'] ?? '');

            foreach ($this->jewelryKeywords as $keyword) {
                if (str_contains($alt, $keyword)) {
                    return true;
                }
            }

            return false;
        }));

        // If the jewelry-keyword filter is too strict for this search term,
        // fall back to the people-free pool (never the raw, unfiltered one).
        $candidates = ! empty($relevant) ? $relevant : $peopleFree;

        $picked = [];

        foreach ($candidates as $photo) {
            $id = $photo['id'] ?? null;
            $url = $photo['src']['large'] ?? null;

            if (! $url || $id === null) {
                continue;
            }

            $alreadyUsed = in_array($id, $this->usedPexelsPhotoIds, true);

            if ($alreadyUsed && ! $allowDuplicates) {
                continue;
            }

            if (! $alreadyUsed) {
                $this->usedPexelsPhotoIds[] = $id;
            }

            $picked[] = $url;

            if (count($picked) >= $count) {
                break;
            }
        }

        return $picked;
    }

    /**
     * Call the Pexels search endpoint, automatically waiting out and
     * retrying a 429 rate-limit response instead of giving up — so a long
     * run (100+ products) survives crossing the hourly request quota
     * instead of failing every item after the limit is hit.
     */
    protected function pexelsSearchWithRetry(string $apiKey, string $searchTerm, int $perPage, int $maxRetries = 3)
    {
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $response = Http::withHeaders(['Authorization' => $apiKey])
                ->timeout(15)
                ->get('https://api.pexels.com/v1/search', [
                    'query' => $searchTerm,
                    'per_page' => $perPage,
                    'orientation' => 'square',
                ]);

            if ($response->successful()) {
                return $response;
            }

            if ($response->status() === 429 && $attempt < $maxRetries) {
                // Pexels sends the epoch timestamp its quota resets at; trust
                // it (capped at 30 min) rather than a short guess, so we
                // actually clear the window instead of retrying too soon.
                $resetAt = (int) $response->header('X-Ratelimit-Reset');
                $waitSeconds = $resetAt > time() ? ($resetAt - time() + 2) : 60;
                $waitSeconds = min($waitSeconds, 1800);

                if (method_exists($this, 'warn')) {
                    $this->warn("Pexels rate limit hit, waiting {$waitSeconds}s before retrying...");
                }

                sleep($waitSeconds);

                continue;
            }

            return null;
        }

        return null;
    }

    /**
     * Try each search term in order (most specific first), topping up the
     * result set from the next, broader term whenever the previous one
     * couldn't fill the quota. Guarantees the best possible chance of
     * reaching $count without ever returning a person/model photo.
     *
     * @param  string[]  $searchTerms
     * @return string[]
     */
    protected function searchJewelryPhotosBroadening(string $apiKey, array $searchTerms, int $count): array
    {
        $picked = [];

        foreach ($searchTerms as $term) {
            $term = trim((string) $term);

            if ($term === '' || count($picked) >= $count) {
                continue;
            }

            $found = $this->searchJewelryPhotos($apiKey, $term, $count - count($picked));
            $picked = array_merge($picked, $found);
        }

        // Last resort: every unique candidate is exhausted. Reuse a photo
        // already used elsewhere in this run rather than leaving this
        // product/category with zero images.
        if (empty($picked)) {
            foreach ($searchTerms as $term) {
                $term = trim((string) $term);

                if ($term === '') {
                    continue;
                }

                $found = $this->searchJewelryPhotos($apiKey, $term, $count, allowDuplicates: true);

                if (! empty($found)) {
                    $picked = $found;
                    break;
                }
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
