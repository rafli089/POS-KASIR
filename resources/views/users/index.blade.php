@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Kelola User</h1>
        <a href="{{ route('users.create') }}" class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">+ Tambah User</a>
    </div>

    <form method="GET" class="bg-white border border-line rounded-xl p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-40">
            <label class="block text-xs text-muted mb-1">Cari nama</label>
            <input type="text" name="q" value="{{ request('q') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-muted mb-1">Peran</label>
            <select name="role" class="rounded-lg border border-line px-3 py-2 text-sm bg-white">
                <option value="">Semua</option>
                @foreach(['CASHIER', 'MANAGER', 'ADMIN'] as $r)
                    <option value="{{ $r }}" @selected(request('role') === $r)>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        <button class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Filter</button>
    </form>

    <div class="bg-white border border-line rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted text-xs border-b border-line">
                    <th class="px-5 py-2.5 font-medium">Nama</th>
                    <th class="px-5 py-2.5 font-medium">Peran</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium">Dibuat</th>
                    <th class="px-5 py-2.5 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-b border-line/50 last:border-0 hover:bg-background/50">
                        <td class="px-5 py-2.5 font-medium">{{ $user->name }}</td>
                        <td class="px-5 py-2.5">{{ $user->role }}</td>
                        <td class="px-5 py-2.5">
                            <span class="px-2 py-0.5 rounded-md text-xs font-medium {{ $user->status === 'ACTIVE' ? 'bg-success/10 text-success' : 'bg-error/10 text-error' }}">{{ $user->status }}</span>
                        </td>
                        <td class="px-5 py-2.5 text-muted">{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-5 py-2.5 text-right space-x-3">
                            <a href="{{ route('users.edit', $user) }}" class="text-primary text-xs hover:underline font-medium">Edit</a>
                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-error text-xs hover:underline font-medium">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection