@extends('emails.layouts.oipi', [
    'title' => 'Mission retirée — ' . $registryNumber,
])

@section('content')
    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #17352d; line-height: 1.3;">
        Mission retirée
    </h1>

    <p style="margin: 0 0 12px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        Bonjour {{ $recipientFirstName }},
    </p>

    <p style="margin: 0 0 16px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        {{ $introMessage }} Cette mission a fait l’objet d’une réaffectation opérationnelle. Vous n’êtes plus en charge des recherches pour cette affectation.
    </p>

    @if (!empty($reason))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 16px 0; background-color: #fbfcfb; border: 1px solid #e6ebe8; border-left: 3px solid #5f6b66; border-radius: 4px;">
            <tr>
                <td style="padding: 11px 15px;">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #5f6b66; margin-bottom: 3px;">
                        Motif du retrait
                    </div>
                    <div style="font-size: 14px; color: #17352d; line-height: 1.5;">
                        {{ $reason }}
                    </div>
                </td>
            </tr>
        </table>
    @endif

    @include('emails.missions._details_table', [
        'locationLabel' => 'Localisation concernée',
        'dueDateLabel' => 'Échéance initiale',
        'actorLabel' => 'Retirée par',
    ])

    <p style="margin: 0 0 24px 0; font-size: 14px; color: #5f6b66; line-height: 1.6;">
        Aucune action supplémentaire n’est attendue de votre part sur cette mission. Elle demeure consultable à titre d’historique.
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
