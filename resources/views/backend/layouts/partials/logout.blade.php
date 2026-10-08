<form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</button>
</form>
