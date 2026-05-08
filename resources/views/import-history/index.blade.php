@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <x-table class="table-hover border-0 w-100" headType="thead-light" id="import-history-table">
                <x-slot name="thead">
                    <th>#</th>
                    <th>Module</th>
                    <th>File</th>
                    <th>Records</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-right pr-20">@lang('app.action')</th>
                </x-slot>

                @forelse($imports as $key => $import)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $import->module }}</td>
                        <td>{{ $import->filename }}</td>
                        <td>
                            @if($import->records_count !== null)
                                {{ $import->records_count }} {{ $import->module === 'DoctorImport' ? 'doctors' : 'records' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                             <div class="d-flex align-items-center">
                                @if($import->user)
                                    @if($import->user->image)
                                        <img src="{{ $import->user->image_url }}" class="mr-2 taskEmployeeImg rounded" alt="{{ $import->user->name }}">
                                    @else
                                        <div class="mr-2 taskEmployeeImg rounded bg-primary text-white d-flex align-items-center justify-content-center">
                                            {{ mb_substr($import->user->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <span>{{ $import->user->name }}</span>
                                @else
                                    -
                                @endif
                            </div>
                        </td>
                        <td>{{ $import->created_at->format('d-m-Y H:i A') }}</td>
                        <td>
                            @if($import->status == 'completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($import->status == 'processing')
                                <span class="badge badge-warning">Processing</span>
                            @else
                                <span class="badge badge-danger">Failed</span>
                            @endif
                        </td>
                        <td class="text-right pr-20">
                            @if(!empty($import->filepath))
                                <a href="{{ route('import-history.download', $import->id) }}" class="btn btn-secondary btn-sm">
                                    <i class="fa fa-download"></i> @lang('app.download')
                                </a>
                            @else
                                <span class="text-muted" title="{{ __('File not available') }}">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            <x-cards.no-record icon="file" :message="__('No import history found')" />
                        </td>
                    </tr>
                @endforelse
            </x-table>
            
            <!-- Pagination -->
            @if($imports->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3">
                    <div class="text-muted">
                        Showing {{ $imports->firstItem() }} to {{ $imports->lastItem() }} of {{ $imports->total() }} entries
                    </div>
                    <div>
                        {{ $imports->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
