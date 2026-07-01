<?php

namespace App\Mail;

use App\Models\BookingPayment;
use App\Support\BookingLanguage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingPaymentInvoiceMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public BookingPayment $payment)
    {
    }

    public function build(): self
    {
        return $this
            ->locale(BookingLanguage::locale($this->payment->booking->communication_language))
            ->subject(BookingLanguage::translate('booking.invoice_subject', ['code' => $this->payment->booking->booking_code], $this->payment->booking->communication_language))
            ->view('emails.booking-payment-invoice')
            ->text('emails.booking-payment-invoice-text');
    }
}