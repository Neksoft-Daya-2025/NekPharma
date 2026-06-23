@php
    $defaultAttributes = ['class' => 'input-group'];

    if ($id) {
        $defaultAttributes['id'] = $id;
    }
@endphp

<div {{ $attributes->merge($defaultAttributes) }}>
    @if ($prepend)
        <div class="input-group-prepend">
            {!! $prepend !!}
        </div>
    @endif

    {{ $slot }}

    @if ($preappend)
        <div class="input-group-append">
            {!! $preappend !!}
        </div>
    @endif

    @if ($append)
        <div class="input-group-append">
            {!! $append !!}
        </div>
    @endif
</div>
