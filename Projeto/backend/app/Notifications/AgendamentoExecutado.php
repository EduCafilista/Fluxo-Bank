<?php

namespace App\Notifications;

use App\Models\Agendamento;
use Illuminate\Notifications\Notification;

class AgendamentoExecutado extends Notification
{
    public function __construct(private Agendamento $agendamento) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agendamento_id' => $this->agendamento->id,
            'transacao_id' => $this->agendamento->transacao_id,
            'mensagem' => 'Seu agendamento foi executado.',
        ];
    }
}
