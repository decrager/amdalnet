<?php

namespace App\Notifications;

use App\Entity\TestingMeeting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Notifications\Action;

class MeetingInvitation extends Notification
{
    use Queueable;

    public $meeting;
    public $attachment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(TestingMeeting $meeting, $idProject, $attachment, $pdf, $filename, $role, $user)
    {
        $this->meeting = $meeting;
        $this->attachment = $attachment;
        $this->id_project = $idProject;
        $this->pdf = $pdf;
        $this->filename = $filename;
        $this->role = $role;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        Carbon::setLocale('id');
        $document = '';
        $url = url("/#/project/docspace/". $this->id_project). "/". $this->meeting->document_type . "?" . "idProject=" . $this->id_project . "&" . 'document_type=' . $this->meeting->document_type;
        $urlRkl = url("/#/project/docspace/". $this->id_project). "/". 'rkl-rpl' . "?" . "idProject=" . $this->id_project . "&" . 'document_type=' . 'rkl-rpl';
        $urlAndal = url("/#/project/docspace/". $this->id_project). "/". 'ka-andal' . "?" . "idProject=" . $this->id_project . "&" . 'document_type=' . 'ka-andal';
        $role = '';

        if ($this->role[0] == 'examiner-chief') {
            $role = 'Ketua Ahli';
        } else if ($this->role[0] == 'examiner-secretary') {
            $role = 'Kepala Sekretariat';
        } else if ($this->role[0] == 'examiner-administration') {
            $role = 'Validator Administrasi';
        } else if ($this->role[0] == 'examiner-substance') {
            $role = 'Validator Substansi';
        } else if ($this->role[0] == 'examiner-community') {
            $role = 'Ahli Komunitas';
        }

        if($this->meeting->document_type == 'ka') {
            $document = 'Formulir Kerangka Acuan';
        } else if($this->meeting->document_type == 'rkl-rpl') {
            $document = 'Dokumen Andal dan RKL RPL';
        }
        $link = '-';
        if ($this->meeting->link !== '-') {
            $link = "&lt;a href='{$this->meeting->link}'&gt;{$this->meeting->link}&lt;/a&gt;";
        }
        if (is_array($this->user)) {
            $urlActive = url("/#/activate/".$this->user['id']);
            // dd($this->user);
            if ($this->meeting->document_type == 'rkl-rpl') {
                return (new MailMessage)
                ->subject('Undangan Rapat Pembahasan ' . $this->documentType())
                ->greeting('Yth. ' . $notifiable->name)
                ->line('Sehubungan dengan akan diadakannya acara rapat Pemeriksan ' . $document . ' dengan nama kegiatan/usaha ' . $this->meeting->project->project_title . ', Maka kami mengundang bapak/ibu untuk hadir dalam acara tersebut yang akan dilaksanakan pada:')
                ->line(new HtmlString('Hari/Tanggal: ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('dddd') . ', ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('D MMMM Y') . '<br>Waktu: ' . date('H:i', strtotime($this->meeting->meeting_time)) . ' ' . $this->meeting->zone. '<br>' . 'Tempat: ' . $this->meeting->location . '<br>' . 'Link Meeting: ' . htmlspecialchars_decode($link)))
                ->line('Untuk memberikan Saran Masukan atau Tanggapan, silakan login ke dalam sistem Amdalnet terlebih dulu menggunakan akun yg telah diberikan.')
                ->action('Klik Untuk Memberikan SPT RKL-RPL', $urlRkl)
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Memberikan SPT ANDAL', $urlAndal)))
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Mengunduh Dokumen RKL - RPL', $this->pdf['rkl'])))
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Mengunduh Dokumen ANDAL', $this->pdf['andal'])))
                ->line(new HtmlString('Akun AMDALNET anda telah berhasil dibuat dengan password: <b>' . $this->user['password'] . '</b>'))
                ->line('Silahkan aktivasi akun dengan menekan tombol dibawah ini.')
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Aktivasi Akun', $urlActive)))
                ->line('Demikian undangan ini kami sampaikan, mengingat pentingnya acara tersebut maka dimohon kehadiran tepat pada waktunya, Atas perhatiannya, kami ucapkan terimakasih.')
                ->salutation(new HtmlString('Hormat kami,<br>' . Auth::user()->name . '<br>' . $role))
                ->attach($this->attachment, [
                    'as' => 'Undangan Rapat.pdf',
                    'mime' => 'application/pdf'
                ]);
                // ->attach($this->pdf['rkl'], [
                //     'as' => $this->filename['rkl'].'.pdf',
                //     'mime' => 'application/pdf'
                // ])
                // ->attach($this->pdf['andal'], [
                //     'as' => $this->filename['andal'].'.pdf',
                //     'mime' => 'application/pdf'
                // ]);
            } else {
                return (new MailMessage)
                ->subject('Undangan Rapat Pembahasan ' . $this->documentType())
                ->greeting('Yth. ' . $notifiable->name)
                ->line('Sehubungan dengan akan diadakannya acara rapat Pemeriksan ' . $document . ' dengan nama kegiatan/usaha ' . $this->meeting->project->project_title . ', Maka kami mengundang bapak/ibu untuk hadir dalam acara tersebut yang akan dilaksanakan pada:')
                ->line(new HtmlString('Hari/Tanggal: ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('dddd') . ', ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('D MMMM Y') . '<br>Waktu: ' . date('H:i', strtotime($this->meeting->meeting_time)) . ' ' . $this->meeting->zone. '<br>' . 'Tempat: ' . $this->meeting->location . '<br>' . 'Link Meeting: ' . htmlspecialchars_decode($link)))
                ->line('Untuk memberikan Saran Masukan atau Tanggapan, silakan login ke dalam sistem Amdalnet terlebih dulu menggunakan akun yg telah diberikan.')
                ->action('Klik Untuk Memberikan SPT', $url)
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Mengunduh Dokumen', $this->pdf)))
                ->line(new HtmlString('Akun AMDALNET anda telah berhasil dibuat dengan password: <b>' . $this->user['password'] . '</b>'))
                ->line('Silahkan aktivasi akun dengan menekan tombol dibawah ini.')
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Aktivasi Akun', $urlActive)))
                ->line('Demikian undangan ini kami sampaikan, mengingat pentingnya acara tersebut maka dimohon kehadiran tepat pada waktunya, Atas perhatiannya, kami ucapkan terimakasih.')
                ->salutation(new HtmlString('Hormat kami,<br>' . Auth::user()->name . '<br>' . $role))
                ->attach($this->attachment, [
                    'as' => 'Undangan Rapat.pdf',
                    'mime' => 'application/pdf'
                ]);
                // ->attach($this->pdf, [
                //     'as' => $this->filename.'.pdf',
                //     'mime' => 'application/pdf'
                // ]);
            }
        }

        if (!is_array($this->user)) {
            if ($this->meeting->document_type == 'rkl-rpl') {
                return (new MailMessage)
                ->subject('Undangan Rapat Pembahasan ' . $this->documentType())
                ->greeting('Yth. ' . $notifiable->name)
                ->line('Sehubungan dengan akan diadakannya acara rapat Pemeriksan ' . $document . ' dengan nama kegiatan/usaha ' . $this->meeting->project->project_title . ', Maka kami mengundang bapak/ibu untuk hadir dalam acara tersebut yang akan dilaksanakan pada:')
                ->line(new HtmlString('Hari/Tanggal: ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('dddd') . ', ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('D MMMM Y') . '<br>Waktu: ' . date('H:i', strtotime($this->meeting->meeting_time)) . ' ' . $this->meeting->zone. '<br>' . 'Tempat: ' . $this->meeting->location . '<br>' . 'Link Meeting: ' . htmlspecialchars_decode($link)))
                ->line('Untuk memberikan Saran Masukan atau Tanggapan, silakan login ke dalam sistem Amdalnet terlebih dulu menggunakan akun yg telah diberikan.')
                ->action('Klik Untuk Memberikan SPT RKL-RPL', $urlRkl)
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Memberikan SPT ANDAL', $urlAndal)))
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Mengunduh Dokumen RKL - RPL', $this->pdf['rkl'])))
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Mengunduh Dokumen ANDAL', $this->pdf['andal'])))
                ->line('Demikian undangan ini kami sampaikan, mengingat pentingnya acara tersebut maka dimohon kehadiran tepat pada waktunya, Atas perhatiannya, kami ucapkan terimakasih.')
                ->salutation(new HtmlString('Hormat kami,<br>' . Auth::user()->name . '<br>' . $role))
                ->attach($this->attachment, [
                    'as' => 'Undangan Rapat.pdf',
                    'mime' => 'application/pdf'
                ]);
                // ->attach($this->pdf['rkl'], [
                //     'as' => $this->filename['rkl'].'.pdf',
                //     'mime' => 'application/pdf'
                // ])
                // ->attach($this->pdf['andal'], [
                //     'as' => $this->filename['andal'].'.pdf',
                //     'mime' => 'application/pdf'
                // ]);
            } else {
                return (new MailMessage)
                ->subject('Undangan Rapat Pembahasan ' . $this->documentType())
                ->greeting('Yth. ' . $notifiable->name)
                ->line('Sehubungan dengan akan diadakannya acara rapat Pemeriksan ' . $document . ' dengan nama kegiatan/usaha ' . $this->meeting->project->project_title . ', Maka kami mengundang bapak/ibu untuk hadir dalam acara tersebut yang akan dilaksanakan pada:')
                ->line(new HtmlString('Hari/Tanggal: ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('dddd') . ', ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('D MMMM Y') . '<br>Waktu: ' . date('H:i', strtotime($this->meeting->meeting_time)) . ' ' . $this->meeting->zone. '<br>' . 'Tempat: ' . $this->meeting->location . '<br>' . 'Link Meeting: ' . htmlspecialchars_decode($link)))
                ->line('Untuk memberikan Saran Masukan atau Tanggapan, silakan login ke dalam sistem Amdalnet terlebih dulu menggunakan akun yg telah diberikan.')
                ->action('Klik Untuk Memberikan SPT', $url)
                ->line($this->makeActionIntoLine(new Action('Klik Untuk Mengunduh Dokumen', $this->pdf)))
                ->line('Demikian undangan ini kami sampaikan, mengingat pentingnya acara tersebut maka dimohon kehadiran tepat pada waktunya, Atas perhatiannya, kami ucapkan terimakasih.')
                ->salutation(new HtmlString('Hormat kami,<br>' . Auth::user()->name . '<br>' . $role))
                ->attach($this->attachment, [
                    'as' => 'Undangan Rapat.pdf',
                    'mime' => 'application/pdf'
                ]);
                // ->attach($this->pdf, [
                //     'as' => $this->filename.'.pdf',
                //     'mime' => 'application/pdf'
                // ]);
            }
        }
    }

