<x-default-layout>   
    <x-slot:scripts>
        @vite(['resources/js/poll-dashboard.js'])
    </x-slot>
    <x-slot:title>
        Sondages
    </x-slot>

    {{-- Les props sont passées via window.__PROPS__ plutôt que data-props --}}
    {{-- car @json() dans un attribut HTML cause des problèmes d'échappement --}}
    <script>
        window.__PROPS__ = {
            polls: @json($polls),
            username: @json(auth()->user()->username),
        };
    </script>

    {{-- La div #app est le point de montage de l'app Vue --}}
    <div id="app"></div>
</x-default-layout>