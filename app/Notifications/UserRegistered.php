<?php

namespace App\Notifications;

use App\Laravue\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class UserRegistered extends Notification
{
    use Queueable;

    public $user;
    public $original_password;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, $original_password = null)
    {
        $this->user = $user;
        $this->original_password = $original_password;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail','database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url("/#/activate/".$this->user->id);
        $raw_password = '';
        if (property_exists($this->user, 'raw_password')) {
            $raw_password = $this->user->getRawPassword();
        }
        if (!empty($raw_password)) {
            return (new MailMessage)
                            ->subject('Pendaftaran Akun AMDALNET')
                            ->greeting('Akun AMDALNET Anda Berhasil Dibuat.')
                            ->line('Hai '.$this->user->name)
                            ->line('')
                            ->line('Akun AMDALNET anda telah berhasil dibuat.')
                            ->line('Password: ' . $raw_password);
        } else if($this->original_password) {
            return (new MailMessage)
                    ->subject('Pendaftaran Akun AMDALNET')
                    ->greeting('Akun AMDALNET Anda Berhasil Dibuat.')
                    ->line('Hai '.$this->user->name)
                    ->line('')
                    ->line(new HtmlString('Akun AMDALNET anda telah berhasil dibuat dengan password: <b>' . $this->original_password . '</b>'))
                    ->line('Silahkan aktivasi akun dengan menekan tombol dibawah ini.')
                    ->action('Aktivasi Akun Anda', $url);
        } else {
            return (new MailMessage)
                            ->subject('Pendaftaran Akun AMDALNET')
                            ->greeting('Akun AMDALNET Anda Berhasil Dibuat.')
                            ->line('Hai '.$this->user->name)
                            ->line('')
                            ->line('Akun AMDALNET anda telah berhasil dibuat silahkan aktivasi akun dengan menekan tombol dibawah ini.')
                            ->action('Aktivasi Akun Anda', $url);
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'createdUser' => $this->user,
            'admin' => $notifiable,
            'message' => $this->user->name.' berhasil mendaftar dengan email '.$this->user->email,
        ];
    }
}
