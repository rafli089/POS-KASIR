@extends('layouts.app')

@section('content')
<div x-data="pos()" x-init="init()">
    @if(!$shift)
        <div class="max-w-md mx-auto bg-white rounded-2xl border border-line p-8 text-center shadow-sm">
            <div class="w-12 h-12 mx-auto rounded-xl bg-warning/15 grid place-items-center text-warning text-xl mb-4">🗓</div>
            <h2 class="text-lg font-semibold">Shift belum dibuka</h2>
            <p class="text-sm text-muted mt-1.5 mb-6">Buka shift terlebih dahulu untuk mulai melayani transaksi.</p>
            <a href="{{ route('shift.open') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">
                Buka Shift
            </a>
        </div>
    @else
        {{-- Product area --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-8 space-y-3">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input type="text" x-model="searchQuery" placeholder="Cari produk...  /" x-ref="searchInput"
                               class="w-full rounded-lg border border-line bg-white px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-8"
                               @keydown.enter="searchQuery && window.location='?q=' + searchQuery">
                        <span x-show="searchQuery" @click="searchQuery=''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted hover:text-ink cursor-pointer text-xs">✕</span>
                    </div>
                    <div class="flex items-center gap-1 bg-white border border-line rounded-xl p-1.5 overflow-x-auto text-sm">
                        <a href="{{ route('pos.index') }}"
                           class="shrink-0 px-3.5 py-1.5 rounded-lg transition {{ !$categoryId ? 'bg-primary text-surface' : 'text-muted hover:text-ink' }}">Semua</a>
                        @foreach($categories as $category)
                            <a href="{{ route('pos.index', ['category' => $category->id]) }}"
                               class="shrink-0 px-3.5 py-1.5 rounded-lg transition {{ $categoryId == $category->id ? 'bg-primary text-surface' : 'text-muted hover:text-ink' }}">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>

                @if($products->isEmpty())
                    <div class="bg-white border border-line rounded-xl py-16 text-center text-sm text-muted">Tidak ada produk pada kategori ini.</div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                        <template x-for="p in filteredProducts" :key="p.id">
                            <button @click="add(p.id)"
                                    :class="p.stock === 0 ? 'opacity-45 cursor-not-allowed' : 'hover:border-primary hover:shadow-sm active:scale-[.98]'"
                                    class="text-left bg-white border border-line rounded-xl p-4 transition">
                                <div class="w-full aspect-square rounded-lg bg-background grid place-items-center mb-3 text-2xl">☕</div>
                                <div class="text-sm font-medium leading-snug" x-text="p.name"></div>
                                <div class="text-sm font-semibold mt-1" x-text="fmt(p.price)"></div>
                                <div class="text-xs mt-0.5" :class="p.stock === 0 ? 'text-error' : 'text-muted'"
                                     x-text="p.stock === null ? '' : (p.stock === 0 ? 'Stok habis' : 'Stok: ' + fmt(p.stock))"></div>
                            </button>
                        </template>
                        <div x-show="!filteredProducts.length && searchQuery" class="col-span-full text-center py-10 text-sm text-muted">
                            Produk "<span x-text="searchQuery"></span>" tidak ditemukan.
                        </div>
                    </div>
                @endif
            </div>

            {{-- Cart area --}}
            <div class="lg:col-span-4">
                <div class="bg-white border border-line rounded-xl sticky top-20 flex flex-col max-h-[calc(100vh-7rem)]">
                    <div class="px-4 py-3 border-b border-line flex items-center justify-between">
                        <h3 class="font-semibold">Pesanan</h3>
                        <button @click="cart=[]" x-show="cart.length"
                                class="text-xs text-muted hover:text-error transition">Kosongkan</button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-2 py-1" style="max-height: 40vh">
                        <template x-for="(item, idx) in cart" :key="item.key">
                            <div class="flex items-start gap-2 px-2 py-2.5 border-b border-line/60">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium truncate" x-text="item.name"></div>
                                    <div class="text-xs text-muted" x-text="fmt(item.unit) + ' × ' + item.qty + ' = ' + fmt(item.unit * item.qty)"></div>
                                    <div class="text-[11px] text-muted truncate" x-text="item.modifiers.map(m => m.name).join(', ')"></div>
                                    <input type="text" x-model="item.notes" placeholder="Catatan..." maxlength="255"
                                           class="text-xs text-muted bg-background rounded px-1.5 py-0.5 mt-1 w-full focus:outline-none focus:ring-1 focus:ring-primary">
                                </div>
                                <div class="flex items-center gap-1 mt-5">
                                    <button @click="changeQty(idx, -1)" class="w-6 h-6 rounded-md bg-background hover:bg-line text-sm leading-none">−</button>
                                    <span class="w-6 text-center text-sm" x-text="item.qty"></span>
                                    <button @click="changeQty(idx, 1)" class="w-6 h-6 rounded-md bg-background hover:bg-line text-sm leading-none">+</button>
                                </div>
                                <button @click="cart.splice(idx,1)" class="text-xs text-muted hover:text-error px-1 mt-5">✕</button>
                            </div>
                        </template>
                        <div x-show="!cart.length" class="text-center text-sm text-muted py-10">
                            Kosong.<br>Pilih produk untuk memulai.
                        </div>
                    </div>

                    <div class="p-4 border-t border-line space-y-1.5">
                        <div class="flex justify-between text-sm text-muted"><span>Subtotal</span><span x-text="fmt(subtotal)"></span></div>
                        @if($serviceChargePct > 0)
                            <div class="flex justify-between text-sm text-muted"><span>Layanan ({{ $serviceChargePct }}%)</span><span x-text="fmt(serviceCharge)"></span></div>
                        @endif
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="text-muted">Diskon</span>
                            <input type="number" min="0" x-model.number="discount" placeholder="0"
                                   class="w-24 text-right rounded-md border border-line px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                        </div>
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="text-muted">Pajak</span>
                            <input type="number" min="0" x-model.number="tax" placeholder="0"
                                   class="w-24 text-right rounded-md border border-line px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                        </div>
                        <div class="flex justify-between text-base font-semibold pt-1 border-t border-line/60 mt-1">
                            <span>Total</span><span x-text="fmt(total)"></span>
                        </div>
                        <button @click="openPayment()" :disabled="!cart.length"
                                class="w-full mt-3 py-3 rounded-lg bg-primary text-surface text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed hover:bg-black transition">
                            Bayar (F2) · <span x-text="fmt(total)"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Payment modal --}}
    <div x-show="showPayment" x-cloak
         class="fixed inset-0 z-50 bg-black/40 grid place-items-center p-4"
         @keydown.escape.window="showPayment=false">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl" @click.outside="showPayment=false">
            <h3 class="font-semibold mb-4">Pembayaran</h3>

            <div class="flex justify-between items-baseline py-2 border-b border-line mb-4">
                <span class="text-sm text-muted">Total</span>
                <span class="text-2xl font-bold" x-text="fmt(total)"></span>
            </div>

            <div class="grid grid-cols-2 gap-2 mb-4">
                @foreach($paymentMethods ?? [] as $method)
                    <button @click="method={{ $method->id }}; if(method=={{ $cashId ?? 0 }}){ cash=total }"
                            class="py-2.5 rounded-lg border text-sm transition"
                            :class="method==={{ $method->id }} ? 'border-primary bg-primary text-surface' : 'border-line hover:border-primary'">
                        {{ $method->name }}
                    </button>
                @endforeach
            </div>

            <div x-show="method == {{ $cashId ?? 0 }}" class="mb-4">
                <label class="block text-xs text-muted mb-1">Uang diterima</label>
                <input type="number" min="0" x-model.number="cash" x-ref="cashInput"
                       class="w-full rounded-lg border border-line px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <div class="flex gap-1.5 mt-2">
                    <button @click="cash=total" class="flex-1 px-2 py-1.5 rounded-md bg-success/10 text-success text-xs font-medium hover:bg-success/20 transition">Pas</button>
                    <button @click="quickCash(Math.ceil(total/50000)*50000)" class="flex-1 px-2 py-1.5 rounded-md bg-background text-xs text-muted hover:bg-line transition" x-text="fmt(Math.ceil(total/50000)*50000)"></button>
                    <button @click="quickCash(Math.ceil(total/100000)*100000)" class="flex-1 px-2 py-1.5 rounded-md bg-background text-xs text-muted hover:bg-line transition" x-text="fmt(Math.ceil(total/100000)*100000)"></button>
                </div>
                <p class="text-xs mt-2">Kembalian: <span class="font-semibold" x-text="fmt(Math.max(cash-total,0))"></span></p>
            </div>

            <div x-show="method != {{ $cashId ?? 0 }}" class="mb-4 text-sm text-muted bg-background rounded-lg px-3 py-2.5">
                Bayar melalui metode terpilih.
            </div>

            <div class="flex gap-2">
                <button @click="showPayment=false" class="flex-1 py-2.5 rounded-lg border border-line text-sm">Batal</button>
                <button @click="pay()" :disabled="method=={{ $cashId ?? 0 }} && cash < total"
                        class="flex-1 py-2.5 rounded-lg bg-primary text-surface text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed hover:bg-black transition">
                    Konfirmasi (Enter)
                </button>
            </div>
        </div>
    </div>

    {{-- Modifier modal --}}
    <div x-show="showModifier" x-cloak
         class="fixed inset-0 z-50 bg-black/40 grid place-items-center p-4"
         @keydown.escape.window="showModifier=false">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl" @click.outside="showModifier=false">
            <h3 class="font-semibold" x-text="modProduct?.name"></h3>
            <p class="text-xs text-muted mb-4">Pilih pengaturan tambahan untuk produk ini.</p>

            <template x-for="g in (modProduct?.modifierGroups || [])" :key="g.id">
                <div class="mb-4">
                    <div class="text-sm font-medium mb-2" x-text="g.name + (g.required ? ' *' : '')"></div>
                    <div class="space-y-1.5">
                        <template x-for="m in g.modifiers" :key="m.id">
                            <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer text-sm transition"
                                   :class="modSelected[g.id] === m.id ? 'border-primary bg-primary/5' : 'border-line hover:border-primary'">
                                <input type="radio" :name="'mod-'+g.id" :value="m.id" x-model="modSelected[g.id]" class="accent-primary">
                                <span class="flex-1" x-text="m.name"></span>
                                <span class="text-muted" x-text="m.price_modifier ? '+' + fmt(m.price_modifier) : ''"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </template>

            <div class="flex gap-2">
                <button @click="showModifier=false" class="flex-1 py-2.5 rounded-lg border border-line text-sm">Batal</button>
                <button @click="confirmModifier()" class="flex-1 py-2.5 rounded-lg bg-primary text-surface text-sm font-medium">Pilih</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function pos() {
    return {
        products: @json($products),
        searchQuery: new URLSearchParams(window.location.search).get('q') || '',
        cart: [],
        showPayment: false,
        method: {{ $cashId ?? 0 }},
        cash: 0,
        paying: false,
        discount: 0,
        tax: 0,
        showModifier: false,
        modProduct: null,
        modSelected: {},

        init() {
            window.addEventListener('keydown', (e) => {
                if (this.showPayment) {
                    if (e.key === 'Escape') { this.showPayment = false; return; }
                    if (e.key === 'Enter' && !e.target.closest('input')) {
                        e.preventDefault(); this.pay(); return;
                    }
                    return;
                }
                if (e.key === 'F2') { e.preventDefault(); if (this.cart.length) this.openPayment(); return; }
                if (e.key === '/' && !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) {
                    e.preventDefault(); this.$refs.searchInput?.focus();
                }
            });
        },

        get filteredProducts() {
            const q = this.searchQuery.toLowerCase().trim();
            if (!q) return this.products;
            return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.sku||'').toLowerCase().includes(q));
        },

        quickCash(n) { this.cash = Math.max(this.cash, n); },

        add(id) {
            const p = this.products.find(x => x.id === id);
            if (!p) return;
            if (p.stock === 0) return;
            const groups = p.modifierGroups || [];
            if (groups.length) {
                this.modProduct = p;
                this.modSelected = {};
                groups.forEach(g => { this.modSelected[g.id] = g.modifiers?.[0]?.id ?? null; });
                this.showModifier = true;
                return;
            }
            this.pushItem(p, []);
        },

        pushItem(p, modifiers) {
            const key = p.id + '|' + modifiers.map(m => m.id).join(',');
            const idx = this.cart.findIndex(x => x.key === key);
            if (idx >= 0) { this.cart[idx].qty++; return; }
            this.cart.push({ key, id: p.id, name: p.name, price: p.price, unit: p.price, qty: 1, notes: '', modifiers });
        },

        confirmModifier() {
            const p = this.modProduct;
            if (!p) return;
            const modifiers = [];
            let extra = 0;
            (p.modifierGroups || []).forEach(g => {
                const m = (g.modifiers || []).find(x => x.id === this.modSelected[g.id]);
                if (m) { modifiers.push({ id: m.id, group: g.name, name: m.name, price_modifier: m.price_modifier }); extra += m.price_modifier; }
            });
            const key = p.id + '|' + modifiers.map(m => m.id).join(',');
            const idx = this.cart.findIndex(x => x.key === key);
            if (idx >= 0) { this.cart[idx].qty++; } else {
                this.cart.push({ id: p.id, name: p.name, price: p.price, unit: p.price + extra, qty: 1, notes: '', modifiers });
            }
            this.showModifier = false;
        },

        changeQty(idx, d) {
            this.cart[idx].qty += d;
            if (this.cart[idx].qty <= 0) this.cart.splice(idx, 1);
        },

        openPayment() {
            this.showPayment = true;
            this.cash = this.total;
            this.$nextTick(() => { if (this.method === {{ $cashId ?? 0 }}) this.$refs.cashInput?.focus(); });
        },

        fmt(n) { return new Intl.NumberFormat('id-ID').format(n); },

        get subtotal() {
            return this.cart.reduce((s, i) => s + i.unit * i.qty, 0);
        },

        get serviceCharge() {
            return Math.round(this.subtotal * {{ (int) $serviceChargePct }} / 100);
        },

        get total() { return Math.max(0, this.subtotal - this.discount + this.tax + this.serviceCharge); },

        async pay() {
            if (this.paying) return;
            this.paying = true;
            try {
                const res = await axios.post('/transactions', {
                    items: this.cart.map(i => ({ product_id: i.id, quantity: i.qty, notes: i.notes || null, modifiers: i.modifiers.length ? i.modifiers : null })),
                    payment_method_id: this.method,
                    payment_amount: this.method === {{ $cashId ?? 0 }} ? this.cash : this.total,
                    discount: this.discount,
                    tax: this.tax,
                }, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                window.location.href = '/transactions/' + res.data.transaction.id + '?print=1';
            } catch (e) {
                alert(e.response?.data?.message || 'Pembayaran gagal. Coba lagi.');
                this.paying = false;
            }
        },
    };
}
</script>
@endsection