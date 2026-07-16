<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class AlertNotificationMail
 *
 * Mailable that sends alert notification emails to configured recipients.
 * Includes machine details, alert severity, description, and timestamp.
 *
 * @package App\Mail
 */
class AlertNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The alert that triggered the notification.
     *
     * @var Alert
     */
    public Alert $alert;

    /**
     * The machine hostname/device name.
     *
     * @var string
     */
    public string $computerName;

    /**
     * The employee mobile number (if set).
     *
     * @var string
     */
    public string $mobileNumber;

    /**
     * Create a new message instance.
     *
     * @param Alert $alert
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;

        $machine = $alert->machine;
        $this->computerName = $machine?->device_name
            ?? $machine?->hostname
            ?? $machine?->machine_uid
            ?? 'Unknown Machine';
        $this->mobileNumber = $machine?->employee_mobile_number ?? 'Not Set';
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        $severityPrefix = strtoupper($this->alert->severity ?? 'ALERT');

        return new Envelope(
            subject: "[DeskGuard {$severityPrefix}] {$this->alert->title} — {$this->computerName}",
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.alert-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        return [];
    }
}
