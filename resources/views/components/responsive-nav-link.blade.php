@props(['active' => false])
<a {{ $attributes->merge(['class' => $active ? 'bg-gray-100 text-gray-900' : 'text-gray-600']) }}>{{ $slot }}</a>
