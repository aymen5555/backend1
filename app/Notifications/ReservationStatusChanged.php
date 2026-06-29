<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationStatusChanged extends Notification
{
    use Queueable;

    protected $reservation;
    protected $customMessage;

    public function __construct(Reservation $reservation, ?string $customMessage = null)
    {
        $this->reservation = $reservation;
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
            $status = $this->reservation->status;
            $labels = [
                'confirmed' => 'confirmée',
                'cancelled' => 'annulée',
                'played' => 'terminée',
                'expired' => 'expirée',
                'pending' => 'en attente',
            ];
            $statusLabel = $labels[$status] ?? $status;
            $dateStr = $this->reservation->start_at ? $this->reservation->start_at->format('Y-m-d') : ($this->reservation->date_seance_res ?? '');
            $timeStr = $this->reservation->start_at ? $this->reservation->start_at->format('H:i') : ($this->reservation->heure_debut_res ?? '');
            $message = "Votre réservation du " . $dateStr . ($timeStr ? " à " . $timeStr : "") . " est " . $statusLabel . ".";
        }

        return [
            'type' => 'reservation_status_changed',
            'reservation_id' => $this->reservation->id,
            'message' => $message,
        ];
    }
}

