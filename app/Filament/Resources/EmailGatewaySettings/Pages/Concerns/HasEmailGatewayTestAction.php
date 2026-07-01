<?php

namespace App\Filament\Resources\EmailGatewaySettings\Pages\Concerns;

use App\Gateways\Email\EmailGatewayService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

trait HasEmailGatewayTestAction
{
    protected function sendTestEmailAction(): Action
    {
        return Action::make('send_test_email')
            ->label('Send test email')
            ->icon('heroicon-o-paper-airplane')
            ->schema([
                TextInput::make('recipient')->email()->required(),
            ])
            ->action(function (array $data): void {
                try {
                    app(EmailGatewayService::class)->sendTest($data['recipient']);
                    app(EmailGatewayService::class)->recordTestResult($this->record, 'success', 'Test email sent to '.$data['recipient']);
                    Notification::make()->title('Test email sent')->success()->send();
                } catch (\Throwable $exception) {
                    app(EmailGatewayService::class)->recordTestResult($this->record, 'failed', $exception->getMessage());
                    Notification::make()->title('Test email failed')->body($exception->getMessage())->danger()->send();
                }
            });
    }
}