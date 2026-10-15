<?php

namespace App\Notifications\Authentication;

use App\Models\Administration\UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Routing\UrlGenerator;
use RuntimeException;

final class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {}

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
    public function toMail(object $notifiable): MailMessage
    {
        /** @var UserModel $notifiable */
        $resetUrl = $this->resetUrl($notifiable);
        $expiresInMinutes = (int) config('auth.passwords.users.expire');
        $actionText = 'Réinitialiser mon mot de passe';

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe RISFM')
            ->greeting('Bonjour '.$notifiable->first_names.',')
            ->line('Une demande de réinitialisation du mot de passe de votre compte RISFM a été effectuée.')
            ->action($actionText, $resetUrl)
            ->line("Ce lien expirera dans {$expiresInMinutes} minutes.")
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez ce message et contactez l’administrateur en cas de doute.')
            ->view('emails.authentication.password-reset', [
                'recipientFirstName' => $notifiable->first_names,
                'expiresInMinutes' => $expiresInMinutes,
                'actionLabel' => $actionText,
                'actionUrl' => $resetUrl,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_code_unique' => $notifiable->user_code_unique,
        ];
    }

    private function resetUrl(UserModel $user): string
    {
        $baseUrl = (string) config('app.url');
        $parts = parse_url($baseUrl);

        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            throw new RuntimeException('APP_URL doit contenir une URL HTTP ou HTTPS valide pour les liens de réinitialisation.');
        }

        $applicationUrls = app(UrlGenerator::class);
        $urls = new UrlGenerator(app('router')->getRoutes(), $applicationUrls->getRequest());
        $urls->useOrigin($baseUrl);
        $urls->forceScheme($parts['scheme']);

        return $urls->route('password.reset', [
            'token' => $this->token,
            'email' => $user->getEmailForPasswordReset(),
        ]);
    }
}
