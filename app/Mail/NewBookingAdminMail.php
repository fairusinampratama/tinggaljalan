<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewBookingAdminMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("New Tinggal Jalan booking: {$this->booking->booking_code}")
            ->view('emails.new-booking-admin')
            ->text('emails.new-booking-admin-text');
    }
}