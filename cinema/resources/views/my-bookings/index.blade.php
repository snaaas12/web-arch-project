<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Мои билеты') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if($bookings->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-gray-500">У вас пока нет билетов.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($bookings as $booking)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800">{{ $booking->session->movie->title }}</h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ $booking->session->start_time->format('d.m.Y H:i') }} | 
                                            Зал: {{ $booking->session->hall->name }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-indigo-600">{{ $booking->total_price }} ₽</p>
                                        <p class="text-xs text-gray-500">
                                            Статус: 
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
                                </div>

                                <div class="mb-4">
                                    <p class="text-sm text-gray-600 mb-2">Места:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($booking->seats as $seatId)
                                            @php
                                                $row = floor(($seatId - 1) / $booking->session->hall->seats_per_row) + 1;
                                                $seat = (($seatId - 1) % $booking->session->hall->seats_per_row) + 1;
                                            @endphp
                                            <span class="bg-indigo-100 text-indigo-800 text-sm font-semibold px-3 py-1 rounded">
                                                Ряд {{ $row }}, Место {{ $seat }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <a href="{{ route('my-bookings.show', $booking) }}" 
                                       class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                        Показать билет →
                                    </a>
                                    <span class="text-xs text-gray-500">
                                        Заказ #{{ $booking->id }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
