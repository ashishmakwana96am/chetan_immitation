<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogger;

trait LogsActivity
{
    /**
     * Attribute keys that must never be written to the activity log,
     * even as part of an old/new value diff.
     */
    protected static function activityHiddenKeys(): array
    {
        return array_merge(
            ['password', 'remember_token'],
            property_exists(static::class, 'activityHidden') ? static::$activityHidden : []
        );
    }

    /**
     * Human-readable module label for this model. Override per-model
     * when the class name isn't the label you want in the report.
     */
    public function activityModule(): string
    {
        return class_basename(static::class);
    }

    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            if (property_exists(static::class, 'logCreate') && static::$logCreate === false) {
                return;
            }

            $attributes = self::stripHidden($model->getAttributes());
            ActivityLogger::log(
                $model->activityModule(),
                'create',
                $model,
                null,
                $attributes,
                $model->activityModule() . ' #' . $model->getKey() . ' created'
            );
        });

        static::updated(function ($model) {
            $changes = self::stripHidden($model->getChanges());
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            $original = self::stripHidden($model->getOriginal());
            $old = array_intersect_key($original, $changes);

            ActivityLogger::log(
                $model->activityModule(),
                'update',
                $model,
                $old,
                $changes,
                $model->activityModule() . ' #' . $model->getKey() . ' updated'
            );
        });

        static::deleted(function ($model) {
            $attributes = self::stripHidden($model->getAttributes());
            ActivityLogger::log(
                $model->activityModule(),
                'delete',
                $model,
                $attributes,
                null,
                $model->activityModule() . ' #' . $model->getKey() . ' deleted'
            );
        });
    }

    protected static function stripHidden(array $attributes): array
    {
        foreach (self::activityHiddenKeys() as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }
}
