<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    private string $iconDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->iconDir = sys_get_temp_dir().'/beerjudge-branding-'.uniqid();
        File::ensureDirectoryExists($this->iconDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->iconDir);

        parent::tearDown();
    }

    public function test_manifest_without_club_name_names_no_club(): void
    {
        config(['app.club_name' => '']);

        $this->get('/manifest.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'BeerJudge')
            ->assertJsonPath('description', 'Homebrew competition judging');
    }

    public function test_manifest_uses_club_name(): void
    {
        config(['app.club_name' => 'Hopheads']);

        $this->get('/manifest.json')
            ->assertOk()
            ->assertJsonPath('name', 'Hopheads BeerJudge')
            ->assertJsonPath('description', 'Homebrew competition judging for Hopheads')
            ->assertJsonPath('icons.0.src', '/branding/icon-192.png')
            ->assertJsonPath('icons.1.src', '/branding/icon-512.png');
    }

    public function test_default_icons_are_served_without_an_override_dir(): void
    {
        config(['app.club_icon_dir' => null]);

        $this->assertServes('icon-192.png', public_path('icons/icon-192.png'));
        $this->assertServes('icon-512.png', public_path('icons/icon-512.png'));
        $this->assertServes('favicon.svg', public_path('favicon.svg'));
    }

    public function test_override_dir_icons_replace_the_defaults(): void
    {
        foreach (['icon-192.png', 'icon-512.png', 'favicon.svg'] as $file) {
            File::put($this->iconDir.'/'.$file, 'club-'.$file);
        }
        config(['app.club_icon_dir' => $this->iconDir]);

        $this->assertServes('icon-192.png', $this->iconDir.'/icon-192.png');
        $this->assertServes('icon-512.png', $this->iconDir.'/icon-512.png');
        $this->assertServes('favicon.svg', $this->iconDir.'/favicon.svg');
    }

    public function test_icon_missing_from_override_dir_falls_back_to_the_default(): void
    {
        File::put($this->iconDir.'/favicon.svg', 'club-favicon');
        config(['app.club_icon_dir' => $this->iconDir]);

        $this->assertServes('favicon.svg', $this->iconDir.'/favicon.svg');
        $this->assertServes('icon-192.png', public_path('icons/icon-192.png'));
    }

    public function test_unknown_icon_file_is_not_found(): void
    {
        $this->get('/branding/.env')->assertNotFound();
        $this->get('/branding/logo.png')->assertNotFound();
    }

    public function test_page_head_links_the_branding_routes(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<link rel="manifest" href="/manifest.json">', false)
            ->assertSee('<link rel="apple-touch-icon" href="/branding/icon-192.png">', false)
            ->assertSee('<link rel="icon" href="/branding/favicon.svg" type="image/svg+xml">', false);
    }

    private function assertServes(string $file, string $expectedPath): void
    {
        $response = $this->get('/branding/'.$file);

        $response->assertOk();
        $this->assertSame(file_get_contents($expectedPath), $response->streamedContent());
    }
}
