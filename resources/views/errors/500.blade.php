@extends('errors::minimal')

@section('title', 'Erreur interne du serveur')
@section('code', '500')
@section('message', 'Une anomalie inattendue s’est produite. L’incident a été journalisé et l’équipe technique en a été informée.')
