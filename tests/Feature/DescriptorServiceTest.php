<?php

namespace Tests\Feature;

use App\Services\DescriptorService;
use Tests\TestCase;

class DescriptorServiceTest extends TestCase
{
    public function test_returns_17_descriptors(): void
    {
        $this->assertCount(17, DescriptorService::descriptors());
    }

    public function test_slugs_returns_17_entries(): void
    {
        $this->assertCount(17, DescriptorService::slugs());
    }

    public function test_descriptors_contain_known_slugs(): void
    {
        $slugs = array_column(DescriptorService::descriptors(), 'slug');

        $this->assertContains('diacetyl', $slugs);
        $this->assertContains('acetaldehyde', $slugs);
        $this->assertContains('dms', $slugs);
    }

    public function test_each_descriptor_has_required_keys(): void
    {
        foreach (DescriptorService::descriptors() as $descriptor) {
            $this->assertArrayHasKey('slug', $descriptor);
            $this->assertArrayHasKey('name', $descriptor);
            $this->assertArrayHasKey('description', $descriptor);
        }
    }

    public function test_slugs_matches_descriptor_slugs(): void
    {
        $slugs = DescriptorService::slugs();
        $descriptorSlugs = array_column(DescriptorService::descriptors(), 'slug');

        $this->assertEquals($slugs, $descriptorSlugs);
    }
}
