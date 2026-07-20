@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-2xl p-8">
        <h1 class="text-2xl font-bold">Persetujuan Pengajuan Aset</h1>

        @if (session('status'))
            <p class="mt-4 rounded bg-green-100 p-3 text-green-800">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="mt-4 rounded bg-red-100 p-3 text-red-800">{{ session('error') }}</p>
        @endif

        <p class="mt-4 text-sm text-gray-600">Pengajuan: {{ $assetRequest->reference_number ?? '-' }}</p>

        <div class="mt-6 flex gap-3">
            <form method="POST" action="{{ route('public.asset-requests.approval.approve', $token) }}">
                @csrf
                <textarea name="notes" rows="3" class="block w-full rounded border p-2" placeholder="Catatan (opsional)"></textarea>
                <button type="submit" class="mt-2 rounded bg-green-600 px-4 py-2 text-white">Setujui</button>
            </form>
            <form method="POST" action="{{ route('public.asset-requests.approval.reject', $token) }}">
                @csrf
                <textarea name="notes" rows="3" class="block w-full rounded border p-2" placeholder="Alasan penolakan" required></textarea>
                <button type="submit" class="mt-2 rounded bg-red-600 px-4 py-2 text-white">Tolak</button>
            </form>
        </div>
    </div>
@endsection