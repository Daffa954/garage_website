@extends('layouts.app')

@section('title', 'Manage Users - Admin Verification')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-green-700">User Management & Verification</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Review and manually verify user registrations.
                </p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <x-commons.card
                title="Total Users"
                :value="$totalUsers"
                subtitle="Registered renter accounts"
            />

            <x-commons.card
                title="Verified Users"
                title-color="text-emerald-600"
                :value="$verifiedUsers"
                value-color="text-emerald-600"
                subtitle="Approved to book garages"
            />

            <x-commons.card
                title="Pending Verification"
                title-color="text-amber-600"
                :value="$unverifiedUsers"
                value-color="text-amber-600"
                subtitle="Awaiting manual approval"
            />
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
                                    {{ $users->firstItem() + $loop->index }}
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
                                            <button type="button"
                                                onclick="confirmUserVerification(this.closest('form'), 'unverify', '{{ addslashes($user->name) }}')"
                                                class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                                                Unverify
                                            </button>
                                        @else
                                            <button type="button"
                                                onclick="confirmUserVerification(this.closest('form'), 'verify', '{{ addslashes($user->name) }}')"
                                                class="px-3.5 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition">
                                                Verify User
                                            </button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </x-commons.datatable>

                    @if ($users->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

    </div>

    <script>
        function confirmUserVerification(form, action, userName) {
            const isUnverify = action === 'unverify';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: isUnverify ? 'Revoke Verification?' : 'Verify User?',
                    text: isUnverify
                        ? `Are you sure you want to revoke verification for ${userName}? This user will not be able to rent garages.`
                        : `Are you sure you want to verify ${userName}? This will permit the user to rent garages.`,
                    icon: isUnverify ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: isUnverify ? '#dc2626' : '#059669',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: isUnverify ? 'Yes, Unverify' : 'Yes, Verify',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm(isUnverify ? `Revoke verification for ${userName}?` : `Verify user ${userName}?`)) {
                    form.submit();
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: "{{ session('success') }}",
                        timer: 3000,
                        showConfirmButton: false,
                    });
                }
            @endif

            @if (session('error'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: "{{ session('error') }}",
                    });
                }
            @endif
        });
    </script>
@endsection
