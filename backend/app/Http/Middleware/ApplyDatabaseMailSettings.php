<?php

namespace App\Http\Middleware;

use App\Models\EmailSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class ApplyDatabaseMailSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = EmailSetting::query()->first();
        if ($settings) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.scheme' => $settings->scheme,
                'mail.mailers.smtp.host' => $settings->host,
                'mail.mailers.smtp.port' => $settings->port,
                'mail.mailers.smtp.username' => $settings->username,
                'mail.mailers.smtp.password' => $settings->password,
                'mail.from.address' => $settings->from_address,
                'mail.from.name' => $settings->from_name,
            ]);
            Mail::purge('smtp');
        }

        return $next($request);
    }
}
