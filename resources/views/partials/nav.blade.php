<header class="bg-surface border-b border-line sticky top-0 z-40 no-print">
    <div class="container mx-auto px-4 h-14 flex items-center gap-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
            <span class="w-7 h-7 rounded-lg bg-primary text-surface grid place-items-center text-xs font-semibold">☕</span>
            <span class="font-semibold tracking-tight hidden sm:block">{{ config('app.name') }}</span>
        </a>

        @if(session('user_id'))
            <nav class="flex items-center gap-1 ml-2 text-sm">
                @php($current = request()->route()?->getName())
                @foreach ([
                    'dashboard' => 'Dashboard',
                    'pos.index' => 'POS',
                    'shift.current' => 'Shift',
                    'transactions.index' => 'Transaksi',
                ] as $route => $label)
                    <a href="{{ route($route) }}"
                       class="px-3 py-1.5 rounded-lg transition {{ ($current === $route || str_starts_with($current, explode('.', $route)[0].'.')) && $route !== 'shift.current' ? 'bg-primary text-surface' : 'text-muted hover:text-ink hover:bg-background' }}">{{ $label }}</a>
                @endforeach
                @if(in_array(session('user_role'), ['ADMIN', 'MANAGER']))
                    <a href="{{ route('products.index') }}"
                       class="px-3 py-1.5 rounded-lg transition {{ str_starts_with($current ?? '', 'products.') ? 'bg-primary text-surface' : 'text-muted hover:text-ink hover:bg-background' }}">Produk</a>
                    <a href="{{ route('modifiers.index') }}"
                       class="px-3 py-1.5 rounded-lg transition {{ str_starts_with($current ?? '', 'modifiers.') ? 'bg-primary text-surface' : 'text-muted hover:text-ink hover:bg-background' }}">Modifier</a>
                    <a href="{{ route('reports.daily') }}"
                       class="px-3 py-1.5 rounded-lg transition {{ str_starts_with($current ?? '', 'reports.') ? 'bg-primary text-surface' : 'text-muted hover:text-ink hover:bg-background' }}">Laporan</a>
                @endif
                @if(session('user_role') === 'ADMIN')
                    <a href="{{ route('users.index') }}"
                       class="px-3 py-1.5 rounded-lg transition {{ str_starts_with($current ?? '', 'users.') ? 'bg-primary text-surface' : 'text-muted hover:text-ink hover:bg-background' }}">Pengguna</a>
                    <a href="{{ route('settings.index') }}"
                       class="px-3 py-1.5 rounded-lg transition {{ str_starts_with($current ?? '', 'settings.') ? 'bg-primary text-surface' : 'text-muted hover:text-ink hover:bg-background' }}">Pengaturan</a>
                @endif
            </nav>

            <div class="ml-auto flex items-center gap-3 text-sm">
                @if(session('shift_id'))
                    <a href="{{ route('shift.current') }}" class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-success/10 text-success text-xs font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                        Shift aktif
                    </a>
                @else
                    <a href="{{ route('shift.open') }}" class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-warning/15 text-warning text-xs font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                        Buka shift
                    </a>
                @endif

                <span class="text-muted hidden md:block" x-data x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'}), 1000)">
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="px-3 py-1.5 rounded-lg border border-line text-muted hover:text-ink hover:border-ink transition text-sm">Keluar</button>
                </form>
            </div>
        @endif
    </div>
</header>