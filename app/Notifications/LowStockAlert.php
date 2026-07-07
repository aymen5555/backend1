<?php

namespace App\Notifications;

use App\Models\Stock;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification
{
    use Queueable;

    protected $stock;

    public function __construct(Stock $stock)
    {
        $this->stock = $stock;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $productName = $this->stock->produit?->nom ?? 'Produit';
        $qty = $this->stock->quantite_disponible;
        $min = $this->stock->quantite_minimale;
        $message = "Alerte stock faible : Le produit '{$productName}' est presque épuisé (Stock restant : {$qty}, Seuil minimum : {$min}).";

        return [
            'type' => 'low_stock_alert',
            'stock_id' => $this->stock->id,
            'message' => $message,
        ];
    }
}
