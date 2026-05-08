@props(['title' => '', 'items' => []])
<div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
    <div class="my-auto mb-2">
        <h2 class="mb-1">{{ $title }}</h2>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}"><i class="ti ti-smart-home"></i></a>
                </li>
                @foreach($items as $item)
                    <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" {{ $loop->last ? 'aria-current="page"' : '' }}>
                        @if($loop->last)
                            {{ $item['label'] ?? $item }}
                        @else
                            <a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] ?? $item }}</a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
    {{ $slot ?? '' }}
</div>
