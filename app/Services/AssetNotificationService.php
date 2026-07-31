<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends asset-request notifications to a user.
 *
 * Ported from web-shelf minus the WhatsApp/Fonnte gateway (guzzle-based,
 * not present in this shelf). Email delivery is retained via the framework
 * Mail facade; WhatsApp notifications are logged and skipped.
 */
class AssetNotificationService
{
    /**
     * Notify a user about an asset-request event.
     *
     * @param  string|array{whatsapp: string, email?: array<string, mixed>}  $message
     * @return array{whatsapp: bool|null, email: bool|null}
     */
    public static function send(User $user, string $subject, string|array $message): array
    {
        $whatsappMessage = is_array($message) ? (string) ($message['whatsapp'] ?? '') : $message;
        $emailBody = is_array($message) ? (string) ($message['email']['body'] ?? $whatsappMessage) : $whatsappMessage;

        $result = [
            'whatsapp' => null,
            'email' => null,
        ];

        // 1. WhatsApp channel is not available in this shelf — log and skip.
        if ($user->whatsapp_number) {
            $result['whatsapp'] = false;
            Log::info('Notifikasi WhatsApp dilewati (gateway tidak tersedia).', [
                'user_id' => $user->id,
                'subject' => $subject,
                'message' => $whatsappMessage,
            ]);
        }

        // 2. Send email when the address is present and valid.
        if ($user->email && self::isSafeEmail($user->email)) {
            try {
                Mail::to($user->email)->raw($emailBody, function ($mailable) use ($subject) {
                    $mailable->subject($subject);
                });
                $result['email'] = true;
            } catch (Throwable $e) {
                $result['email'] = false;
                Log::error('Gagal mengirim email notifikasi pengajuan aset: '.$e->getMessage(), [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        } elseif ($user->email) {
            $result['email'] = false;
            Log::warning('Email notifikasi pengajuan aset dilewati karena alamat tidak aman.', [
                'user_id' => $user->id,
            ]);
        }

        return $result;
    }

    private static function isSafeEmail(string $email): bool
    {
        return preg_match('/[\r\n]/', $email) !== 1
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
