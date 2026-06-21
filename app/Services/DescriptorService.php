<?php

namespace App\Services;

class DescriptorService
{
    private const SLUGS = [
        'acetaldehyde', 'alcoholic', 'astringent', 'diacetyl', 'dms',
        'estery', 'grassy', 'light_struck', 'metallic', 'musty',
        'oxidized', 'phenolic', 'solvent', 'sour_acidic', 'sulfur',
        'vegetal', 'yeasty',
    ];

    /**
     * @return array<int, array{slug: string, name: string, description: string}>
     */
    public static function descriptors(): array
    {
        return array_map(fn (string $slug) => [
            'slug' => $slug,
            'name' => __("descriptor.{$slug}.name"),
            'description' => __("descriptor.{$slug}.description"),
        ], self::SLUGS);
    }

    /** @return array<int, string> */
    public static function slugs(): array
    {
        return self::SLUGS;
    }
}
