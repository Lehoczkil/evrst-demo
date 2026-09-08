<?php

namespace Tests\Unit;

use App\Support\OrgEmail;
use Tests\TestCase;

class OrgEmailTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function nameProvider(): array
    {
        return [
            'hungarian order is swapped' => ['Lehoczki László', 'laszlo.lehoczki@evrst.hu'],
            'accents are transliterated' => ['Bába Kíra', 'kira.baba@evrst.hu'],
            'middle given names dropped' => ['Horváth Márton Antal', 'marton.horvath@evrst.hu'],
            'hyphens collapse' => ['Tello-Pálfy Sebastián', 'sebastian.tellopalfy@evrst.hu'],
            'extra whitespace ignored' => ['  Kerek   Gábor ', 'gabor.kerek@evrst.hu'],
            'single token stands alone' => ['Somnenie', 'somnenie@evrst.hu'],
            'unusable name yields null' => ['   ', null],
            'punctuation-only yields null' => ['!!! ???', null],
        ];
    }

    /** @dataProvider nameProvider */
    public function test_it_builds_the_org_address(string $name, ?string $expected): void
    {
        $this->assertSame($expected, OrgEmail::forName($name));
    }

    public function test_domain_comes_from_config(): void
    {
        config(['mail.org_domain' => 'example.test']);

        $this->assertSame('example.test', OrgEmail::domain());
        $this->assertSame('laszlo.lehoczki@example.test', OrgEmail::forName('Lehoczki László'));
    }

    public function test_is_org_address_is_case_insensitive(): void
    {
        $this->assertTrue(OrgEmail::isOrgAddress('Laszlo.Lehoczki@EVRST.hu'));
        $this->assertFalse(OrgEmail::isOrgAddress('laszlo@gmail.com'));
        $this->assertFalse(OrgEmail::isOrgAddress(null));
    }
}
