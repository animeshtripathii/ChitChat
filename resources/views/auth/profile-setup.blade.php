@extends('layouts.app')

@section('title', 'Profile Onboarding - ChitChat')

@section('content')
<div class="glass rounded-3xl shadow-2xl overflow-hidden border border-white/50 p-8 sm:p-10 animate-fade-in-up"
     x-data="profileSetupForm()">
    
    <!-- Header/Branding -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-whatsapp-teal/10 text-whatsapp-teal mb-3">
            <span class="material-symbols-outlined text-[40px]">account_box</span>
        </div>
        <h2 class="font-outfit text-3xl font-extrabold text-gray-900 tracking-tight">Profile Setup</h2>
        <p class="text-xs text-gray-500 font-medium tracking-wide uppercase mt-1">Customize your messenger identity</p>
    </div>

    <!-- Backend Errors -->
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <span class="material-symbols-outlined text-red-500">error</span>
                </div>
                <div class="ml-3">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm font-semibold text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Profile Form -->
    <form action="{{ route('profile.setup') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Interactive Avatar Upload -->
        <div class="flex flex-col items-center justify-center space-y-3">
            <div class="relative w-32 h-32 rounded-full overflow-hidden border-4 border-whatsapp-teal/20 group hover:border-whatsapp-teal transition-all duration-300 shadow-md">
                <img :src="avatarPreview" alt="Avatar Preview" class="w-full h-full object-cover">
                <label for="avatar" class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity duration-200">
                    <span class="material-symbols-outlined text-[28px]">photo_camera</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider mt-1">Upload Photo</span>
                </label>
                <input type="file" name="avatar" id="avatar" accept="image/*" class="hidden" @change="handleFileChange($event)">
            </div>
            
            <div class="text-center">
                <p class="text-xs font-bold text-gray-700">Display Picture</p>
                <p class="text-[10px] text-gray-400 mt-0.5">JPG, PNG, or GIF. Max 5MB.</p>
                <p x-show="errorMessage" class="text-[11px] font-bold text-red-600 mt-1" x-cloak x-text="errorMessage"></p>
            </div>
        </div>

        <!-- Display Name -->
        <div class="space-y-1">
            <label for="name" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Display Name</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <span class="material-symbols-outlined text-[20px]">person</span>
                </div>
                <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}"
                       placeholder="Enter your name"
                       class="block w-full pl-11 pr-4 py-3 border border-gray-300 rounded-2xl bg-white/50 text-sm font-medium focus:ring-2 focus:ring-whatsapp-teal focus:border-whatsapp-teal transition-all duration-200">
            </div>
        </div>

        <!-- Status / About Message -->
        <div class="space-y-1">
            <label for="status_message" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Status Message</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <span class="material-symbols-outlined text-[20px]">chat</span>
                </div>
                <input type="text" name="status_message" id="status_message" value="{{ old('status_message', $user->status_message ?? 'Available') }}"
                       placeholder="e.g. Busy, At work, Sleeping"
                       class="block w-full pl-11 pr-4 py-3 border border-gray-300 rounded-2xl bg-white/50 text-sm font-medium focus:ring-2 focus:ring-whatsapp-teal focus:border-whatsapp-teal transition-all duration-200">
            </div>
            
            <!-- Quick Status Presets -->
            <div class="flex flex-wrap gap-1.5 mt-2">
                <template x-for="preset in presets" :key="preset">
                    <button type="button" @click="setStatus(preset)"
                            class="text-[10px] font-bold bg-gray-150 hover:bg-gray-200 text-gray-700 px-2.5 py-1 rounded-full border border-gray-300/40 transition-colors">
                        <span x-text="preset"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="errorMessage !== ''"
                class="w-full bg-whatsapp-teal hover:bg-whatsapp-darkTeal disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3.5 px-4 rounded-2xl text-sm transition-all duration-200 shadow-lg shadow-whatsapp-teal/20 flex justify-center items-center gap-2 hover:scale-[1.01] active:scale-[0.99]">
            <span class="material-symbols-outlined text-[20px]">done_all</span>
            <span>Finish Onboarding</span>
        </button>
    </form>
</div>

@push('scripts')
<script>
    function profileSetupForm() {
        return {
            avatarPreview: '{{ $user->avatar ?? "https://api.dicebear.com/7.x/pixel-art/svg?seed=" . urlencode($user->email ?? $user->id) }}',
            errorMessage: '',
            presets: ['Available', 'Busy', 'At school', 'At the movies', 'At work', 'Battery about to die', 'In a meeting', 'At the gym', 'Urgent calls only'],

            setStatus(preset) {
                document.getElementById('status_message').value = preset;
            },

            handleFileChange(e) {
                const file = e.target.files[0];
                if (!file) return;

                // Validate image size (5MB = 5 * 1024 * 1024 bytes)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    this.errorMessage = 'File size exceeds 5MB limit.';
                    return;
                }

                this.errorMessage = '';

                // Create local object URL for instant preview
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.avatarPreview = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        };
    }
</script>
@endpush
@endsection

