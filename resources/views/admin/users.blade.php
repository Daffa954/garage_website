@extends('layouts.app')

@section('title', 'Manage Users - Admin Verification')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">User Management & Verification</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Review and manually verify user registrations.
                </p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <h2 class="text-3xl font-bold text-gray-800 mt-2">{{ $users->count() }}</h2>
                <p class="text-xs text-gray-400 mt-1">Registered renter accounts</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm font-medium text-emerald-600">Verified Users</p>
                <h2 class="text-3xl font-bold text-emerald-600 mt-2">{{ $users->where('is_verified', true)->count() }}</h2>
                <p class="text-xs text-gray-400 mt-1">Approved to book garages</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm font-medium text-amber-600">Pending Verification</p>
                <h2 class="text-3xl font-bold text-amber-600 mt-2">{{ $users->where('is_verified', false)->count() }}</h2>
                <p class="text-xs text-gray-400 mt-1">Awaiting manual approval</p>
            </div>
        </div>

        {{-- User List Table --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Registered Users</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Click the action button to toggle user verification status.
                </p>
            </div>

            @if ($users->isEmpty())
                <div class="py-16 px-6 text-center">
                    <p class="text-gray-500 text-sm">No registered users found.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <x-commons.datatable :headers="[
                        ['label' => '#'],
                        ['label' => 'Name'],
                        ['label' => 'Email'],
                        ['label' => 'NIK'],
                        ['label' => 'Phone'],
                        ['label' => 'Status'],
                        ['label' => 'Registered At'],
                        ['label' => 'Action', 'class' => 'text-center'],
                    ]">
                        @foreach ($users as $user)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $user->name }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $user->email }}
                                </td>

                                <td class="px-6 py-4 text-gray-600 font-mono text-xs">
                                    {{ $user->nik ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $user->phone ?? '-' }}
                                </td>

                                <td class="px-6 py-4">
                                    @if ($user->is_verified)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Verified
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Unverified
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-gray-500 text-xs">
                                    {{ $user->created_at ? $user->created_at->format('d M Y, H:i') : '-' }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('api.user.verify', $user->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')

                                        @if ($user->is_verified)
                                            <button type="submit"
                                                onclick="return confirm('Revoke verification for {{ $user->name }}?')"
                                                class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                                                Unverify
                                            </button>
                                        @else
                                            <button type="submit"
                                                class="px-3.5 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition">
                                                Verify User
                                            </button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </x-commons.datatable>
                </div>
            @endif
        </div>

    </div>
@endsection
