@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        @if(session('success'))
            <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        @endif
        <div class="d-lg-flex d-md-flex d-block justify-content-between align-items-center mb-3">
            <h4 class="mb-0">{{ $pageTitle ?? 'Merge duplicate chemists' }}</h4>
            <a href="{{ route('chemists.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left mr-1"></i> @lang('app.back') to chemists
            </a>
        </div>

        <div class="alert alert-info">
            <strong>How duplicates are detected</strong>
            <ul class="mb-0 pl-3">
                <li>Same mobile number (last 10 digits), or</li>
                <li>Same shop name + same headquarter when mobile is missing or too short.</li>
            </ul>
            <p class="mb-0 mt-2">
                For each group, the record with the <strong>most filled fields</strong> is kept (ties: lower ID wins).
                Empty fields on the kept record are filled from the other records. DCR references are moved onto the kept chemist; duplicate rows are removed.
            </p>
        </div>

        @if(!isset($duplicateGroups) || $duplicateGroups->isEmpty())
            <div class="w-tables rounded bg-white p-4 text-center text-muted">
                @lang('No duplicate groups found') for your access scope.
            </div>
        @else
            <div class="w-tables rounded bg-white p-3 mb-3">
                <p class="text-dark-grey mb-2">
                    <strong>{{ $duplicateGroups->count() }}</strong> duplicate group(s) will be merged.
                </p>

                @foreach($duplicateGroups as $idx => $group)
                    <div class="border rounded mb-3 p-3">
                        <div class="mb-2">
                            <span class="badge badge-success">Keep</span>
                            <strong>{{ $group['winner']->shopname }}</strong>
                            @if($group['winner']->fullname)
                                <span class="text-muted">— {{ $group['winner']->fullname }}</span>
                            @endif
                            <span class="text-muted">(ID {{ $group['winner']->id }}, completeness {{ $group['winner_score'] }})</span>
                        </div>
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Shop</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Headquarter</th>
                                    <th>Score</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group['chemists'] as $c)
                                    <tr class="{{ $c->id === $group['winner']->id ? 'table-success' : '' }}">
                                        <td>{{ $c->id }}</td>
                                        <td>{{ $c->shopname ?? '—' }}</td>
                                        <td>{{ $c->fullname ?? '—' }}</td>
                                        <td>{{ $c->mobile ?? '—' }}</td>
                                        <td>{{ optional($c->headquarter)->name ?? '—' }}</td>
                                        <td>{{ $group['scores'][$c->id] ?? 0 }}</td>
                                        <td>
                                            @if($c->id === $group['winner']->id)
                                                <span class="text-success">Kept</span>
                                            @else
                                                <span class="text-danger">Merged into {{ $group['winner']->id }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>

            <div class="card border-danger">
                <div class="card-body">
                    <form method="POST" action="{{ route('chemists.merge-duplicates.run') }}" id="merge-duplicates-form">
                        @csrf
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_merge" id="confirm_merge" value="1" required>
                            <label class="form-check-label" for="confirm_merge">
                                I understand that duplicate chemist records will be <strong>soft-deleted</strong> and merged into the best-filled record above.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-compress mr-1"></i> Merge all duplicate groups
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
