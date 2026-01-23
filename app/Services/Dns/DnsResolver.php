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

        return $this->formatRecords($records);
    }

    private function formatRecords(array $records): string
    {
        $lines = [];

        foreach ($records as $record) {
            $type = $record['type'] ?? null;
            $host = $this->withTrailingDot($record['host'] ?? '');
            $ttl = $record['ttl'] ?? 0;
            $class = $record['class'] ?? 'IN';

            switch ($type) {
                case 'A':
                    $lines[] = sprintf('%s		%d %s A %s', $host, $ttl, $class, $record['ip'] ?? '');
                    break;
                case 'AAAA':
                    $lines[] = sprintf('%s		%d %s AAAA %s', $host, $ttl, $class, $record['ipv6'] ?? '');
                    break;
                case 'CNAME':
                    $lines[] = sprintf('%s		%d %s CNAME %s', $host, $ttl, $class, $this->withTrailingDot($record['target'] ?? ''));
                    break;
                case 'NS':
                    $lines[] = sprintf('%s		%d %s NS %s', $host, $ttl, $class, $this->withTrailingDot($record['target'] ?? ''));
                    break;
                case 'MX':
                    $lines[] = sprintf('%s		%d %s MX %d %s', $host, $ttl, $class, $record['pri'] ?? 0, $this->withTrailingDot($record['target'] ?? ''));
                    break;
                case 'TXT':
                    $text = $record['txt'] ?? '';
                    $lines[] = sprintf('%s		%d %s TXT "%s"', $host, $ttl, $class, $text);
                    break;
                case 'SOA':
                    $lines[] = sprintf(
                        '%s		%d %s SOA %s %s (',
                        $host,
                        $ttl,
                        $class,
                        $this->withTrailingDot($record['mname'] ?? ''),
                        $this->withTrailingDot($record['rname'] ?? '')
                    );
                    $lines[] = sprintf('				%d ; serial', $record['serial'] ?? 0);
                    $lines[] = $this->formatSoaInterval($record['refresh'] ?? 0, 'refresh');
                    $lines[] = $this->formatSoaInterval($record['retry'] ?? 0, 'retry');
                    $lines[] = $this->formatSoaInterval($record['expire'] ?? 0, 'expire');
                    $lines[] = $this->formatSoaInterval($record['minimum-ttl'] ?? 0, 'minimum');
                    $lines[] = '				)';
                    break;
                default:
                    // Skip unknown record types to avoid noisy output.
                    break;
            }
        }

        return implode("\n", $lines);
    }

    private function withTrailingDot(string $value): string
    {
        if ($value === '' || $value === '.') {
            return $value === '' ? '.' : $value;
        }

        return str_ends_with($value, '.') ? $value : $value . '.';
    }

    private function formatSoaInterval(int $seconds, string $label): string
    {
        $human = $this->formatDuration($seconds);

        if ($human !== null) {
            return sprintf('				%d ; %s (%s)', $seconds, $label, $human);
        }

        return sprintf('				%d ; %s', $seconds, $label);
    }

    private function formatDuration(int $seconds): ?string
    {
        if ($seconds <= 0) {
            return null;
        }

        $units = [
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
        ];

        foreach ($units as $label => $unitSeconds) {
            if ($seconds % $unitSeconds === 0) {
                $value = (int) ($seconds / $unitSeconds);
                return $value === 1 ? "1 {$label}" : "{$value} {$label}s";
            }
        }

        return "{$seconds} seconds";
    }
}
