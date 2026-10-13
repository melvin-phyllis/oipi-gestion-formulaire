@extends('emails.layouts.oipi', [
    'title' => 'Création du compte administrateur RISFM',
])

@section('content')
    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #17352d; line-height: 1.3;">
        Votre compte administrateur RISFM est prêt
    </h1>

    <p style="margin: 0 0 16px 0; font-size: 15px; color: #17352d; line-height: 1.6;">
        Un compte administrateur initial vient d’être créé pour l’application RISFM.
    </p>

    <p style="margin: 0 0 8px 0; font-size: 14px; color: #5f6b66; line-height: 1.6;">
        Identifiant : <strong>{{ $username }}</strong>
    </p>
    <p style="margin: 0 0 20px 0; font-size: 14px; color: #5f6b66; line-height: 1.6;">
        Mot de passe temporaire : <strong>{{ $temporaryPassword }}</strong>
    </p>

    <p style="margin: 0; font-size: 14px; color: #5f6b66; line-height: 1.6;">
        Ce mot de passe ne peut être utilisé que pour la première connexion : RISFM vous demandera de le remplacer immédiatement. Ne transférez pas cet e-mail et supprimez-le après avoir changé le mot de passe.
    </p>
@endsection
