<?php

namespace App\Investor\Support;

use App\Models\Admin\BasicSettings;

/**
 * Company identity for the header of every document.
 *
 * The logo is resolved to a path on disk, not a URL: dompdf renders with remote
 * fetching disabled, so an <img> pointing at the site would silently come out
 * blank. If the file is not there the documents fall back to the company name
 * set in the admin panel, which is correct but plain.
 */
final class Branding
{
    public static function get(): array
    {
        $settings = BasicSettings::first();

        return [
            'name' => $settings->site_name ?? config('app.name', 'Gold Investment'),
            'logo' => self::logoPath($settings?->site_logo),
        ];
    }

    private static function logoPath(?string $file): ?string
    {
        if (! $file) {
            return null;
        }

        try {
            $path = base_path(files_asset_path_basename('image-assets') . '/' . $file);
        } catch (\Throwable) {
            return null;
        }

        return is_file($path) ? $path : null;
    }
}
