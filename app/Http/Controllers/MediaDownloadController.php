<?php

namespace App\Http\Controllers;

use App\Models\DownloadedMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaDownloadController extends Controller
{
    public function download(int $id): Response
    {
        return $this->serve($id, false);
    }

    public function preview(int $id): Response
    {
        return $this->serve($id, true);
    }

    private function serve(int $id, bool $inline): Response
    {
        // The route is protected by the "signed" middleware (tamper-proof + time-limited). Everything below is defence in depth.
        $media = DownloadedMedia::with('downloadRequest')->findOrFail($id);
        $req = $media->downloadRequest;

        abort_unless($req && $req->isCompleted(), 404);
        abort_if($req->isExpired(), 410, 'This download has expired. Please submit the link again.');

        $prefix = trim((string) config('media.directory'), '/') . '/' . $req->uuid . '/';
        $path = (string) $media->storage_path;
        abort_unless(
            str_starts_with($path, $prefix) && ! str_contains($path, '..') && ! str_contains(substr($path, strlen($prefix)), '/'),
            404
        );
        abort_unless(array_key_exists($media->mime_type, config('media.allowed_mimes')), 404);

        $diskName = config('media.disk');
        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($path), 410, 'The file is no longer available.');

        $headers = [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, no-store',
        ];
        $disposition = $inline ? 'inline' : 'attachment';

        if (config("filesystems.disks.{$diskName}.driver") === 'local') {
            // BinaryFileResponse = Range support (video seeking).
            return response()->file($disk->path($path), $headers)
                ->setContentDisposition($disposition, $media->file_name);
        }

        return $disk->response($path, $media->file_name, $headers, $disposition);
    }
}
