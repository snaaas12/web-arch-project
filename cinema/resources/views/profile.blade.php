<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Мой профиль') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center mb-6">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="Avatar" class="w-20 h-20 rounded-full mr-4">
                    @else
                        <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h3>
                        <p class="text-gray-600">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">ID пользователя</p>
                            <p class="font-semibold">{{ $user->id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Дата регистрации</p>
                            <p class="font-semibold">{{ $user->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                        @if($user->github_id)
                            <div>
                                <p class="text-sm text-gray-500">GitHub ID</p>
                                <p class="font-semibold">{{ $user->github_id }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
