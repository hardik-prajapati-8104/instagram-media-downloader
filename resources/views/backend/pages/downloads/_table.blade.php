<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead class="table-light"><tr><th>UUID</th><th>Media Type</th><th>Status</th><th>Provider</th><th>Requested At</th><th>Expires At</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($downloads as $d)
            <tr>
                <td><code>{{ \Illuminate\Support\Str::limit($d->uuid, 13, '…') }}</code></td>
                <td class="text-capitalize">{{ $d->media_type ?? $d->requested_mode }}</td>
                <td>@php $c = ['completed'=>'success','failed'=>'danger','processing'=>'warning','pending'=>'secondary','expired'=>'dark'][$d->status] ?? 'secondary'; @endphp<span class="badge text-bg-{{ $c }} badge-status">{{ $d->status }}</span></td>
                <td>{{ $d->provider ?? '—' }}</td>
                <td>{{ $d->requested_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $d->expires_at?->format('Y-m-d H:i') ?? '—' }}</td>
                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.downloads.show', $d->uuid) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No requests yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $downloads->links('pagination::bootstrap-5') }}
