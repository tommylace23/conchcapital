<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralBonusEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public float $amount,
        public string $referrerName,
        public string $reference,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Referral bonus credited — ' . config('app.name', 'ConchCapital'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.referral-bonus',
            with: [
                'user' => $this->user,
                'amount' => $this->amount,
                'referrerName' => $this->referrerName,
                'reference' => $this->reference,
                'appName' => config('app.name', 'ConchCapital'),
                'appUrl' => config('app.url'),
                'newBalance' => (float) $this->user->fresh()->real_balance,
            ],
        );
    }
}
