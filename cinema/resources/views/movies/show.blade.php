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
                    @if($movie->poster_url)
                        <div class="md:w-1/3">
                            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" 
                                 class="w-full h-full object-cover">
                        </div>
                    @endif
                    
                    <div class="p-6 {{ $movie->poster_url ? 'md:w-2/3' : 'w-full' }}">
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
                                <ul class="space-y-2">
                                    @foreach($movie->sessions as $session)
                                        <li class="bg-gray-50 p-3 rounded">
                                            <div class="font-semibold">
                                                {{ $session->start_time->format('d.m.Y H:i') }}
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                Зал: {{ $session->hall->name }} | 
                                                Формат: {{ $session->format }} | 
                                                Цена: {{ $session->base_price }} ₽
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
</x-app-layout>

