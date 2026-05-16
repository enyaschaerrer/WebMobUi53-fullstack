<x-default-layout>
    <x-slot:scripts>
        @vite(['resources/js/poll-vote.js'])
    </x-slot>
    
    <x-slot:title>
        Sondage
    </x-slot>

    <!-- window.__PROPS__ évite les problèmes d'échappement JSON -->
    <!-- script ici car doit être défini après que le DOM est chargé mais avant que Vue monte l'app. -->
    <script>
        window.__PROPS__ = {
            token: @json($token),
            loginUrl: @json(route('login')),
            isAuthenticated: @json(auth()->check()),
        };
    </script>

    <div id="app"></div>
</x-default-layout>