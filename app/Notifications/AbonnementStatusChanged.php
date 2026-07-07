<?php

namespace App\Notifications;

use App\Models\AbonnementAdherent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AbonnementStatusChanged extends Notification
{
    use Queueable;

    protected $abonnement;
    protected $customMessage;

    public function __construct(AbonnementAdherent $abonnement, ?string $customMessage = null)
    {
        $this->abonnement = $abonnement;
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
            $statut = $this->abonnement->statut;
            $labels = [
                'actif' => 'activé',
                'annule' => 'annulé',
                'expire' => 'expiré',
            ];
            $statutLabel = $labels[$statut] ?? $statut;
            $nomPlan = $this->abonnement->typeAbonnement?->nom ?? 'Abonnement';
            $complexeName = $this->abonnement->complexe?->name ?? 'Complexe';
            $message = "Votre abonnement " . $nomPlan . " chez " . $complexeName . " est " . $statutLabel . ".";
        }

        return [
            'type' => 'abonnement_status_changed',
            'abonnement_id' => $this->abonnement->id,
            'message' => $message,
        ];
    }
}
