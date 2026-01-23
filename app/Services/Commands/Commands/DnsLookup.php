<?php

namespace App\Services\Commands\Commands;

use App\Services\Commands\Command;
use App\Services\Dns\DnsResolver;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class DnsLookup implements Command
{
    public function canPerform(string $command): bool
    {
        return true;
    }

    public function perform(string $command): Response
    {
        $dns = new DnsResolver();

        try {
            $domain = $dns->getDomain($command);
            $dnsRecords = $domain ? $dns->getRecords($domain) : '';
        } catch (Exception $e) {
            $dnsRecords = '';
        }

        if ($dnsRecords === '') {
            $errorText = __('errors.noDnsRecordsFound', ['domain' => $domain ?? null]);

            flash()->error($errorText);

            return redirect('/');
        }

        return response()->view('home.index', ['output' => $dnsRecords, 'domain' => $domain ]);
    }
}
