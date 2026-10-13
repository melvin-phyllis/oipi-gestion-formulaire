<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
    <title>{{ $title ?? 'Notification RISFM | OIPI' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style type="text/css">
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
        }
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            background-color: #f3f5f6;
            color: #17352d;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .email-card {
                padding: 24px 18px !important;
            }
            .meta-label, .meta-value {
                display: block !important;
                width: 100% !important;
                text-align: left !important;
                padding: 6px 10px !important;
            }
            .meta-label {
                padding-bottom: 2px !important;
            }
            .meta-value {
                padding-top: 0 !important;
                padding-bottom: 10px !important;
            }
            .cta-button {
                display: block !important;
                width: 100% !important;
                text-align: center !important;
                box-sizing: border-box !important;
                padding: 14px 20px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; width: 100% !important; background-color: #f3f5f6; color: #17352d; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f5f6; margin: 0; padding: 36px 12px 44px 12px; width: 100%;">
        <tr>
            <td align="center">
                <!-- CONTENEUR GLOBAL (580px max) -->
                <table role="presentation" class="email-container" width="580" cellpadding="0" cellspacing="0" border="0" style="width: 580px; max-width: 580px; margin: 0 auto;">
                    <!-- 1. LOGO CENTRÉ AU-DESSUS DE LA CARTE -->
                    <tr>
                        <td align="center" style="padding: 0 0 26px 0;">
                            <img src="{{ isset($message) ? $message->embed(public_path('images/oipi-logo.jpg')) : asset('images/oipi-logo.jpg') }}"
                                 alt="Office Ivoirien de la Propriété Intellectuelle (OIPI)"
                                 width="160"
                                 height="53"
                                 style="display: block; margin: 0 auto; border: 0; outline: none; text-decoration: none; max-width: 160px; height: auto;" />
                        </td>
                    </tr>

                    <!-- 2. CARTE BLANCHE CENTRALE -->
                    <tr>
                        <td class="email-card" style="background-color: #ffffff; border: 1px solid #e2e8e5; border-radius: 6px; padding: 38px 36px;">
                            @yield('content')

                            <!-- LIEN DE SECOURS -->
                            @if (!empty($actionUrl))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 26px; padding-top: 18px; border-top: 1px solid #edf1ef;">
                                    <tr>
                                        <td style="font-size: 12px; color: #5f6b66; line-height: 1.5; word-break: break-all;">
                                            Si le bouton « {{ $actionLabel ?? 'Accéder à la plateforme' }} » ne s’affiche pas correctement, copiez et collez le lien suivant dans votre navigateur :<br>
                                            <a href="{{ $actionUrl }}" style="color: #009846; text-decoration: underline;">{{ $actionUrl }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>

                    <!-- 3. PIED DE PAGE EXTÉRIEUR PLACÉ SOUS LA CARTE -->
                    <tr>
                        <td align="center" style="padding: 24px 16px 0 16px; font-size: 12px; color: #5f6b66; line-height: 1.6; text-align: center;">
                            <!-- RÉSEAUX SOCIAUX OIPI -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto 10px auto;">
                                <tr>
                                    <td align="center" style="padding: 0 8px;">
                                        <a href="https://www.facebook.com/oipiofficiel/?locale=fr_FR" target="_blank" rel="noopener" style="display: inline-block; text-decoration: none;">
                                            <img src="{{ isset($message) ? $message->embed(public_path('images/facebook-logo.png')) : asset('images/facebook-logo.png') }}"
                                                 alt="Facebook OIPI"
                                                 width="24"
                                                 height="24"
                                                 style="display: block; border: 0; outline: none; text-decoration: none; width: 24px; height: 24px;" />
                                        </a>
                                    </td>
                                    <td align="center" style="padding: 0 8px;">
                                        <a href="https://ci.linkedin.com/company/oipi-office-ivoirien-de-la-propri%C3%A9t%C3%A9-intellectuelle" target="_blank" rel="noopener" style="display: inline-block; text-decoration: none;">
                                            <img src="{{ isset($message) ? $message->embed(public_path('images/linkedin-logo.png')) : asset('images/linkedin-logo.png') }}"
                                                 alt="LinkedIn OIPI"
                                                 width="24"
                                                 height="24"
                                                 style="display: block; border: 0; outline: none; text-decoration: none; width: 24px; height: 24px;" />
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-bottom: 12px; font-size: 12px;">
                                <a href="https://www.facebook.com/oipiofficiel/?locale=fr_FR" target="_blank" rel="noopener" style="color: #5f6b66; text-decoration: underline; font-weight: 500;">Facebook</a>
                                <span style="color: #cbd5e1; margin: 0 6px;">·</span>
                                <a href="https://ci.linkedin.com/company/oipi-office-ivoirien-de-la-propri%C3%A9t%C3%A9-intellectuelle" target="_blank" rel="noopener" style="color: #5f6b66; text-decoration: underline; font-weight: 500;">LinkedIn</a>
                            </div>

                            <div style="font-weight: 600; color: #17352d; margin-bottom: 2px;">
                                Office Ivoirien de la Propriété Intellectuelle (OIPI)
                            </div>
                            <div>
                                Ce message a été généré automatiquement par le Registre Informatisé de Suivi des Formulaires Manquants (RISFM).
                            </div>
                            <div style="margin-top: 4px; color: #7a8b84;">
                                © {{ date('Y') }} OIPI — Tous droits réservés.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
