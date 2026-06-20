<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Добавить фильм') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('movies.store') }}" method="POST">
                    @csrf
                    @include('movies._form')
                    
                    <div class="flex justify-end space-x-2 mt-6">
                        <a href="{{ route('movies.index') }}" 
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Отмена
                        </a>
                        <button type="submit" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                            Создать фильм
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
