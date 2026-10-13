<?php

namespace App\Notifications\Mission;

use App\Enums\Mission\MissionReminderType;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MissionReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly MissionReminderType $reminderType,
        public readonly string $missionUuid,
        public readonly string $missingFormUuid,
        public readonly string $registryNumber,
        public readonly CarbonImmutable $dueDate,
        public readonly int $daysDelta,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->reminderType->label(),
            'message' => $this->message(),
            'mission_uuid' => $this->missionUuid,
            'missing_form_uuid' => $this->missingFormUuid,
            'registry_number' => $this->registryNumber,
            'due_date' => $this->dueDate->toDateString(),
            'days_delta' => $this->daysDelta,
            'type' => $this->reminderType->value,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = route('missions.show', $this->missionUuid);
        $actionText = 'Consulter et traiter la mission';
        $formattedDueDate = $this->dueDate->format('d/m/Y');
        $recipientName = isset($notifiable->first_names) && is_string($notifiable->first_names)
            ? $notifiable->first_names
            : null;

        return (new MailMessage)
            ->subject($this->reminderType->label().' — '.$this->registryNumber)
            ->greeting($recipientName ? 'Bonjour '.$recipientName.',' : 'Bonjour,')
            ->line($this->message())
            ->line('Échéance : '.$formattedDueDate)
            ->action($actionText, $actionUrl)
            ->view('emails.missions.reminder', [
                'reminderType' => $this->reminderType->value,
                'title' => $this->reminderType->label(),
                'recipientFirstName' => $recipientName,
                'missionUuid' => $this->missionUuid,
                'missingFormUuid' => $this->missingFormUuid,
                'registryNumber' => $this->registryNumber,
                'dueDate' => $formattedDueDate,
                'daysDelta' => $this->daysDelta,
                'introMessage' => $this->message(),
                'actionLabel' => $actionText,
                'actionUrl' => $actionUrl,
            ]);
    }

    public function databaseType(object $notifiable): string
    {
        return 'mission_reminder';
    }

    private function message(): string
    {
        return match ($this->reminderType) {
            MissionReminderType::BeforeDue => "La mission du formulaire {$this->registryNumber} arrive bientôt à échéance.",
            MissionReminderType::DueToday => "La mission du formulaire {$this->registryNumber} arrive à échéance aujourd’hui.",
            MissionReminderType::Overdue => "La mission du formulaire {$this->registryNumber} est en retard.",
            MissionReminderType::Escalation => "La mission du formulaire {$this->registryNumber} nécessite une intervention du responsable.",
        };
    }
}
