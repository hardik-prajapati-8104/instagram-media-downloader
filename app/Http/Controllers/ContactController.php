<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $to = config('media.contact_email');
        abort_unless($to, 404);

        if ($request->filled('website')) { // honeypot
            return back()->with('status', 'Thanks! Your message has been sent.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        try {
            Mail::raw("From: {$data['name']} <{$data['email']}>\n\n{$data['message']}", function ($m) use ($to, $data) {
                $m->to($to)->replyTo($data['email'], $data['name'])->subject('Contact form message');
            });
        } catch (Throwable $e) {
            Log::error('Contact mail failed: ' . $e->getMessage());

            return back()->withInput()->withErrors(['message' => 'Your message could not be sent right now. Please try again later.']);
        }

        return back()->with('status', 'Thanks! Your message has been sent.');
    }
}
