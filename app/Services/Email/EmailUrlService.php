<?php

namespace App\Services\Email;

class EmailUrlService
{
    public function to(string $path): string
    {
        $baseUrl = rtrim((string) config('email_management.public_url'), '/');

        return $baseUrl.'/'.ltrim($path, '/');
    }
}
