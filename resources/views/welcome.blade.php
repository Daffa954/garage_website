@extends('layouts.app')

@section('title', 'Home - LibraryApp')

@section('content')
    <div x-data="{ activeForm: 'login' }" class="md:w-[40%] w-full m-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-tight">
                Garage Rental Web
            </h1>

            <p class="text-gray-500 mt-2 text-sm">
                Manage your garage rental account
            </p>
        </div>

        <div class="bg-white border border-gray-200  rounded-2xl shadow-sm p-6 sm:p-8">

            {{-- Form Login --}}
            <section x-show="activeForm === 'login'" x-cloak>
                <h2 class="text-2xl font-semibold mb-2">Welcome back!</h2>

                <p class="text-sm text-gray-500 mb-6">
                    Login to access your account.
                </p>

                <form action="{{ route('api.auth.login') }}" method="POST">
                    @csrf

                    <x-commons.input name="email" label="Email" type="email" placeholder="example@email.com" />

                    <x-commons.input name="password" label="Password" type="password" placeholder="Enter your password" />

                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="remember"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Remember me
                        </label>


                    </div>

                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-lg transition">
                        Login
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Don't have an account?

                    <button type="button" @click="activeForm = 'register'"
                        class="text-indigo-600 hover:text-indigo-700 font-semibold">
                        Register
                    </button>
                </p>
            </section>

            {{-- Form Register --}}
            <section x-show="activeForm === 'register'" x-cloak>
                <h2 class="text-2xl font-semibold mb-2">Create account</h2>

                <p class="text-sm text-gray-500 mb-6">
                    Register to get started.
                </p>

                <form action="{{ route('api.auth.register') }}" method="POST">
                    @csrf

                    <x-commons.input name="name" label="Full Name" placeholder="Enter your full name" />

                    <x-commons.input name="email" label="Email" type="email" placeholder="example@email.com" />

                    <x-commons.input name="nik" label="NIK" placeholder="Enter your NIK" minLength="16" maxLength="16" />

                    <x-commons.input name="phone" label="Phone Number" type="tel"
                        placeholder="Enter your phone number" />

                    <x-commons.input name="password" label="Password" type="password" placeholder="Create a password" />

                    <x-commons.input name="password_confirmation" label="Confirm Password" type="password"
                        placeholder="Confirm your password" />

                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-lg transition">
                        Create Account
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Already have an account?

                    <button type="button" @click="activeForm = 'login'"
                        class="text-indigo-600 hover:text-indigo-700 font-semibold">
                        Login
                    </button>
                </p>
            </section>

        </div>
    </div>
@endsection
