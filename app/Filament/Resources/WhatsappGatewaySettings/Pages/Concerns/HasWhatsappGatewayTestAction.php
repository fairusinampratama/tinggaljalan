<?php

namespace App\Filament\Resources\WhatsappGatewaySettings\Pages\Concerns;

use App\Gateways\WhatsApp\WhatsAppGatewayService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

trait HasWhatsappGatewayTestAction
{
    protected function sendTestWhatsappAction(): Action
    {
        return Action::make('send_test_whatsapp')
            ->label('Send test WhatsApp')
            ->icon('heroicon-o-paper-airplane')
            ->schema([
                TextInput::make('phone')->required(),
                TextInput::make('message')->default('This is a Tinggal Jalan WhatsApp gateway test.')->required(),
            ])
            ->action(function (array $data): void {
                try {
                    app(WhatsAppGatewayService::class)->sendTest($data['phone'], $data['message']);
                    app(WhatsAppGatewayService::class)->recordTestResult($this->record, 'success', 'Test WhatsApp sent to '.$data['phone']);
                    Notification::make()->title('Test WhatsApp sent')->success()->send();
                } catch (\Throwable $exception) {
                    app(WhatsAppGatewayService::class)->recordTestResult($this->record, 'failed', $exception->getMessage());
                    Notification::make()->title('Test WhatsApp failed')->body($exception->getMessage())->danger()->send();
                }
            });
    }
}