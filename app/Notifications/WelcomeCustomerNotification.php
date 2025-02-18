<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeCustomerNotification extends Notification
{
    use Queueable;

    protected string $magicLinkUrl;

    /**
     * Nombre de la tienda.
     *
     * @var string
     */
    protected $storeName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $magicLinkUrl, string $storeName)
    {
        $this->magicLinkUrl = $magicLinkUrl;
        $this->storeName = $storeName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('¡Bienvenido al Sistema de '.$this->storeName.'!')
            ->greeting('¡Hola '.$notifiable->first_name.'!')
            ->line('Gracias por unirte al sistema de '.$this->storeName.'. Estamos emocionados de tenerte con nosotros.')
            ->action('Acceder a tu Cuenta', $this->magicLinkUrl)
            ->line('Haz clic en el botón para acceder a tu cuenta fácilmente.')
            ->line('Si tienes alguna duda, no dudes en contactarnos.')
            ->salutation('Atentamente, el equipo de '.$this->storeName.'.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'magic_link_url' => $this->magicLinkUrl,
            'store_name' => $this->storeName,
        ];
    }
}
