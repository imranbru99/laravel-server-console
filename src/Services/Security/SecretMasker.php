<?php

namespace Imran\WebShell\Services\Security;

class SecretMasker
{
    public function maskValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('•', max($length, 4));
        }

        return str_repeat('•', min(12, $length));
    }

    public function isSecretKey(string $key): bool
    {
        $configured = array_map('strtoupper', (array) config('webshell.secret_env_keys', []));

        if (in_array(strtoupper($key), $configured, true)) {
            return true;
        }

        return (bool) preg_match('/(PASSWORD|SECRET|TOKEN|KEY|PRIVATE|CREDENTIAL|AUTH)/i', $key);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function maskEnvironment(array $variables): array
    {
        $masked = [];

        foreach ($variables as $key => $value) {
            $masked[$key] = $this->isSecretKey((string) $key)
                ? $this->maskValue((string) $value)
                : $value;
        }

        return $masked;
    }
}
