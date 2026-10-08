<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function show(Request $request, string $page, string $view)
    {
        $meta = config("seo.pages.{$page}");
        abort_unless($meta, 404);

        return view($view, ['meta' => $meta, 'mode' => $meta['mode'] ?? 'home']);
    }

    public function sitemap(): Response
    {
        $urls = collect(config('seo.pages'))->map(fn ($p) => route($p['route']))->values();

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $txt = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api\n\nSitemap: " . url('/sitemap.xml') . "\n";

        return response($txt)->header('Content-Type', 'text/plain');
    }
}
