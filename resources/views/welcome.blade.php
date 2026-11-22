@extends('layouts.app')

@section('title', 'Beranda - Sewa Garasi')

@section('content')
    <div x-data="{ activeForm: 'login' }" class="md:w-[40%] w-full m-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                Sistem Penyewaan Garasi
            </h1>

            <p class="text-gray-500 mt-2 text-sm">
                Kelola akun penyewaan garasi kendaraan Anda
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-8">

            {{-- Form Login --}}
            <section x-show="activeForm === 'login'" x-cloak>
                <h2 class="text-2xl font-semibold mb-2 text-gray-800">Selamat Datang!</h2>

                <p class="text-sm text-gray-500 mb-6">
                    Masuk untuk mengakses akun Anda.
                </p>

                <form action="{{ route('api.auth.login') }}" method="POST">
                    @csrf

                    <x-commons.input name="email" label="Email (Username)" type="email" placeholder="contoh@email.com" />

                    <x-commons.input name="password" label="Kata Sandi" type="password" placeholder="Masukkan kata sandi Anda" />

                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" name="remember"
                                class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 rounded-lg transition shadow-sm">
                        Masuk
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Belum memiliki akun?

                    <button type="button" @click="activeForm = 'register'"
                        class="text-green-600 hover:text-green-700 font-semibold">
                        Daftar sebagai Penyewa
                    </button>
                </p>
            </section>

            {{-- Form Register --}}
            <section x-show="activeForm === 'register'" x-cloak>
                <h2 class="text-2xl font-semibold mb-2 text-gray-800">Daftar Akun Baru</h2>

                <p class="text-sm text-gray-500 mb-6">
                    Lengkapi data pendaftaran sebagai penyewa garasi.
                </p>

                <form action="{{ route('api.auth.register') }}" method="POST">
                    @csrf

                    <x-commons.input name="name" label="Nama Lengkap" placeholder="Masukkan nama lengkap sesuai KTP" />

                    <x-commons.input name="email" label="Email (sebagai Username)" type="email" placeholder="contoh@email.com" />

                    <x-commons.input name="nik" label="NIK (Nomor KTP)" placeholder="Masukkan 16 digit NIK" minLength="16" maxLength="16" />

                    <x-commons.input name="phone" label="Nomor Telepon" type="tel"
                        placeholder="Contoh: 08123456789" />

                    <x-commons.input name="password" label="Kata Sandi" type="password" placeholder="Buat kata sandi minimal 8 karakter" />

                    <x-commons.input name="password_confirmation" label="Konfirmasi Kata Sandi" type="password"
                        placeholder="Ulangi kata sandi Anda" />

                    <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 rounded-lg transition shadow-sm">
                        Daftar Sekarang
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Sudah memiliki akun?

                    <button type="button" @click="activeForm = 'login'"
                        class="text-green-600 hover:text-green-700 font-semibold">
                        Masuk
                    </button>
                </p>
            </section>

        </div>
    </div>
@endsection
