@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'required' => true,
    'min' => null,
    'max' => null,
    'minlength' => null,
    'maxlength' => null,
])

<div class="mb-5">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
    </label>

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        min="{{ $min }}"
        max="{{ $max }}"
        @required($required)
        {{ $attributes->merge([
            'class' => 'w-full rounded-lg border border-gray-300
                        bg-white px-4 py-3 text-sm
                        text-gray-900 outline-none
                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20
                        transition',
        ]) }}
    >

    @error($name)
        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</div>