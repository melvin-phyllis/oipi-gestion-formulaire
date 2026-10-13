@extends('errors::minimal')

@section('illustration')
    <i class="ti ti-file-search text-primary" style="font-size: 4rem;" aria-hidden="true"></i>
@endsection

@section('title', 'Page introuvable')
@section('code', '404')
@section('message', 'La page recherchée n’existe pas, a été déplacée ou n’est plus disponible.')
