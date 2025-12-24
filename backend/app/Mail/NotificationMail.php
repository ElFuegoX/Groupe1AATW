<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable pour l'envoi de notifications par email
 *
 * Ce mailable utilise un template Blade pour générer le contenu de l'email
 */
class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Créer une nouvelle instance du mailable
     *
     * @param Notification $notification
     */
    public function __construct(
        public Notification $notification
    ) {
    }

    /**
     * Obtenir l'enveloppe du message
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->subject,
        );
    }

    /**
     * Obtenir le contenu du message
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'notification' => $this->notification,
                'recipientName' => $this->notification->recipient_name,
                'body' => $this->notification->body,
                'variables' => $this->notification->variables ?? [],
            ],
        );
    }

    /**
     * Obtenir les pièces jointes du message
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

