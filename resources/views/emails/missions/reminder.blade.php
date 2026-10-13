@php
    $isDanger = in_array($reminderType ?? '', ['overdue', 'escalation'], true);
@endphp

@extends('emails.layouts.oipi', [
    'title' => ($title ?? 'Rappel de mission') . ' — ' . $registryNumber,
])

@section('content')
    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #17352d; line-height: 1.3;">
        {{ $title ?? 'Rappel d’échéance de mission' }}
    </h1>

    <p style="margin: 0 0 12px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        Bonjour {{ $recipientFirstName ?? 'Agent' }},
    </p>

    <p style="margin: 0 0 16px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        {{ $introMessage }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0 22px 0; background-color: #fbfcfb; border: 1px solid #e6ebe8; border-radius: 6px;">
        <tr>
            <td class="meta-label" width="42%" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 13px; color: #5f6b66;">
                N° de dossier
            </td>
            <td class="meta-value" width="58%" align="right" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 14px; font-weight: 700; color: #17352d; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; text-align: right;">
                {{ $registryNumber }}
            </td>
        </tr>
        <tr>
            <td class="meta-label" style="padding: 10px 16px; font-size: 13px; color: #5f6b66;">
                Date d’échéance
            </td>
            <td class="meta-value" align="right" style="padding: 10px 16px; font-size: 14px; font-weight: 700; color: {{ $isDanger ? '#dc2626' : '#17352d' }}; text-align: right;">
                {{ $dueDate }}
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 24px 0; font-size: 14px; color: #5f6b66; line-height: 1.6;">
        Veuillez vous connecter à RISFM afin de finaliser le traitement de cette mission dans les meilleurs délais.
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
