@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-2xl p-8">
        <h1 class="text-2xl font-bold">Status Pengajuan Aset</h1>

        <dl class="mt-6 space-y-2 text-sm">
            <div><dt class="font-semibold inline">Nomor Referensi: </dt><dd class="inline">{{ $assetRequest->reference_number ?? '-' }}</dd></div>
            <div><dt class="font-semibold inline">Tipe: </dt><dd class="inline">{{ $assetRequest->type ?? '-' }}</dd></div>
            <div><dt class="font-semibold inline">Status: </dt><dd class="inline">{{ $assetRequest->status ?? '-' }}</dd></div>
        </dl>
    </div>
@endsection