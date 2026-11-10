<?php

namespace App\Mail;

use App\Models\Respondent;
use App\Support\SimulationThemeContent;
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
        $content = SimulationThemeContent::for($this->respondent->effectiveTheme());

        return new Envelope(
            subject: $content['email_subject'],
        );
    }

    public function content(): Content
    {
        $themeContent = SimulationThemeContent::for($this->respondent->effectiveTheme());

        return new Content(
            view: 'mail.simulation-phishing',
            with: [
                ...$themeContent,
                'accessUrl' => route('simulation.access', ['respondent' => $this->respondent->session_token]),
                'rejectUrl' => route('simulation.access', ['respondent' => $this->respondent->session_token, 'action' => 'reject']),
            ],
        );
    }
}
