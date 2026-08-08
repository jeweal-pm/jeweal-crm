@props(['active' => false])
<a {{ $attributes->merge(['class' => $active ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500']) }}>{{ $slot }}</a>
