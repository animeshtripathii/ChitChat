@extends('layouts.app')

@section('title', 'Login - ChitChat')

@section('content')
<div class="auth-card rounded-[24px] p-8 sm:p-10" x-data="{ showPassword: false }">
    
    <!-- Branding Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-whatsapp-teal/10 text-whatsapp-teal mb-3">
            <!-- Circular ChitChat Icon -->
            <span class="material-symbols-outlined text-[32px] text-emerald-600">forum</span>
        </div>
        <h2 class="font-outfit text-2xl font-extrabold text-gray-900 tracking-tight">ChitChat</h2>
        <p class="text-xs font-semibold text-gray-500 mt-1">Log in to continue your conversations securely.</p>
    </div>

    <!-- Errors -->
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-3.5 rounded-xl mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <span class="material-symbols-outlined text-red-500 text-[20px]">error</span>
                </div>
                <div class="ml-2.5">
                    @foreach ($errors->all() as $error)
                        <p class="text-xs font-semibold text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Login Form -->
    <form action="{{ route('login') }}" method="POST" class="space-y-5">
        @csrf

        <!-- Email or Phone -->
        <div class="space-y-1">
            <label for="login" class="text-xs font-bold text-gray-700 tracking-wide block">Email or Phone</label>
            <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                   placeholder="Enter your email or phone"
                   class="block w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-gray-50/50 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200">
        </div>

        <!-- Password -->
        <div class="space-y-1">
            <div class="flex justify-between items-center">
                <label for="password" class="text-xs font-bold text-gray-700 tracking-wide block">Password</label>
                <a href="#" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline">Forgot Password?</a>
            </div>
            <div class="relative">
                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required
                       placeholder="Enter your password"
                       class="block w-full pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl bg-gray-50/50 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200">
                <button type="button" @click="showPassword = !showPassword" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined text-[18px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                </button>
            </div>
        </div>

        <!-- Login Button -->
        <button type="submit"
                class="w-full bg-[#10b981] hover:bg-[#059669] text-white font-bold py-3 px-4 rounded-xl text-sm transition-all duration-200 shadow-md shadow-emerald-500/10 flex justify-center items-center gap-1.5 hover:scale-[1.01] active:scale-[0.99] mt-6">
            <span>Login</span>
            <span class="material-symbols-outlined text-[16px]">logout</span>
        </button>
    </form>

    <!-- Footer -->
    <div class="mt-8 pt-5 border-t border-gray-100 text-center">
        <p class="text-xs font-semibold text-gray-500">
            Don't have an account? 
            <a href="{{ route('register') }}" class="text-emerald-600 hover:text-emerald-700 hover:underline font-bold ml-0.5">Sign up</a>
        </p>
    </div>
</div>
@endsection

