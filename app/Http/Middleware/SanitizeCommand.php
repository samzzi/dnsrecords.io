<?php

namespace App\Http\Middleware;

use App\Http\Controllers\HomeController;
use App\Services\Dns\DnsResolver;
use Closure;
use Illuminate\Http\Request;

class SanitizeCommand
{
    public function handle(Request $request, Closure $next)
    {
        $command = $request->command ?? $request->route('command');

        $sanitizedCommand = $this->sanitizeCommand($command);

        $sanitizedCommand = str_replace('...', '', $sanitizedCommand);

        if ($command !== $sanitizedCommand) {
            return redirect()->action([HomeController::class, 'submit'], ['command' => $sanitizedCommand]);
        }

        return $next($request);
    }

    protected function sanitizeCommand(?string $command = ''): ?string
    {
        $cleanCommand = strip_tags($command);

        if (!$cleanCommand) {
            return null;
        }

        return (new DnsResolver())->getDomain(strip_tags($cleanCommand));
    }
}
