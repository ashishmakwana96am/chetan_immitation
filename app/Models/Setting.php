<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes;

    public static function bootSoftDeletes()
    {
        if (!app()->runningInConsole() || (\Illuminate\Support\Facades\Schema::hasTable('settings') && \Illuminate\Support\Facades\Schema::hasColumn('settings', 'deleted_at'))) {
            static::addGlobalScope(new \Illuminate\Database\Eloquent\SoftDeletingScope);
        }
    }

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }
        $value = $setting->value;
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public static function setValue(string $key, mixed $value): void
    {
        $stored = is_array($value) ? json_encode($value) : $value;
        self::updateOrCreate(['key' => $key], ['value' => $stored]);
    }

    public static function getInstagramProfileUrl(): string
    {
        return self::getValue('instagram_profile_url', 'https://www.instagram.com/chetan_imitation?igsh=Zm9lNHNoaTQ3c2t4');
    }

    public static function getInstagramPosts(): array
    {
        $profileUrl = self::getInstagramProfileUrl();
        $accessToken = self::getValue('instagram_access_token', 'IGAAWZA8ewYnlBBZAFk2SVA1ZAHNpclhYVUh3dWo4STFRNTRvMGN3VnlXWG1hMGJmTGpoWGNYTExJSnQ1UFBNRFAzUEV2b1pmWVBpS1JSbDZApWEdCZAW85UW8zXzZATMGttVTVEdGNjMms1NnBVNDg3cWRqRW5ENEZA2N1FYdGVIeDkzdwZDZD');

        if (!empty($accessToken)) {
            return \Illuminate\Support\Facades\Cache::remember('instagram_feed_posts_v6', 3600, function () use ($accessToken, $profileUrl) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(5)->get('https://graph.instagram.com/me/media', [
                        'fields'       => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp',
                        'access_token' => $accessToken,
                        'limit'        => 50,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json('data') ?? [];
                        if (!empty($data)) {
                            $reels = collect($data)->filter(function ($post) {
                                return in_array($post['media_type'] ?? '', ['VIDEO', 'REEL']);
                            });

                            if ($reels->isNotEmpty()) {
                                return $reels->take(6)->map(function ($post) use ($profileUrl) {
                                    return [
                                        'image'      => $post['thumbnail_url'] ?? $post['media_url'],
                                        'link'       => $post['permalink'] ?? $profileUrl,
                                        'caption'    => $post['caption'] ?? 'Instagram Reel',
                                        'media_type' => $post['media_type'] ?? 'VIDEO',
                                    ];
                                })->toArray();
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Instagram Feed Fetch Error: ' . $e->getMessage());
                }
                return self::getDefaultInstagramPosts($profileUrl);
            });
        }

        return self::getDefaultInstagramPosts($profileUrl);
    }

    public static function getDefaultInstagramPosts($profileUrl = null): array
    {
        $profileUrl = $profileUrl ?? self::getInstagramProfileUrl();
        return [
            ['image' => asset('website/assets/images/Rectangle1.png'), 'link' => $profileUrl, 'caption' => 'Follow us on Instagram'],
            ['image' => asset('website/assets/images/Rectangle2.png'), 'link' => $profileUrl, 'caption' => 'Follow us on Instagram'],
            ['image' => asset('website/assets/images/Rectangle3.png'), 'link' => $profileUrl, 'caption' => 'Follow us on Instagram'],
            ['image' => asset('website/assets/images/Rectangle4.png'), 'link' => $profileUrl, 'caption' => 'Follow us on Instagram'],
            ['image' => asset('website/assets/images/Rectangle5.png'), 'link' => $profileUrl, 'caption' => 'Follow us on Instagram'],
            ['image' => asset('website/assets/images/Rectangle6.png'), 'link' => $profileUrl, 'caption' => 'Follow us on Instagram'],
        ];
    }
}
