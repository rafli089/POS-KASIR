@extends('layouts.app')

@section('content')
<div class="min-h-screen grid place-items-center bg-[#F5F5F3] px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl border border-[#E5E5E5] p-8 shadow-sm">
        <div class="text-center mb-6">
            <div class="w-11 h-11 mx-auto rounded-xl bg-[#2F2F2F] grid place-items-center text-white text-lg mb-3">☕</div>
            <h1 class="text-xl font-semibold tracking-tight">{{ config('app.name') }}</h1>
            <p class="text-sm text-[#6B6B6B] mt-1">Masuk dengan PIN</p>
        </div>

        <form method="POST" action="{{ route('login.attempt') }}" x-data="{ pin: '{{ old('pin', '') }}', selected: '{{ old('user_id', '') }}' }">
            @csrf
            @csrf

            <label class="block text-sm font-medium mb-1.5">Pilih kasir</label>
            <select name="user_id" x-model="selected"
                    class="w-full rounded-lg border border-[#E5E5E5] px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2F2F2F] focus:border-transparent mb-5 bg-white">
                <option value="" disabled>-- Pilih nama --</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->role }}</option>
                @endforeach
            </select>

            @error('user_id')
                <p class="text-xs text-[#A65D5D] -mt-4 mb-3">{{ $message }}</p>
            @enderror

            <div class="flex justify-center gap-2 mb-5">
                @for($i = 0; $i < 6; $i++)
                    <div class="w-3 h-3 rounded-full transition-colors"
                         :class="pin.length > {{ $i }} ? 'bg-[#2F2F2F]' : 'bg-[#E5E5E5]'"></div>
                @endfor
            </div>

            <input type="hidden" name="pin" :value="pin">

            <div class="grid grid-cols-3 gap-2 mb-4">
                @foreach([1,2,3,4,5,6,7,8,9] as $n)
                    <button type="button" @click="if(pin.length<6) pin+='{{ $n }}'"
                            class="py-3 text-lg font-medium rounded-lg hover:bg-[#F5F5F3] active:bg-[#E5E5E5] transition select-none">{{ $n }}</button>
                @endforeach
                <button type="button" @click="pin=''"
                        class="py-3 text-sm text-[#6B6B6B] rounded-lg hover:bg-[#F5F5F3] transition select-none">Hapus</button>
                <button type="button" @click="if(pin.length<6) pin+='0'"
                        class="py-3 text-lg font-medium rounded-lg hover:bg-[#F5F5F3] active:bg-[#E5E5E5] transition select-none">0</button>
                <button type="button" @click="pin=pin.slice(0,-1)"
                        class="py-3 text-sm text-[#6B6B6B] rounded-lg hover:bg-[#F5F5F3] transition select-none">⌫</button>
            </div>

            @error('pin')
                <p class="text-xs text-[#A65D5D] text-center mb-3">{{ $message }}</p>
            @enderror

            <button type="submit" :disabled="!selected || pin.length < 4"
                    class="w-full py-2.5 rounded-lg bg-[#2F2F2F] text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed hover:bg-black transition">
                Masuk
            </button>
        </form>
    </div>
</div>
@endsection