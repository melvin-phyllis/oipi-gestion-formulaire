@php
    $priorityStyle = match (mb_strtolower((string) ($priority ?? ''))) {
        'urgente', 'urgent' => ['bg' => '#fef2f2', 'color' => '#dc2626'],
        'haute', 'high' => ['bg' => '#fff7ed', 'color' => '#c2410c'],
        'normale', 'normal' => ['bg' => '#f0fdf4', 'color' => '#009846'],
        default => ['bg' => '#f3f5f6', 'color' => '#5f6b66'],
    };
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0 22px 0; background-color: #fbfcfb; border: 1px solid #e6ebe8; border-radius: 6px;">
    <tr>
        <td class="meta-label" width="42%" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 13px; color: #5f6b66;">
            N° de dossier
        </td>
        <td class="meta-value" width="58%" align="right" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 14px; color: #17352d; font-weight: 700; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; text-align: right;">
            {{ $registryNumber }}
        </td>
    </tr>
    <tr>
        <td class="meta-label" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 13px; color: #5f6b66;">
            Formulaire recherché
        </td>
        <td class="meta-value" align="right" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 14px; color: #17352d; font-weight: 600; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; text-align: right;">
            {{ $formNumber }}
        </td>
    </tr>
    <tr>
        <td class="meta-label" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 13px; color: #5f6b66;">
            {{ $locationLabel ?? 'Localisation de recherche' }}
        </td>
        <td class="meta-value" align="right" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 14px; color: #17352d; font-weight: 600; text-align: right;">
            {{ $searchLocation }}
        </td>
    </tr>
    <tr>
        <td class="meta-label" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 13px; color: #5f6b66;">
            Priorité
        </td>
        <td class="meta-value" align="right" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 13px; text-align: right;">
            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; background-color: {{ $priorityStyle['bg'] }}; color: {{ $priorityStyle['color'] }};">
                {{ $priority }}
            </span>
        </td>
    </tr>
    @if (!empty($dueDate))
        <tr>
            <td class="meta-label" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 13px; color: #5f6b66;">
                {{ $dueDateLabel ?? 'Date d’échéance' }}
            </td>
            <td class="meta-value" align="right" style="padding: 10px 16px; border-bottom: 1px solid #edf1ef; font-size: 14px; color: #17352d; font-weight: 700; text-align: right;">
                {{ $dueDate }}
            </td>
        </tr>
    @endif
    <tr>
        <td class="meta-label" style="padding: 10px 16px; font-size: 13px; color: #5f6b66;">
            {{ $actorLabel ?? 'Action effectuée par' }}
        </td>
        <td class="meta-value" align="right" style="padding: 10px 16px; font-size: 14px; color: #17352d; font-weight: 600; text-align: right;">
            {{ $assignedBy }}
        </td>
    </tr>
</table>
