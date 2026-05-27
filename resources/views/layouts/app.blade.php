<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ChitChat - Real-time Messenger')</title>
    
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    
    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        whatsapp: {
                            teal: '#128C7E',
                            lightTeal: '#25D366',
                            darkTeal: '#075E54',
                            chatBg: '#ECE5DD',
                            lightBg: '#F0F2F5',
                            darkBg: '#111b21',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Premium CSS & Animations -->
    <style>
        [x-cloak] { display: none !important; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }

        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }

        /* Clean solid card style */
        .auth-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05), 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        /* â”€â”€ @Mention chip styles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        /* Rendered inside message bubbles */
        .mention-chip {
            display: inline-block;
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
            font-weight: 700;
            font-size: 0.82em;
            padding: 1px 5px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
        }
        .mention-chip:hover {
            background: rgba(16, 185, 129, 0.22);
        }
        /* Self-mention (you were mentioned) - slightly brighter */
        .mention-chip.self {
            background: rgba(59, 130, 246, 0.12);
            color: #2563eb;
        }
        /* Ensure pre-wrap doesn't break inline chips */
        .mention-body {
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* â”€â”€ Toggle Switch Component â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        /* 
           Usage:  <button class="toggle-switch" :class="isOn ? 'on' : ''" @click="isOn = !isOn">
                     <span class="toggle-thumb"></span>
                   </button>
        */
        .toggle-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            width: 44px;
            height: 24px;
            border-radius: 9999px;
            background-color: #d1d5db; /* gray-300 */
            padding: 3px;
            cursor: pointer;
            border: none;
            outline: none;
            flex-shrink: 0;
            transition: background-color 0.2s ease;
        }
        .toggle-switch.on {
            background-color: #10b981; /* emerald-500 */
        }
        .toggle-switch .toggle-thumb {
            display: block;
            width: 18px;
            height: 18px;
            border-radius: 9999px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: transform 0.2s ease;
            transform: translateX(0);
            flex-shrink: 0;
        }
        .toggle-switch.on .toggle-thumb {
            transform: translateX(20px);
        }
        .toggle-switch:focus-visible {
            box-shadow: 0 0 0 3px rgba(16,185,129,0.35);
        }
    </style>
</head>
<body class="h-full antialiased flex flex-col justify-between">

    <!-- Main Content Area -->
    <main class="flex-grow flex items-center justify-center min-h-screen relative overflow-hidden py-12 px-4 sm:px-6 lg:px-8">
        
        <!-- Inner Content wrapper -->
        <div class="w-full max-w-[420px] z-10">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>
</html>

