<?php

namespace App\Notifications\Mission;

use App\Enums\Mission\MissionAssignmentNotificationType;
use App\Models\Administration\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MissionAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly MissionAssignmentNotificationType $notificationType,
        public readonly string $missionUuid,
        public readonly string $missingFormUuid,
        public readonly string $registryNumber,
        public readonly string $formNumber,
        public readonly string $searchLocation,
        public readonly string $priority,
        public readonly ?CarbonImmutable $dueDate,
        public readonly string $assignedBy,
        public readonly ?string $reason = null,
    ) {
        $this->onQueue('notifications');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->notificationType->label(),
            'message' => $this->message(),
            'mission_uuid' => $this->missionUuid,
            'missing_form_uuid' => $this->missingFormUuid,
            'registry_number' => $this->registryNumber,
            'form_number' => $this->formNumber,
            'search_location' => $this->searchLocation,
            'priority' => $this->priority,
            'due_date' => $this->dueDate?->toDateString(),
            'assigned_by' => $this->assignedBy,
            'reason' => $this->reason,
            'type' => $this->notificationType->value,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var UserModel $notifiable */
        $actionUrl = route('missions.show', $this->missionUuid);
        $actionText = $this->actionLabel();
        $formattedDueDate = $this->dueDate?->format('d/m/Y');

        $mail = (new MailMessage)
            ->subject($this->notificationType->label().' — '.$this->registryNumber)
            ->greeting('Bonjour '.$notifiable->first_names.',')
            ->line($this->message())
            ->line('Formulaire : '.$this->formNumber)
            ->line('Localisation : '.$this->searchLocation)
            ->line('Priorité : '.$this->priority);

        if ($formattedDueDate !== null) {
            $mail->line('Échéance : '.$formattedDueDate);
        }
        if ($this->reason !== null && $this->reason !== '') {
            $mail->line('Motif : '.$this->reason);
        }

        return $mail
            ->line('Action effectuée par : '.$this->assignedBy)
            ->action($actionText, $actionUrl)
            ->view($this->templateView(), [
                'notificationType' => $this->notificationType->value,
                'recipientFirstName' => $notifiable->first_names,
                'missionUuid' => $this->missionUuid,
                'missingFormUuid' => $this->missingFormUuid,
                'registryNumber' => $this->registryNumber,
                'formNumber' => $this->formNumber,
                'searchLocation' => $this->searchLocation,
                'priority' => $this->priority,
                'dueDate' => $formattedDueDate,
                'assignedBy' => $this->assignedBy,
                'reason' => $this->reason,
                'introMessage' => $this->message(),
                'actionLabel' => $actionText,
                'actionUrl' => $actionUrl,
            ]);
    }

    public function databaseType(object $notifiable): string
    {
        return 'mission_assignment';
    }

    private function templateView(): string
    {
        return match ($this->notificationType) {
            MissionAssignmentNotificationType::Assigned => 'emails.missions.assigned',
            MissionAssignmentNotificationType::Reassigned => 'emails.missions.reassigned',
            MissionAssignmentNotificationType::Withdrawn => 'emails.missions.withdrawn',
            MissionAssignmentNotificationType::Cancelled => 'emails.missions.cancelled',
        };
    }

    private function actionLabel(): string
    {
        return match ($this->notificationType) {
            MissionAssignmentNotificationType::Assigned,
            MissionAssignmentNotificationType::Reassigned => 'Consulter et traiter la mission',
            MissionAssignmentNotificationType::Withdrawn,
            MissionAssignmentNotificationType::Cancelled => 'Consulter la fiche de mission',
        };
    }

    private function message(): string
    {
        return match ($this->notificationType) {
            MissionAssignmentNotificationType::Assigned => "La mission du dossier {$this->registryNumber} vous a été affectée.",
            MissionAssignmentNotificationType::Reassigned => "La mission du dossier {$this->registryNumber} vous a été réaffectée.",
            MissionAssignmentNotificationType::Withdrawn => "La mission du dossier {$this->registryNumber} vous a été retirée.",
            MissionAssignmentNotificationType::Cancelled => "La mission du dossier {$this->registryNumber} a été annulée.",
        };
    }
}
