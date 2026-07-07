<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReservationCreated extends Notification
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
        $clientName = $this->reservation->user ? ($this->reservation->user->first_name . ' ' . $this->reservation->user->last_name) : 'Un client';
        $terrainName = $this->reservation->terrain?->name ?? 'Terrain';
        $dateStr = $this->reservation->start_at ? $this->reservation->start_at->format('d/m/Y à H:i') : '';
        $message = "Nouvelle réservation effectuée par {$clientName} pour le terrain {$terrainName} le {$dateStr}.";

        return [
            'type' => 'new_reservation_created',
            'reservation_id' => $this->reservation->id,
            'message' => $message,
        ];
    }
}
