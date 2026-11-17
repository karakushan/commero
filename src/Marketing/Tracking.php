<?php

namespace Commero\Marketing;

use Commero\Models\SiteSetting;
use Throwable;

/**
 * Analytics / marketing integrations from the site settings (Google Tag Manager, Google Analytics 4,
 * Meta Pixel and custom code), rendered by the <x-commero::tracking.*> components.
 */
class Tracking
{
    public static function setting(): ?SiteSetting
    {
        return once(function (): ?SiteSetting {
            try {
                return SiteSetting::query()->first();
            } catch (Throwable) {
                return null;
            }
        });
    }

    /**
     * COMMERO_TRACKING_ENABLED=false turns the codes off (local / staging environments).
     */
    public static function enabled(): bool
    {
        return (bool) config('commero.tracking.enabled', true);
    }

    public static function googleTagManagerId(): ?string
    {
        return static::id(static::setting()?->google_tag_manager_id);
    }

    public static function googleAnalyticsId(): ?string
    {
        return static::id(static::setting()?->google_analytics_id);
    }

    public static function facebookPixelId(): ?string
    {
        return static::id(static::setting()?->facebook_pixel_id);
    }

    public static function headCode(): ?string
    {
        return static::enabled() && filled(static::setting()?->head_code) ? static::setting()->head_code : null;
    }

    public static function bodyCode(): ?string
    {
        return static::enabled() && filled(static::setting()?->body_code) ? static::setting()->body_code : null;
    }

    private static function id(?string $value): ?string
    {
        if (! static::enabled()) {
            return null;
        }

        $value = trim((string) $value);

        return preg_match('/^[A-Za-z0-9_-]+$/', $value) === 1 ? $value : null;
    }
}
