<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Wholesale Outreach') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @livewireScripts
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <div class="min-h-screen flex">
            {{-- Brand Panel --}}
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800">
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice">
                        <defs>
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                            </pattern>
                        </defs>
                        <rect width="100" height="100" fill="url(#grid)"/>
                    </svg>
                </div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-white opacity-5"></div>
                <div class="absolute -top-16 -left-16 w-72 h-72 rounded-full bg-white opacity-5"></div>

                <div class="relative z-10 flex flex-col justify-between p-12 text-white w-full">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold tracking-tight">Wholesale Outreach</span>
                    </div>

                    <div class="space-y-6">
                        <h1 class="text-4xl font-bold leading-tight">
                            Automate your vendor<br>outreach at scale
                        </h1>
                        <p class="text-lg text-indigo-100 max-w-md">
                            AI-powered email generation, smart follow-ups, and comprehensive campaign management — all in one platform.
                        </p>
                        <div class="flex flex-wrap gap-4 pt-2">
                            <div class="flex items-center gap-2 text-indigo-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm">AI Email Generation</span>
                            </div>
                            <div class="flex items-center gap-2 text-indigo-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm">Smart Follow-ups</span>
                            </div>
                            <div class="flex items-center gap-2 text-indigo-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm">Campaign Analytics</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-sm text-indigo-200">&copy; {{ date('Y') }} Wholesale Outreach Platform</p>
                </div>
            </div>

            {{-- Form Panel --}}
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12">
                <div class="w-full max-w-md">
                    {{-- Mobile Logo --}}
                    <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-gray-800 dark:text-gray-100">Wholesale Outreach</span>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
