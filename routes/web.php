<?php

use App\Http\Controllers\CoreSsoController;
use App\Http\Controllers\PublicAssetRequestController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/auth/redirect', [CoreSsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/callback', [CoreSsoController::class, 'callback'])->name('sso.callback');

// Public asset-request portal (named routes are referenced by AssetRequest model
// and Filament resources). WhatsApp gateway and PDF letterhead are intentionally
// omitted in this shelf.
Route::get('asset-requests', [PublicAssetRequestController::class, 'index'])->name('public.asset-requests.index');
Route::post('asset-requests', [PublicAssetRequestController::class, 'store'])->middleware(['throttle:public'])->name('public.asset-requests.store');
Route::get('asset-requests/status/{token}', [PublicAssetRequestController::class, 'show'])->name('public.asset-requests.show');
Route::get('asset-requests/approval/{token}', [PublicAssetRequestController::class, 'showApproval'])->name('public.asset-requests.approval');
Route::post('asset-requests/approval/{token}/approve', [PublicAssetRequestController::class, 'approveApproval'])->middleware(['throttle:public'])->name('public.asset-requests.approval.approve');
Route::post('asset-requests/approval/{token}/reject', [PublicAssetRequestController::class, 'rejectApproval'])->middleware(['throttle:public'])->name('public.asset-requests.approval.reject');
