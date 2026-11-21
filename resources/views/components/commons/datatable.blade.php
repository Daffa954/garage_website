
@props([
    'headers' => [],
])

<div class="overflow-x-auto rounded-lg border border-gray-200">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-6 py-4 font-semibold {{ $header['class'] ?? '' }}">
                        {{ $header['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
            {{ $slot }}
        </tbody>
    </table>
</div>
