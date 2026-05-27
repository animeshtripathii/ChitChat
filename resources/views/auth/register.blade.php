@extends('layouts.app')

@section('title', 'Register - ChitChat')

@section('content')
<div class="auth-card rounded-[24px] p-8 sm:p-10" x-data="registerForm()">
    
    <!-- Branding Header -->
    <div class="text-center mb-6">
        <h2 class="font-outfit text-2xl font-extrabold text-gray-900 tracking-tight">ChitChat</h2>
        <p class="text-xs font-semibold text-gray-500 mt-1">Create your account</p>
    </div>

    <!-- Backend Errors -->
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-3.5 rounded-xl mb-5">
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

    <!-- Registration Form -->
    <form action="{{ route('register') }}" method="POST" class="space-y-4.5" @submit="handleSubmit($event)">
        @csrf

        <!-- Full Name -->
        <div class="space-y-1">
            <label for="name" class="text-xs font-bold text-gray-700 tracking-wide block">Full Name</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                </div>
                <input type="text" name="name" id="name" required value="{{ old('name') }}"
                       placeholder="John Doe"
                       class="block w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl bg-gray-50/50 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200">
            </div>
        </div>

        <!-- Email or Phone Number -->
        <div class="space-y-1">
            <label for="email" class="text-xs font-bold text-gray-700 tracking-wide block">Email or Phone Number</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <span class="material-symbols-outlined text-[18px]">alternate_email</span>
                </div>
                <input type="text" name="email" id="email" required value="{{ old('email') }}"
                       placeholder="you@example.com"
                       @input.debounce.500ms="validateUnique('email')"
                       x-model="email"
                       class="block w-full pl-9 pr-10 py-2 border rounded-xl bg-gray-50/50 text-sm font-medium focus:ring-2 transition-all duration-200"
                       :class="{
                           'border-gray-300 focus:ring-emerald-500 focus:border-emerald-500': emailStatus === 'idle',
                           'border-green-500 focus:ring-green-500 focus:border-green-500 bg-green-50/10': emailStatus === 'available',
                           'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50/10': emailStatus === 'taken',
                           'border-yellow-500 focus:ring-yellow-500 focus:border-yellow-500': emailStatus === 'checking'
                       }">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span x-show="emailStatus === 'checking'" class="animate-spin h-4 w-4 text-yellow-500 material-symbols-outlined text-[16px]" x-cloak>sync</span>
                    <span x-show="emailStatus === 'available'" class="h-4 w-4 text-green-500 material-symbols-outlined text-[16px]" x-cloak>check_circle</span>
                    <span x-show="emailStatus === 'taken'" class="h-4 w-4 text-red-500 material-symbols-outlined text-[16px]" x-cloak>cancel</span>
                </div>
            </div>
            <!-- Sync phone with email field for simplified form submission -->
            <input type="hidden" name="phone" :value="email">
            <p x-show="emailStatus === 'taken'" class="text-[10px] font-bold text-red-600 mt-0.5" x-cloak>Already registered.</p>
        </div>

        <!-- Password -->
        <div class="space-y-1">
            <label for="password" class="text-xs font-bold text-gray-700 tracking-wide block">Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <span class="material-symbols-outlined text-[18px]">lock</span>
                </div>
                <input type="password" name="password" id="password" required x-model="password"
                       placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢"
                       class="block w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl bg-gray-50/50 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200">
            </div>
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1">
            <label for="password_confirmation" class="text-xs font-bold text-gray-700 tracking-wide block">Confirm Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <span class="material-symbols-outlined text-[18px]">lock</span>
                </div>
                <input type="password" name="password_confirmation" id="password_confirmation" required x-model="passwordConfirm"
                       placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢"
                       class="block w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl bg-gray-50/50 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200"
                       :class="{'border-red-500 focus:ring-red-500': passwordsMismatch}">
            </div>
        </div>
        <p x-show="passwordsMismatch" class="text-[10px] font-bold text-red-600" x-cloak>Passwords do not match.</p>

        <!-- Create Account Button -->
        <button type="submit"
                :disabled="isSubmitDisabled"
                class="w-full bg-[#10b981] hover:bg-[#059669] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 px-4 rounded-xl text-sm transition-all duration-200 shadow-md shadow-emerald-500/10 flex justify-center items-center gap-1.5 mt-6">
            <span>Create Account</span>
        </button>
    </form>

    <!-- Footer -->
    <div class="mt-6 pt-5 border-t border-gray-100 text-center">
        <p class="text-xs font-semibold text-gray-500">
            Already have an account? 
            <a href="{{ route('login') }}" class="text-emerald-600 hover:text-emerald-700 hover:underline font-bold ml-0.5">Login</a>
        </p>
    </div>
</div>

@push('scripts')
<script>
    function registerForm() {
        return {
            email: '{{ old('email') }}',
            password: '',
            passwordConfirm: '',
            emailStatus: 'idle', // idle, checking, available, taken

            get passwordsMismatch() {
                return this.passwordConfirm !== '' && this.password !== this.passwordConfirm;
            },

            get isSubmitDisabled() {
                return this.emailStatus === 'taken' || 
                       this.passwordsMismatch || 
                       this.password.length < 6 ||
                       this.email === '';
            },

            validateUnique(field) {
                const val = this[field];
                if (!val) {
                    this[field + 'Status'] = 'idle';
                    return;
                }

                this[field + 'Status'] = 'checking';

                // Determine query type (email or phone)
                const isEmail = val.includes('@');
                const fieldToCheck = isEmail ? 'email' : 'phone';

                fetch(`/api/check-unique?field=${fieldToCheck}&value=${encodeURIComponent(val)}`)
                    .then(res => res.json())
                    .then(data => {
                        this[field + 'Status'] = data.available ? 'available' : 'taken';
                    })
                    .catch(() => {
                        this[field + 'Status'] = 'idle';
                    });
            },

            handleSubmit(e) {
                if (this.isSubmitDisabled) {
                    e.preventDefault();
                }
            }
        };
    }
</script>
@endpush
@endsection

