<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'provider', 'order_id', 'public_token', 'quote_currency', 'quote_amount', 'charge_currency', 'exchange_rate', 'exchange_rate_snapshot', 'charge_amount', 'status', 'snap_token', 'snap_url', 'expires_at', 'sent_at', 'whatsapp_opened_at', 'whatsapp_sent_at', 'whatsapp_failed_at', 'whatsapp_error', 'whatsapp_provider_message_id', 'whatsapp_raw_response', 'receipt_notifications_attempted_at', 'receipt_email_sent_at', 'receipt_email_failed_at', 'receipt_email_error', 'receipt_whatsapp_sent_at', 'receipt_whatsapp_opened_at', 'receipt_whatsapp_failed_at', 'receipt_whatsapp_error', 'receipt_whatsapp_provider_message_id', 'receipt_whatsapp_raw_response', 'paid_at', 'expired_at', 'failed_at', 'cancelled_at', 'midtrans_transaction_id', 'midtrans_payment_type', 'midtrans_transaction_status', 'midtrans_fraud_status', 'midtrans_raw_response', 'midtrans_raw_notification'])]
class BookingPayment extends Model
{
    protected function casts(): array
    {
        return [
            'quote_amount' => 'integer',
            'exchange_rate' => 'integer',
            'exchange_rate_snapshot' => 'array',
            'charge_amount' => 'integer',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'whatsapp_opened_at' => 'datetime',
            'whatsapp_sent_at' => 'datetime',
            'whatsapp_failed_at' => 'datetime',
            'whatsapp_raw_response' => 'array',
            'receipt_notifications_attempted_at' => 'datetime',
            'receipt_email_sent_at' => 'datetime',
            'receipt_email_failed_at' => 'datetime',
            'receipt_whatsapp_sent_at' => 'datetime',
            'receipt_whatsapp_opened_at' => 'datetime',
            'receipt_whatsapp_failed_at' => 'datetime',
            'receipt_whatsapp_raw_response' => 'array',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'midtrans_raw_response' => 'array',
            'midtrans_raw_notification' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
