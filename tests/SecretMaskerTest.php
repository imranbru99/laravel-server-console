<?php

namespace Imran\WebShell\Tests;

use Imran\WebShell\Services\Security\SecretMasker;

class SecretMaskerTest extends TestCase
{
    public function test_it_masks_secret_keys_and_leaves_safe_keys(): void
    {
        $masker = new SecretMasker;

        $masked = $masker->maskEnvironment([
            'APP_ENV' => 'production',
            'DB_PASSWORD' => 'super-secret',
            'OPENAI_API_KEY' => 'sk-test',
        ]);

        $this->assertSame('production', $masked['APP_ENV']);
        $this->assertSame($masker->maskValue('super-secret'), $masked['DB_PASSWORD']);
        $this->assertDoesNotMatchRegularExpression('/super-secret|sk-test/', implode(' ', $masked));
    }
}
