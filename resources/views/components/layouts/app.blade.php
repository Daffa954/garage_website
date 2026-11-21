<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @livewireStyles
</head>

<body class="min-h-screen flex flex-col bg-gray-50 text-gray-900">
    @if (session()->has('user_id'))
        <x-header />
    @endif

    <main class="flex-1 px-8 py-5">
        @yield('content')
    </main>
    @if (session()->has('user_id'))
        <x-footer />
    @endif

    {{-- Flash Message & Livewire SweetAlert Handler --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Swal === 'undefined') return;

            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: "{{ session('error') }}"
                });
            @endif

            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal!',
                    html: `{!! implode('<br>', $errors->all()) !!}`
                });
            @endif

            // Livewire SweetAlert Event Listener
            window.addEventListener('swal', (event) => {
                const data = Array.isArray(event.detail) ? event.detail[0] : (event.detail || {});
                Swal.fire({
                    icon: data.icon || 'info',
                    title: data.title || 'Pemberitahuan',
                    text: data.text || '',
                    timer: data.timer || (data.icon === 'success' ? 3000 : null),
                    showConfirmButton: data.showConfirmButton ?? (data.icon !== 'success')
                });
            });
        });
    </script>

</body>

</html>
