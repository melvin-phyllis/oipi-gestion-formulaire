@extends('errors::minimal')

@section('title', 'Accès refusé')
@section('code', '403')
@section('message', $exception->getMessage() ?: 'Vous ne disposez pas des privilèges nécessaires pour accéder à cette ressource.')
