<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Билет #' . $booking->id) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    {{-- Заголовок билета --}}
                    <div class="text-center mb-8 pb-6 border-b-2 border-dashed border-gray-300">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $booking->session->movie->title }}</h1>
                        <p class="text-gray-600">
                            {{ $booking->session->start_time->format('d.m.Y H:i') }}
                        </p>
                    </div>

                    {{-- Информация о сеансе --}}
                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Зал</p>
                            <p class="text-lg font-semibold">{{ $booking->session->hall->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Формат</p>
                            <p class="text-lg font-semibold">{{ $booking->session->format }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Дата и время</p>
                            <p class="text-lg font-semibold">{{ $booking->session->start_time->format('d.m.Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Общая стоимость</p>
                            <p class="text-lg font-bold text-indigo-600">{{ $booking->total_price }} ₽</p>
                        </div>
                    </div>

                    {{-- Места --}}
                    <div class="mb-8">
                        <p class="text-sm text-gray-500 mb-3">Ваши места:</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($booking->seats as $seatId)
                                @php
                                    $row = floor(($seatId - 1) / $booking->session->hall->seats_per_row) + 1;
                                    $seat = (($seatId - 1) % $booking->session->hall->seats_per_row) + 1;
                                @endphp
                                <div class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold">
                                    Ряд {{ $row }}, Место {{ $seat }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- QR-код --}}
                    @if($booking->qr_code)
                        <div class="text-center mb-8">
                            <p class="text-sm text-gray-500 mb-3">QR-код для входа в зал:</p>
                            <img src="{{ url('storage/' . $booking->qr_code) }}" alt="QR Code" 
                                 class="mx-auto w-64 h-64 border-4 border-gray-200 rounded-lg">
                        </div>
                    @endif

                    {{-- Информация о заказе --}}
                    <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-600">
                        <p><strong>Номер заказа:</strong> #{{ $booking->id }}</p>
                        <p><strong>Дата заказа:</strong> {{ $booking->created_at->format('d.m.Y H:i') }}</p>
                        <p><strong>Статус:</strong> 
                            <span class="font-semibold 
                                @if($booking->status === 'confirmed') text-green-600
                                @elseif($booking->status === 'pending') text-yellow-600
                                @else text-red-600
                                @endif
                            ">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </p>
                    </div>

                    {{-- Кнопки действий --}}
                    <div class="mt-8 flex justify-between">
                        <a href="{{ route('my-bookings.index') }}" 
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            ← Назад к билетам
                        </a>
                        @if($booking->qr_code)
                            <a href="{{ url('storage/' . $booking->qr_code) }}" download 
                               class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Скачать QR-код
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
