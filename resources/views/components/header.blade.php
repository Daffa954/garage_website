@php
    $role = session('user_role');
    $isAdmin = $role === 'admin';
    $isRenter = $role === 'user';
@endphp

<header class="bg-white border-b border-gray-200">
    <nav class="max-w-7xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">

        <a href="{{ route('garages.index') }}" class="text-xl font-bold text-green-700 tracking-tight">
            Sewa Garasi
        </a>

        <div class="hidden md:flex items-center gap-6 text-sm font-medium">
            <a href="{{ route('garages.index') }}" class="hover:text-green-600 transition">
                Daftar Garasi
            </a>

            @if ($isRenter)
                <a href="{{ route('vehicles.index') }}" class="hover:text-green-600 transition">
                    Kendaraan Saya
                </a>
            @endif

            @if ($isAdmin)
                <a href="{{ route('admin.users.index') }}" class="hover:text-green-600 transition">
                    Verifikasi Pendaftar
                </a>
            @endif

            @if ($isRenter)
                <a href="{{ route('rents.index') }}" class="hover:text-green-600 transition">
                    Sewa Saya
                </a>
            @else
                <a href="{{ route('rents.index') }}" class="hover:text-green-600 transition">
                    Laporan Persewaan
                </a>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600 font-medium">
                {{ session('user_name', 'Pengguna') }}
            </span>

            <form action="{{ route('api.auth.logout') }}" method="POST">
                @csrf

                <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-600 transition">
                    Keluar
                </button>
            </form>
        </div>

    </nav>
</header>
