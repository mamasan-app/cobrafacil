<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RefundSuccessfulNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $store;
    protected $amount;

    public function __construct($store, $amount)
    {
        $this->store = $store;
        $this->amount = $amount;
    }

    public function via($notifiable)
    {
        return ['mail']; // Se enviará por correo
    }

    public function toMail($notifiable)
    {
        $storeUrl = env('APP_URL') . "/tienda/{$this->store->slug}/transactions";
        
        return (new MailMessage)
            ->subject('Pago de Vuelto Realizado Exitosamente')
            ->greeting('¡Hola ' . $this->store->name . '!')
            ->line('Se ha realizado un pago de vuelto exitosamente.')
            ->line('Monto recibido: Bs' . number_format($this->amount, 2) . ' USD')
            ->line('Gracias por confiar en nuestro servicio.')
            ->action('Ver detalles', $storeUrl)
            ->salutation('Saludos, Equipo de Mamapay');
    }
}
