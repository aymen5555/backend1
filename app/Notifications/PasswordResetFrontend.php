<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetFrontend extends Notification
{
    public function __construct(private readonly string $resetUrl)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Réinitialisation du mot de passe')
            ->line("Vous recevez cet e-mail parce que nous avons reçu une demande de réinitialisation du mot de passe pour votre compte.")
            ->action('Réinitialiser le mot de passe', $this->resetUrl)
            ->line("Si vous n'avez pas demandé cette réinitialisation, aucune autre action n'est requise.");
    }
}
