@props(['title'])

<x-layouts.app :title="$title">
    <div class="flex min-h-screen">
        @isset($sidebar)
            {{ $sidebar }}
        @endisset

        <div class="flex-1 flex flex-col min-w-0">
            @isset($header)
                {{ $header }}
            @else
                <header class="app-header">
                    <span class="company">{{ $title }}</span>
                </header>
            @endisset

            <main class="flex-1 p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
