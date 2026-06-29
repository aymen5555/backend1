<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationStatusChanged extends Notification
{
    use Queueable;

    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $status = $this->reservation->status;
        $statusLabel = $status === 'played' ? 'terminée' : 'expirée';
        $dateStr = $this->reservation->start_at ? $this->reservation->start_at->format('Y-m-d') : '';
        $timeStr = $this->reservation->start_at ? $this->reservation->start_at->format('H:i') : '';
        $message = "Votre réservation du " . $dateStr . " à " . $timeStr . " est " . $statusLabel . ".";

        return [
            'type' => 'reservation_status_changed',
            'reservation_id' => $this->reservation->id,
            'message' => $message,
        ];
    }
}
