<?php

namespace App\Notifications;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification
{
    use Queueable;

    protected $commande;
    protected $customMessage;

    public function __construct(Commande $commande, ?string $customMessage = null)
    {
        $this->commande = $commande;
        $this->customMessage = $customMessage;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        if ($this->customMessage) {
            $message = $this->customMessage;
        } else {
            $statut = $this->commande->statut;
            $labels = [
                'en_attente' => 'en attente de validation',
                'confirmee' => 'confirmée',
                'preparee' => 'préparée et prête à être récupérée',
                'livree' => 'livrée',
                'annulee' => 'annulée',
            ];
            $statutLabel = $labels[$statut] ?? $statut;
            $message = "Votre commande #{$this->commande->id} est {$statutLabel}.";
        }

        return [
            'type' => 'order_status_changed',
            'commande_id' => $this->commande->id,
            'message' => $message,
        ];
    }
}
