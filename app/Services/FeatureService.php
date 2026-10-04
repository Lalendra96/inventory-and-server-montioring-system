<?php

namespace App\Services;

use App\Models\FeatureOption;
use Illuminate\Support\Facades\Cache;

class FeatureService
{
    public function enabled(string $key, bool $default = true): bool
    {
        return Cache::remember("feature.{$key}", 60, function () use ($key, $default) {
            $feature = FeatureOption::query()->where('key', $key)->first();
            return $feature ? $feature->enabled : $default;
        });
    }

    public function flush(string $key): void
    {
        Cache::forget("feature.{$key}");
    }
}
