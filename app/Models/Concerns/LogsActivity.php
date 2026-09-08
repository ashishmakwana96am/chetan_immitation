<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogger;

trait LogsActivity
{
    protected static bool $activityLogSuppressed = false;

    public static function withoutActivityLogging(\Closure $callback)
    {
        $previous = static::$activityLogSuppressed;
        static::$activityLogSuppressed = true;

        try {
            return $callback();
        } finally {
            static::$activityLogSuppressed = $previous;
        }
    }

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

    public function activityModule(): string
    {
        return class_basename(static::class);
    }

    public function activityLabel(): string
    {
        if (isset($this->invoice_no) && !empty($this->invoice_no)) {
            return $this->invoice_no;
        }
        if (isset($this->order_no) && !empty($this->order_no)) {
            return $this->order_no;
        }
        if (isset($this->transfer_no) && !empty($this->transfer_no)) {
            return $this->transfer_no;
        }
        if (isset($this->name) && !empty($this->name)) {
            return $this->name;
        }
        if (isset($this->title) && !empty($this->title)) {
            return $this->title;
        }
        return '#' . $this->getKey();
    }

    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            if (static::$activityLogSuppressed) {
                return;
            }
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
                $model->activityModule() . ' ' . $model->activityLabel() . ' created'
            );
        });

        static::updated(function ($model) {
            if (static::$activityLogSuppressed) {
                return;
            }

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
                $model->activityModule() . ' ' . $model->activityLabel() . ' updated'
            );
        });

        static::deleted(function ($model) {
            if (static::$activityLogSuppressed) {
                return;
            }

            $attributes = self::stripHidden($model->getAttributes());
            ActivityLogger::log(
                $model->activityModule(),
                'delete',
                $model,
                $attributes,
                null,
                $model->activityModule() . ' ' . $model->activityLabel() . ' deleted'
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
