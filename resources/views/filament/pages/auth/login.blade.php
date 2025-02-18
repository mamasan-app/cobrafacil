@extends('filament::layouts.base') {{-- Usa un layout base de Filament --}}

@section('title', 'Iniciar sesión')

@section('content')
  <div class="flex min-h-screen items-center justify-center bg-gray-100">
    <div class="w-full max-w-md space-y-8 rounded-lg bg-white p-8 shadow-md">
      <h2 class="text-center text-2xl font-bold">Iniciar sesión</h2>

      @if (session('message'))
        <div class="mt-4 rounded bg-green-100 p-2 text-green-700">
          {{ session('message') }}
        </div>
      @endif

      <form
        wire:submit.prevent="submit"
        method="POST"
      >
        {{ $this->form }}

        <div class="mt-6">
          <button
            type="submit"
            class="w-full rounded bg-indigo-500 px-4 py-2 text-white"
          >
            Enviar Enlace
          </button>
        </div>
      </form>

      <div class="mt-4 text-center text-sm text-gray-600">
        Lea los términos y condiciones de uso <a
          href="https://mamapay.test"
          target="_blank"
          class="text-primary-600 hover:text-primary-500 underline"
        >aquí</a>
      </div>
    </div>
  </div>
@endsection
