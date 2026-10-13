@extends('emails.layouts.oipi', [
    'title' => 'Nouvelle mission affectée — ' . $registryNumber,
])

@section('content')
    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #17352d; line-height: 1.3;">
        Nouvelle mission affectée
    </h1>

    <p style="margin: 0 0 12px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        Bonjour {{ $recipientFirstName }},
    </p>

    <p style="margin: 0 0 16px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        {{ $introMessage }} Vous êtes invité(e) à démarrer les vérifications physiques dans la localisation désignée et à consigner le résultat de votre recherche dans le système.
    </p>

    @include('emails.missions._details_table', [
        'locationLabel' => 'Localisation à inspecter',
        'dueDateLabel' => 'Échéance de traitement',
        'actorLabel' => 'Affectée par',
    ])

    <p style="margin: 0 0 24px 0; font-size: 14px; color: #5f6b66; line-height: 1.6;">
        Connectez-vous à votre espace RISFM pour consulter la fiche complète et enregistrer votre compte rendu avant la date d’échéance.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 8px 0;">
        <tr>
            <td align="center">
                <a href="{{ $actionUrl }}" class="cta-button" target="_blank" rel="noopener" style="display: inline-block; background-color: #009846; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.04em;">
                    {{ $actionLabel }}
                </a>
            </td>
        </tr>
    </table>
@endsection
