<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    public function __construct(private readonly User $user)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $roleLabel = match ($this->user->role) {
            'gerant' => 'gérant',
            'client' => 'client',
            default => $this->user->role,
        };

        return [
            'type' => 'new_user_registered',
            'user_id' => $this->user->id,
            'role' => $this->user->role,
            'message' => 'Un nouveau ' . $roleLabel . ' a été ajouté : ' . $this->user->first_name . ' ' . $this->user->last_name,
        ];
    }
}
