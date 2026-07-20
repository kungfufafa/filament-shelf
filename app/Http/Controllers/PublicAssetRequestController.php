<?php

namespace App\Http\Controllers;

use App\Models\AssetRequest;
use App\Models\AssetRequestApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Minimal public-facing endpoints for asset requests.
 *
 * The full web-shelf public submission form (Fonnte WhatsApp, PDF letterhead,
 * complex validation) is intentionally trimmed here — only the status lookup
 * and approval/reject flows needed by the Filament resources are wired, so
 * the named routes (`public.asset-requests.*`) resolve.
 */
class PublicAssetRequestController extends Controller
{
    public function index(): View
    {
        return view('public.asset-requests.index');
    }

    public function show(string $token): View
    {
        $assetRequest = $this->resolveRequest($token);

        return view('public.asset-requests.show', [
            'assetRequest' => $assetRequest,
            'token' => $token,
        ]);
    }

    public function showApproval(string $token): View
    {
        $approval = AssetRequestApproval::where('public_token', $token)->firstOrFail();

        return view('public.asset-requests.approval', [
            'approval' => $approval,
            'assetRequest' => $approval->assetRequest,
            'token' => $token,
        ]);
    }

    public function approveApproval(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $approval = AssetRequestApproval::where('public_token', $token)->firstOrFail();

        try {
            $approval->assetRequest->approveCurrentLevel($data['notes'] ?? null, $approval->user);
        } catch (\Throwable $e) {
            Log::error('Public approval failed: '.$e->getMessage(), ['token' => $token]);

            return redirect()
                ->route('public.asset-requests.approval', $token)
                ->with('error', 'Gagal menyetujui pengajuan.');
        }

        return redirect()
            ->route('public.asset-requests.approval', $token)
            ->with('status', 'Pengajuan disetujui.');
    }

    public function rejectApproval(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'max:1000']]);

        $approval = AssetRequestApproval::where('public_token', $token)->firstOrFail();

        try {
            $approval->assetRequest->rejectCurrentLevel($data['notes'], $approval->user);
        } catch (\Throwable $e) {
            Log::error('Public rejection failed: '.$e->getMessage(), ['token' => $token]);

            return redirect()
                ->route('public.asset-requests.approval', $token)
                ->with('error', 'Gagal menolak pengajuan.');
        }

        return redirect()
            ->route('public.asset-requests.approval', $token)
            ->with('status', 'Pengajuan ditolak.');
    }

    public function store(Request $request): RedirectResponse
    {
        // Minimal placeholder: the full public submission flow is out of scope
        // for this shelf. Filament resources remain the primary entry point.
        return redirect()->route('public.asset-requests.index')
            ->with('status', 'Pengajuan diterima.');
    }

    protected function resolveRequest(string $token): AssetRequest
    {
        return AssetRequest::where('public_token', $token)->firstOrFail();
    }
}