    private function makeActionIntoLine(Action $action): Htmlable {
        return new class($action) implements Htmlable {
            private $action;

            public function __construct(Action $action) {
                $this->action = $action;
            } // end __construct()

            public function toHtml() {
                return $this->strip($this->table());
            } // end toHtml()

            private function table() {
                return sprintf(
                    '<table class="action">
                        <tr>
                        <td align="center">%s</td>
                    </tr></table>
                ', $this->btn());
            } // end table()

            private function btn() {
                return sprintf(
                    '<a
                        href="%s"
                        class="button button-primary"
                        target="_blank"
                        style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; box-sizing: border-box; border-radius: 3px; box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16); color: #fff; display: inline-block; text-decoration: none; -webkit-text-size-adjust: none; background-color: #3490dc; border-top: 10px solid #3490dc; border-right: 18px solid #3490dc; border-bottom: 10px solid #3490dc; border-left: 18px solid #3490dc;"
                    >%s</a>',
                    htmlspecialchars($this->action->url),
                    htmlspecialchars($this->action->text)
                );
            } // end btn()

            private function strip($text) {
                return str_replace("\n", ' ', $text);
            } // end strip()

        };
    } // end makeActionIntoLine()

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        Carbon::setLocale('id');
        $document = '';
        if($this->meeting->document_type == 'ka') {
            $document = 'Formulir Kerangka Acuan';
        } else if($this->meeting->document_type == 'rkl-rpl') {
            $document = 'Dokumen Andal dan RKL RPL';
        }
        if(!$notifiable->roles->contains('name', 'formulator')) {
            $message = 'Sehubungan dengan akan diadakannya acara rapat Pemeriksan ' . $document . ' dengan nama kegiatan/usaha ' . $this->meeting->project->project_title . ', Maka kami mengundang bapak/ibu untuk hadir dalam acara tersebut yang akan dilaksanakan pada: ';
            $message .= 'Hari/Tanggal: ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('dddd') . ', ' . Carbon::createFromFormat('Y-m-d', $this->meeting->meeting_date)->isoFormat('D MMMM Y') . ', ';
            $message .= 'Waktu: ' . $this->meeting->meeting_time . ', ';
            $message .= 'Tempat: ' . $this->meeting->location . '. ';
            $message .= 'Demikian undangan ini kami sampaikan, mengingat pentingnya acara tersebut maka dimohon kehadiran tepat pada waktunya, Atas perhatiannya, kami ucapkan terimakasih. ';
            $message .= 'Hormat kami, ' . Auth::user()->name;

            return [
                'meeting' => $this->meeting ,
                'member' => $notifiable,
                'message' => $message,
                'path' => '#',
            ];
        } else {
            return [
                'meeting' => $this->meeting ,
                'member' => $notifiable,
                'message' => 'Kegiatan ' . $this->meeting->project->project_title . ' akan dilakukan pembahasan ' . $this->documentType() . ' oleh Tim Uji Kelayakan',
                'path' => '#',
            ];
        }
    }

    private function documentType()
    {
        $document_type = '';
        if($this->meeting->document_type == 'ka') {
            $document_type = 'Kerangka Acuan';
        } else if($this->meeting->document_type == 'rkl-rpl') {
            $document_type = 'Analisis Dampak Lingkungan dan Rencana Pengelolaan Hidup dan Rencana Pemantauan Lingkungan Hidup';
        } else if($this->meeting->document_type == 'ukl-upl') {
            $document_type = 'Upaya Pengelolaan Lingkungan Hidup dan Upaya Pemantauan Lingkungan Hidup';
        }

        return $document_type;
    }
}
