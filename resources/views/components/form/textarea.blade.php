@props([
    'label' => null,
    'name',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'rows' => 3,
])

@php
    $id = $attributes->get('id', $name);
    $val = old($name, $value);
@endphp

<div>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
            {{ $label }} @if($required) <span class="text-rose-600">*</span> @endif
        </label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($required) required @endif
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:border-indigo-500 focus:ring-indigo-500'
        ]) }}
    >{{ $val }}</textarea>

    @error($name)
        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
