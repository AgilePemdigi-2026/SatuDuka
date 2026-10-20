<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Surel tautan pengaturan ulang kata sandi SATUDUKA (berbahasa Indonesia).
 *
 * Tautan mengarah ke halaman `atur_ulang_kata_sandi` beserta surel terdaftar
 * pemilik akun.
 */
class TautanAturUlangKataSandi extends ResetPassword
{
    /**
     * Membangun isi surel.
     *
     * @param  string  $url  Tautan pengaturan ulang kata sandi.
     */
    protected function buildMailMessage($url)
    {
        $menitBerlaku = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Pengaturan Ulang Kata Sandi SATUDUKA')
            ->greeting('Halo,')
            ->line('Anda menerima surel ini karena kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda.')
            ->action('Atur Ulang Kata Sandi', $url)
            ->line("Tautan ini kedaluwarsa dalam {$menitBerlaku} menit.")
            ->line('Bila Anda tidak meminta pengaturan ulang kata sandi, abaikan surel ini.');
    }

    /**
     * Tautan pengaturan ulang memakai route aplikasi (bukan route bawaan).
     *
     * @param  mixed  $notifiable
     */
    protected function resetUrl($notifiable)
    {
        return route('atur_ulang_kata_sandi', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
