<?php

namespace App\Gateways\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestGatewayMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function build(): self
    {
        return $this
            ->subject('Tinggal Jalan email gateway test')
            ->html('<p>This is a Tinggal Jalan email gateway test.</p>');
    }
}