    @php
        $role = session('user_role');
        $isAdmin = $role === 'admin';
        $isRenter = $role === 'user';
    @endphp

    <header class="bg-white border-b border-gray-200">
        <nav class="max-w-7xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">

            <a class="text-xl font-bold text-indigo-600">
                Garage Rental
            </a>

            <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition">
                    Dashboard
                </a>
                <a href="{{ route('garages.index') }}" class="hover:text-indigo-600 transition">
                    Garages
                </a>
                @if ($isRenter)
                    <a href="{{ route('vehicles.index') }}" class="hover:text-indigo-600 transition">
                        My Vehicles
                    </a>
                @endif

                @if ($isAdmin)
                    <a href="{{ route('admin.users.index') }}" class="hover:text-indigo-600 transition">
                        List Users
                    </a>
                @endif

                @if ($isRenter)
                    <a href="{{ route('rents.index') }}" class="hover:text-indigo-600 transition">
                        My Rental
                    </a>
                @else
                    <a href="{{ route('rents.index') }}" class="hover:text-indigo-600 transition">
                        List Rentals
                    </a>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">
                    {{ session('user_name', 'User') }}
                </span>

                <form action="{{ route('api.auth.logout') }}" method="POST">
                    @csrf

                    <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-600 transition">
                        Logout
                    </button>
                </form>
            </div>

        </nav>
    </header>
