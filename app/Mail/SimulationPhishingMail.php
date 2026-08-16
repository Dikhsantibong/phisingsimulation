<?php

namespace App\Mail;

use App\Models\Respondent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SimulationPhishingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Respondent $respondent) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Peringatan Keamanan: Aktivitas Tidak Biasa pada Akun Pembelajaran Digital Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.simulation-phishing',
            with: [
                'accessUrl' => route('simulation.access', ['respondent' => $this->respondent->session_token]),
                'rejectUrl' => route('simulation.access', ['respondent' => $this->respondent->session_token, 'action' => 'reject']),
            ],
        );
    }
}
