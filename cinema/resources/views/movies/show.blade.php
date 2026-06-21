<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $movie->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="md:flex">
                    {{-- Красивый градиент вместо постера --}}
                    <div class="md:w-1/3 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 flex items-center justify-center min-h-[400px] relative overflow-hidden">
                        <div class="absolute inset-0 opacity-20">
                            <div class="absolute top-0 left-0 w-40 h-40 bg-white rounded-full -translate-x-20 -translate-y-20"></div>
                            <div class="absolute bottom-0 right-0 w-52 h-52 bg-white rounded-full translate-x-26 translate-y-26"></div>
                        </div>
                        <div class="text-center z-10 p-8">
                            <svg class="w-32 h-32 text-white mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path>
                            </svg>
                            <div class="text-white font-bold text-2xl drop-shadow-lg">{{ $movie->title }}</div>
                        </div>
                    </div>
                    
                    <div class="p-6 md:w-2/3">
                        <div class="flex items-center mb-4">
                            @if($movie->rating)
                                <span class="text-2xl font-bold text-yellow-500 mr-2">
                                    ⭐ {{ number_format($movie->rating, 1) }}
                                </span>
                            @endif
                            @if($movie->age_restriction)
                                <span class="bg-red-100 text-red-800 text-sm px-2 py-1 rounded">
                                    {{ $movie->age_restriction }}+
                                </span>
                            @endif
                        </div>

                        <div class="mb-4 text-sm text-gray-600">
                            <span class="mr-4">⏱ {{ $movie->duration }} мин</span>
                            @if($movie->genre)
                                <span>🎭 {{ $movie->genre }}</span>
                            @endif
                        </div>

                        @if($movie->description)
                            <div class="mb-6">
                                <h3 class="font-semibold mb-2">Описание:</h3>
                                <p class="text-gray-700">{{ $movie->description }}</p>
                            </div>
                        @endif

                        <div class="mb-6 text-sm text-gray-500">
                            Добавил: <strong>{{ $movie->user->name ?? 'Неизвестно' }}</strong>
                            <br>
                            Дата создания: {{ $movie->created_at->format('d.m.Y H:i') }}
                        </div>

                        @if($movie->sessions->isNotEmpty())
                            <div class="mb-6">
                                <h3 class="font-semibold mb-2">Сеансы:</h3>
                                <ul class="space-y-4">
                                    @foreach($movie->sessions as $session)
                                        <li class="bg-gray-50 p-4 rounded">
                                            <div class="font-semibold">
                                                {{ $session->start_time->format('d.m.Y H:i') }}
                                            </div>
                                            <div class="text-sm text-gray-600 mb-3">
                                                Зал: {{ $session->hall->name }} | 
                                                Формат: {{ $session->format }} | 
                                                Цена: {{ $session->base_price }} ₽
                                            </div>
                                            
                                            {{-- React-компонент для схемы зала --}}
                                            <div id="seat-map-{{ $session->id }}" 
                                                data-session-id="{{ $session->id }}" 
                                                class="mt-3">
                                                <div class="text-center text-gray-500 py-8">
                                                    Загрузка схемы зала...
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex justify-end space-x-2">
                            @if($movie->user_id === auth()->id())
                                <a href="{{ route('movies.edit', $movie) }}" 
                                   class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                                    Редактировать
                                </a>
                                <form action="{{ route('movies.destroy', $movie) }}" method="POST" 
                                      onsubmit="return confirm('Удалить фильм?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                                        Удалить
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('movies.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Назад к списку
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @vite(['resources/js/app.jsx'])
</x-app-layout>