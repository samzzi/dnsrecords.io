<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    public function hosts(): array
    {
        $hosts = [];

        $trustedHosts = env('TRUSTED_HOSTS');
        if ($trustedHosts) {
            foreach (explode(',', $trustedHosts) as $host) {
                $host = trim($host);
                if ($host !== '') {
                    $hosts[] = $host;
                }
            }
        }

        $hosts[] = $this->allSubdomainsOfApplicationUrl();
        $hosts[] = '^localhost$';
        $hosts[] = '^127\\.0\\.0\\.1$';

        return array_filter($hosts);
    }
}
