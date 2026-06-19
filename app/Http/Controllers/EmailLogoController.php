<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmailLogoController extends Controller
{
    public function __invoke(string $filename): StreamedResponse
    {
        $path = 'email-logos/' . $filename;

        User::query()
            ->where('email_logo_path', $path)
            ->firstOrFail();

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $filename, [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
