<?php

namespace App\Logging;

/**
 * Processor para Monolog 3.x
 * Sanitiza campos sensíveis em context/extra dos logs.
 */
class SanitizeSensitiveData
{
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        $needles = [
            'password', 'password_confirmation',
            'token', 'access_token', 'refresh_token', 'authorization',
            'cookie', 'set-cookie', 'secret', 'client_secret',
            'apikey', 'api_key', 'key', 'credentials',
        ];

        foreach (['context', 'extra'] as $bag) {
            if (!isset($record[$bag]) || !is_array($record[$bag])) {
                continue;
            }
            $record[$bag] = $this->redactArray($record[$bag], $needles);
        }

        // Sanitiza mensagem formatada se usar placeholders {token}, etc.
        if (isset($record['message']) && is_string($record['message'])) {
            $lower = strtolower($record['message']);
            foreach ($needles as $needle) {
                if (str_contains($lower, $needle)) {
                    $record['message'] = preg_replace(
                        '/(' . preg_quote($needle, '/') . '\s*[:=]\s*)([^ \t\r\n]+)/i',
                        '$1[REDACTED]',
                        $record['message']
                    );
                }
            }
        }

        return $record;
    }

    private function redactArray(array $data, array $needles): array
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = $this->redactArray($v, $needles);
                continue;
            }
            if (!is_string($k)) {
                continue;
            }
            $lk = strtolower($k);
            foreach ($needles as $needle) {
                if (str_contains($lk, $needle)) {
                    $data[$k] = '[REDACTED]';
                    break;
                }
            }
        }
        return $data;
    }
}
