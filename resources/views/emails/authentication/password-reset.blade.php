@extends('emails.layouts.oipi', [
    'title' => 'Réinitialisation de votre mot de passe RISFM',
])

@section('content')
    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #17352d; line-height: 1.3;">
        Réinitialisation de votre mot de passe
    </h1>

    <p style="margin: 0 0 12px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        Bonjour {{ $recipientFirstName }},
    </p>

    <p style="margin: 0 0 16px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        Une demande de réinitialisation du mot de passe associée à votre compte <strong>RISFM</strong> a été enregistrée.
    </p>

    <p style="margin: 0 0 24px 0; font-size: 14px; color: #5f6b66; line-height: 1.6;">
        Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe. Ce lien est à usage unique et expirera dans <strong>{{ $expiresInMinutes }} minutes</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px 0;">
        <tr>
            <td align="center">
                <a href="{{ $actionUrl }}" class="cta-button" target="_blank" rel="noopener" style="display: inline-block; background-color: #009846; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.04em;">
                    {{ $actionLabel }}
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-size: 13px; color: #5f6b66; line-height: 1.6;">
        Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet e-mail : votre compte demeure protégé et aucune modification n’a été effectuée.
    </p>
@endsection
