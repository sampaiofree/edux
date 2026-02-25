<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CityCampaignCache
{
    private const COURSES_VERSION_KEY = 'city_campaign:version:courses';

    private const SETTINGS_VERSION_KEY = 'city_campaign:version:settings';

    public static function coursesVersion(): int
    {
        return self::version(self::COURSES_VERSION_KEY);
    }

    public static function settingsVersion(): int
    {
        return self::version(self::SETTINGS_VERSION_KEY);
    }

    public static function catalogDataKey(): string
    {
        return 'city_campaign:data:courses:v'.self::coursesVersion();
    }

    public static function settingsDataKey(): string
    {
        return 'city_campaign:data:settings:v'.self::settingsVersion();
    }

    public static function bumpCourses(): void
    {
        self::bump(self::COURSES_VERSION_KEY);
    }

    public static function bumpSettings(): void
    {
        self::bump(self::SETTINGS_VERSION_KEY);
    }

    private static function version(string $key): int
    {
        $value = (int) Cache::get($key, 0);

        if ($value >= 1) {
            return $value;
        }

        Cache::forever($key, 1);

        return 1;
    }

    private static function bump(string $key): void
    {
        Cache::forever($key, self::version($key) + 1);
    }
}
