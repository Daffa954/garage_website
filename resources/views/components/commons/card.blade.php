@props([
    'title' => null,
    'value' => null,
    'subtitle' => null,
    'icon' => null,
    'iconClass' => 'bg-green-50 text-green-600',
    'titleColor' => null,
    'valueColor' => null,
    'subtitleColor' => null,
    'bg' => null,
    'theme' => 'light',
])

@php
    $customClass = $attributes->get('class', '');
    $hasCustomBg = $bg || str_contains($customClass, 'bg-');
    $hasCustomBorder = str_contains($customClass, 'border-');
    $hasCustomText = str_contains($customClass, 'text-');

    $isDark = $theme === 'dark';

    // Apply defaults only if caller did NOT supply custom ones
    $bgClass = $bg ?? ($hasCustomBg ? '' : ($isDark ? 'bg-gradient-to-br from-gray-900 to-gray-800' : 'bg-white'));
    $borderClass = $hasCustomBorder ? '' : ($isDark ? 'border-gray-800' : 'border-gray-200');
    $textClass = $hasCustomText ? '' : ($isDark ? 'text-white' : 'text-gray-800');

    $defaultTitleColor = ($hasCustomText || $isDark) ? 'text-gray-200' : 'text-gray-500';
    $defaultValueColor = ($hasCustomText || $isDark) ? 'text-white' : 'text-gray-800';
    $defaultSubtitleColor = ($hasCustomText || $isDark) ? 'text-gray-200' : 'text-gray-400';
@endphp

<div {{ $attributes->merge([
    'class' => trim("border rounded-xl p-5 sm:p-6 shadow-sm {$bgClass} {$borderClass} {$textClass}")
]) }}>
    @if ($title || $icon || isset($header))
        <div class="flex items-center justify-between gap-2">
            @if (isset($header))
                {{ $header }}
            @elseif ($title)
                <p class="text-xs font-medium uppercase tracking-wider {{ $titleColor ?? $defaultTitleColor }}">
                    {{ $title }}
                </p>
            @endif

            @if (isset($iconSlot))
                {{ $iconSlot }}
            @elseif ($icon)
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ ($isDark || $hasCustomText) ? 'bg-white/10 text-white' : $iconClass }}">
                    {{ $icon }}
                </span>
            @endif
        </div>
    @endif

    @if (isset($valueSlot))
        <div class="mt-2">
            {{ $valueSlot }}
        </div>
    @elseif ($value !== null)
        <h3 class="text-2xl sm:text-3xl font-bold mt-2 {{ $valueColor ?? $defaultValueColor }}">
            {{ $value }}
        </h3>
    @endif

    @if ($subtitle || isset($subtitleSlot))
        <p class="text-xs mt-1 {{ $subtitleColor ?? $defaultSubtitleColor }}">
            {{ $subtitleSlot ?? $subtitle }}
        </p>
    @endif

    {{ $slot }}
</div>
