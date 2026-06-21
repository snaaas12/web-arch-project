<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Мои фильмы') }}
            </h2>
            <a href="{{ route('movies.create') }}" 
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                + Добавить фильм
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($movies->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-gray-500 mb-4">У вас пока нет добавленных фильмов.</p>
                    <a href="{{ route('movies.create') }}" class="text-indigo-600 hover:underline">
                        Добавить первый фильм
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($movies as $movie)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition">
                            <div class="w-full h-64 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 flex items-center justify-center relative overflow-hidden">
                                <div class="absolute inset-0 opacity-20">
                                    <div class="absolute top-0 left-0 w-32 h-32 bg-white rounded-full -translate-x-16 -translate-y-16"></div>
                                    <div class="absolute bottom-0 right-0 w-40 h-40 bg-white rounded-full translate-x-20 translate-y-20"></div>
                                </div>
                                <div class="text-center z-10 p-4">
                                    <svg class="w-24 h-24 text-white mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path>
                                    </svg>
                                    <div class="text-white font-bold text-lg drop-shadow-lg">{{ $movie->title }}</div>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-bold text-lg mb-2">{{ $movie->title }}</h3>
                                <div class="flex items-center text-sm text-gray-600 mb-2">
                                    <span class="mr-3">⏱ {{ $movie->duration }} мин</span>
                                    @if($movie->genre)
                                        <span class="mr-3">🎭 {{ $movie->genre }}</span>
                                    @endif
                                    @if($movie->rating)
                                        <span>⭐ {{ number_format($movie->rating, 1) }}</span>
                                    @endif
                                </div>
                                
                                <div class="mt-4 flex justify-between items-center">
                                    <a href="{{ route('movies.show', $movie) }}" 
                                       class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                        Подробнее →
                                    </a>
                                    <div class="flex gap-2">
                                        <a href="{{ route('movies.edit', $movie) }}" 
                                           class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-bold py-1 px-3 rounded">
                                            Изменить
                                        </a>
                                        <form action="{{ route('my-movies.destroy', $movie) }}" method="POST" 
                                              onsubmit="return confirm('Вы уверены, что хотите удалить этот фильм?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-1 px-3 rounded">
                                                Удалить
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $movies->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
