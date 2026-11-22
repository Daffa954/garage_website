@extends('layouts.app')

@section('title', 'Verifikasi Pendaftar Penyewa - Admin')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-green-700">Verifikasi Pendaftar Penyewa Baru</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Cek validasi NIK/KTP pendaftar baru, admin dapat menerima atau menolak/membatalkan pendaftaran.
                </p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <x-commons.card
                title="Total Pendaftar"
                :value="$totalUsers"
                subtitle="Akun penyewa terdaftar dalam sistem"
            />

            <x-commons.card
                title="Penyewa Terverifikasi"
                title-color="text-green-600"
                :value="$verifiedUsers"
                value-color="text-green-600"
                subtitle="Diizinkan menyewa garasi"
            />

            <x-commons.card
                title="Menunggu Verifikasi KTP"
                title-color="text-amber-600"
                :value="$unverifiedUsers"
                value-color="text-amber-600"
                subtitle="Menunggu persetujuan admin"
            />
        </div>

        {{-- User List Table --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Akun Penyewa</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Klik tombol aksi untuk menyetujui atau mencabut verifikasi pendaftar.
                </p>
            </div>

            @if ($users->isEmpty())
                <div class="py-16 px-6 text-center">
                    <p class="text-gray-500 text-sm">Tidak ada data pendaftar yang ditemukan.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <x-commons.datatable :headers="[
                        ['label' => '#'],
                        ['label' => 'Nama Lengkap'],
                        ['label' => 'Email (Username)'],
                        ['label' => 'NIK (No. KTP)'],
                        ['label' => 'No. Telepon'],
                        ['label' => 'Status Verifikasi'],
                        ['label' => 'Tanggal Mendaftar'],
                        ['label' => 'Aksi', 'class' => 'text-center'],
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

                                <td class="px-6 py-4 text-gray-600 font-mono text-xs font-semibold">
                                    {{ $user->nik ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $user->phone ?? '-' }}
                                </td>

                                <td class="px-6 py-4">
                                    @if ($user->is_verified)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Terverifikasi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Belum Diverifikasi
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
                                                Batalkan Verifikasi
                                            </button>
                                        @else
                                            <button type="button"
                                                onclick="confirmUserVerification(this.closest('form'), 'verify', '{{ addslashes($user->name) }}')"
                                                class="px-3.5 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition">
                                                Verifikasi
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
                    title: isUnverify ? 'Batalkan Verifikasi Pengguna?' : 'Verifikasi Pendaftar Baru?',
                    text: isUnverify
                        ? `Apakah Anda yakin ingin membatalkan status verifikasi ${userName}? Pengguna ini tidak akan dapat menyewa garasi.`
                        : `Apakah Anda yakin NIK/KTP ${userName} valid dan ingin memverifikasinya? Pengguna akan dapat menyewa garasi.`,
                    icon: isUnverify ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: isUnverify ? '#dc2626' : '#16a34a',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: isUnverify ? 'Ya, Batalkan' : 'Ya, Verifikasi',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm(isUnverify ? `Batalkan verifikasi untuk ${userName}?` : `Verifikasi ${userName}?`)) {
                    form.submit();
                }
            }
        }
    </script>
@endsection
