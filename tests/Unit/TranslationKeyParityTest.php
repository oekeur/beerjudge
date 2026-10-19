<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TranslationKeyParityTest extends TestCase
{
    public function test_english_and_dutch_lang_files_have_identical_keys(): void
    {
        $english = $this->keys('en');
        $dutch = $this->keys('nl');

        $this->assertSame([], array_values(array_diff($english, $dutch)), 'Keys missing from lang/nl.json');
        $this->assertSame([], array_values(array_diff($dutch, $english)), 'Keys missing from lang/en.json');
    }

    /**
     * @return list<string>
     */
    private function keys(string $locale): array
    {
        $decoded = json_decode((string) file_get_contents(dirname(__DIR__, 2)."/lang/{$locale}.json"), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        $keys = array_map('strval', array_keys($decoded));
        sort($keys);

        return $keys;
    }
}
