<div x-data="{ selectedCategory: @js(old('category', '')) }" x-show="showCreateModal" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6"
    @click.self="showCreateModal = false" @keydown.escape.window="showCreateModal = false">
    <div x-show="showCreateModal" x-transition
        class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Add New Vehicle
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Enter your vehicle information below.
                </p>
            </div>

            <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">
                &times;
            </button>
        </div>

        {{-- Modal Form --}}
        <form action="{{ route('api.vehicle.store') }}" method="POST" class="p-6">
            @csrf

            <x-commons.input name="brand" label="Vehicle Brand" placeholder="e.g. Toyota Avanza" :required="true" />



            {{-- Category --}}
            <div class="mb-5">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                    Vehicle Category
                </label>

                <select name="category" id="category" x-model="selectedCategory" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
                    <option value="">Select Category</option>

                    @foreach (\App\Enums\VehicleCategory::cases() as $category)
                        <option value="{{ $category->value }}">
                            {{ $category->value }}
                        </option>
                    @endforeach
                </select>

                @error('category')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            {{-- License Plate --}}
            <div x-show="selectedCategory !== '' && selectedCategory !== 'Bike'" x-cloak>
                <x-commons.input name="license_plate" label="License Plate" placeholder="e.g. B 1234 ABC"
                    :required="false" ::disabled="selectedCategory === 'Bike'" />
            </div>

            {{-- Battery Capacity --}}
            <div x-show="selectedCategory === 'Electric Car'" x-cloak>
                <x-commons.input name="total_kwh" label="Battery Capacity (kWh)" type="number" placeholder="e.g. 75"
                    min="0.01" step="0.01" ::required="selectedCategory === 'Electric Car'" ::disabled="selectedCategory !== 'Electric Car'" />
            </div>

            {{-- Modal Footer --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" @click="showCreateModal = false"
                    class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </button>

                <button type="submit"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                    Create Vehicle
                </button>
            </div>
        </form>
    </div>
</div>
