@extends('errors::minimal')

@section('title', 'Service temporairement indisponible')
@section('code', '503')
@section('message', $exception->getMessage() ?: 'La plateforme est actuellement en maintenance planifiée. Veuillez réessayer dans quelques instants.')
