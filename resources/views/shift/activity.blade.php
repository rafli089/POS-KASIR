@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <h1 class="text-xl font-semibold">Aktivitas Shift</h1>

    <div class="bg-white border border-line rounded-xl p-4 flex items-center gap-4 text-sm">
        <span class="font-medium">{{ $shift->shift_number }}</span>
        <span class="text-muted">{{ $shift->opened_at->format('d M Y · H:i') }}</span>
        <span class="ml-auto text-muted">{{ $shift->user->name }}</span>
    </div>

    @if($activities->isEmpty())
        <div class="bg-white border border-line rounded-xl px-5 py-12 text-center text-sm text-muted">
            Belum ada aktivitas pada shift ini.
        </div>
    @else
        <div class="bg-white border border-line rounded-xl p-5">
            <div class="relative pl-6 space-y-5 before:absolute before:left-[5px] before:top-2 before:bottom-2 before:w-px before:bg-line">
                @foreach($activities as $activity)
                    <div class="relative">
                        <span class="absolute -left-6 top-1.5 w-[11px] h-[11px] rounded-full border-2 border-white shadow ring-1 ring-line bg-[#2F2F2F]" style="background: {{ in_array($activity->activity_type, ['TRANSACTION_CREATED','PAYMENT_COMPLETED']) ? '#5F7D68' : '#2F2F2F' }}"></span>
                        <div class="text-xs text-muted mb-0.5">{{ $activity->created_at->format('H:i:s') }} · {{ $activity->user->name }}</div>
                        <div class="text-sm">{{ $activity->activity_type }}</div>
                        @if($activity->description)
                            <div class="text-sm text-muted">{{ $activity->description }}</div>
                        @endif
                        @if($activity->reference_type === 'transaction' && $activity->reference_id)
                            <a href="{{ route('transactions.show', $activity->reference_id) }}" class="text-xs text-primary hover:underline">Lihat transaksi</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection