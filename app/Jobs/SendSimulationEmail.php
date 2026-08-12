<?php

namespace App\Jobs;

use App\Enums\RespondentStatus;
use App\Mail\SimulationPhishingMail;
use App\Models\Respondent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSimulationEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public Respondent $respondent) {}

    /**
     * Send the simulated phishing email and record the send timestamp.
     */
    public function handle(): void
    {
        $respondent = $this->respondent->fresh();

        if ($respondent === null || $respondent->status !== RespondentStatus::Pending) {
            return;
        }

        Mail::to($respondent->email)->send(new SimulationPhishingMail($respondent));

        $respondent->simulationEvent()->updateOrCreate([], ['sent_at' => now()]);
        $respondent->update(['status' => RespondentStatus::Sent]);
    }
}
