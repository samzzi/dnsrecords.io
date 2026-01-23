<?php

namespace App\Services\Dns;

class DnsResolver
{
    public function getDomain(?string $input): ?string
    {
        if (!$input) {
            return null;
        }

        $input = trim($input);

        if ($input === '') {
            return null;
        }

        if ($input === '.') {
            return '.';
        }

        $input = preg_replace('/\s+/', '', $input);
        $url = str_contains($input, '://') ? $input : 'http://' . $input;

        $host = parse_url($url, PHP_URL_HOST) ?: parse_url($url, PHP_URL_PATH);

        if (!$host) {
            return null;
        }

        $host = trim($host, '.');

        if ($host === '' && $input === '.') {
            return '.';
        }

        if ($host === '' || !preg_match('/^[a-z0-9.-]+$/i', $host)) {
            return null;
        }

        return strtolower($host);
    }

    public function getRecords(string $domain): string
    {
        $records = dns_get_record($domain, DNS_ANY);

        if (!$records) {
            return '';
        }

        return json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
