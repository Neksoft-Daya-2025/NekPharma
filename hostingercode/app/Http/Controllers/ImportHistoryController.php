<?php

namespace App\Http\Controllers;

use App\Models\ImportHistory;
use Illuminate\Support\Facades\Storage;

class ImportHistoryController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Import History';
    }

    public function index()
    {
        $query = ImportHistory::with('user')->orderBy('created_at', 'desc')
            ->where('company_id', company()->id);

        if (!user()->hasRole('admin')) {
            $query->where('user_id', user()->id);
        }

        $this->imports = $query->paginate(20);

        return view('import-history.index', $this->data);
    }

    public function download($id)
    {
        $import = ImportHistory::findOrFail($id);

        // Enforce company scope (multi-tenant isolation)
        if ($import->company_id != company()->id) {
            abort(403);
        }

        if (!user()->hasRole('admin') && $import->user_id != user()->id) {
            abort(403);
        }

        if (empty($import->filepath)) {
            abort(404, __('File not found.'));
        }

        $disk = Storage::disk('public');
        $relative = str_replace(['..', '\\'], ['', '/'], $import->filepath);

        if (!$disk->exists($relative)) {
            abort(404, __('File not found.'));
        }

        $downloadName = basename($import->filename ?: 'import-file');

        return $disk->download($relative, $downloadName);
    }
}
