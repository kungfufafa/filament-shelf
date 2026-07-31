@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-2xl p-8">
        <h1 class="text-2xl font-bold">Pengajuan Aset</h1>
        <p class="mt-2 text-gray-600">Form pengajuan publik sedang dipersiapkan.</p>
        @if (session('status'))
            <p class="mt-4 rounded bg-green-100 p-3 text-green-800">{{ session('status') }}</p>
        @endif
    </div>
@endsection