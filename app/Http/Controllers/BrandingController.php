<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingController extends Controller
{
    /**
     * Icon files a club can override through CLUB_ICON_DIR, mapped to the
     * neutral default under public/.
     *
     * @var array<string, string>
     */
    public const ICONS = [
        'icon-192.png' => 'icons/icon-192.png',
        'icon-512.png' => 'icons/icon-512.png',
        'favicon.svg' => 'favicon.svg',
    ];

    public function manifest(): JsonResponse
    {
        $clubName = is_string($v = config('app.club_name')) ? $v : '';

        return response()->json([
            'name' => $clubName !== '' ? $clubName.' BeerJudge' : 'BeerJudge',
            'short_name' => 'BeerJudge',
            'description' => $clubName !== ''
                ? 'Homebrew competition judging for '.$clubName
                : 'Homebrew competition judging',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#1c1917',
            'theme_color' => '#b45309',
            'icons' => [
                ['src' => route('branding.icon', 'icon-192.png', false), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => route('branding.icon', 'icon-512.png', false), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    /**
     * Serves the club's own icon from CLUB_ICON_DIR when it holds the file,
     * otherwise the neutral default.
     */
    public function icon(string $file): BinaryFileResponse
    {
        $dir = config('app.club_icon_dir');
        $override = is_string($dir) && $dir !== '' ? rtrim($dir, '/').'/'.$file : null;
        $path = $override !== null && is_file($override) ? $override : public_path(self::ICONS[$file]);

        return response()->file($path, [
            'Content-Type' => str_ends_with($file, '.svg') ? 'image/svg+xml' : 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
