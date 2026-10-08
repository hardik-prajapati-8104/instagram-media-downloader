<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\MediaException;
use App\Http\Controllers\Controller;
use App\Services\Instagram\SettingsService;
use App\Services\Instagram\SsrfGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(SettingsService $s)
    {
        return view('backend.pages.settings.index', [
            's' => $s,
            'providers' => array_keys(config('instagram.providers')),
            'queueConnection' => config('queue.default'),
        ]);
    }

    public function update(Request $request, SettingsService $s, SsrfGuard $guard)
    {
        $d = $request->validate([
            'provider' => ['nullable', Rule::in(array_keys(config('instagram.providers')))],
            'provider_url' => ['nullable', 'url:https', 'max:500'],
            'provider_api_key' => ['nullable', 'string', 'max:500'],
            'clear_api_key' => ['nullable', 'boolean'],
            'provider_timeout' => ['required', 'integer', 'between:5,120'],
            'rate_limit' => ['required', 'integer', 'between:1,1000'],
            'rate_window' => ['required', 'integer', 'between:1,60'],
            'retention_minutes' => ['required', 'integer', 'between:5,10080'],
            'max_file_size_mb' => ['required', 'integer', 'between:1,1024'],
            'queue_name' => ['required', 'regex:/^[A-Za-z0-9_.-]{1,40}$/'],
            'ffmpeg_binary' => ['required', 'regex:#^[A-Za-z0-9_./-]{1,200}$#'],
            'ffprobe_binary' => ['required', 'regex:#^[A-Za-z0-9_./-]{1,200}$#'],
        ]);

        if (! empty($d['provider_url'])) {
            try {
                $guard->assertProviderUrl($d['provider_url']);
            } catch (MediaException $e) {
                return back()->withInput($request->except('provider_api_key'))
                    ->withErrors(['provider_url' => 'Provider URL rejected: it must be a public https host (no IPs/localhost/private networks).']);
            }
        }

        $s->set('provider', $d['provider'] ?? null);
        $s->set('provider_url', $d['provider_url'] ?? null);
        if ($request->boolean('clear_api_key')) {
            $s->set('provider_api_key', null, true);
        } elseif (! empty($d['provider_api_key'])) {
            $s->set('provider_api_key', $d['provider_api_key'], true);
        }
        $s->set('provider_timeout', (string) $d['provider_timeout']);
        $s->set('rate_limit', (string) $d['rate_limit']);
        $s->set('rate_window', (string) $d['rate_window']);
        $s->set('retention_minutes', (string) $d['retention_minutes']);
        $s->set('max_file_size', (string) ($d['max_file_size_mb'] * 1048576));
        $s->set('queue_name', $d['queue_name']);
        $s->set('ffmpeg_binary', $d['ffmpeg_binary']);
        $s->set('ffprobe_binary', $d['ffprobe_binary']);

        return redirect()->route('admin.settings')->with('success', 'Settings saved.');
    }
}
