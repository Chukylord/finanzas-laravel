@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'step' => null,
])

@php
    $inputId = $attributes->get('id', $name);
@endphp

<div>
    <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
        {{ $label }} @if($required) <span class="text-red-500">*</span> @endif
    </label>

    <div class="mt-1">
        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($step) step="{{ $step }}" @endif
            {{ $attributes->merge([
                'class' => 'block w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15'
            ]) }}
        />
    </div>

    @error($name)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
