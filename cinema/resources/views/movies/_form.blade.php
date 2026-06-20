<div class="space-y-4">
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700">Название *</label>
        <input type="text" name="title" id="title" value="{{ old('title', $movie->title ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
               required>
        @error('title')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Описание</label>
        <textarea name="description" id="description" rows="4"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $movie->description ?? '') }}</textarea>
        @error('description')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="duration" class="block text-sm font-medium text-gray-700">Длительность (мин) *</label>
            <input type="number" name="duration" id="duration" 
                   value="{{ old('duration', $movie->duration ?? '') }}" min="1" max="600"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                   required>
            @error('duration')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="genre" class="block text-sm font-medium text-gray-700">Жанр</label>
            <input type="text" name="genre" id="genre" 
                   value="{{ old('genre', $movie->genre ?? '') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('genre')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="rating" class="block text-sm font-medium text-gray-700">Рейтинг (0-10)</label>
            <input type="number" name="rating" id="rating" step="0.1" min="0" max="10"
                   value="{{ old('rating', $movie->rating ?? '') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('rating')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="age_restriction" class="block text-sm font-medium text-gray-700">Возрастное ограничение</label>
            <input type="number" name="age_restriction" id="age_restriction" min="0" max="21"
                   value="{{ old('age_restriction', $movie->age_restriction ?? '') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('age_restriction')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="poster_url" class="block text-sm font-medium text-gray-700">URL постера</label>
        <input type="url" name="poster_url" id="poster_url" 
               value="{{ old('poster_url', $movie->poster_url ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
               placeholder="https://example.com/poster.jpg">
        @error('poster_url')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
