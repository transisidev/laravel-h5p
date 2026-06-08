<?php

namespace Transisi\LaravelH5P\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Chunked uploads for the H5P editor iframe (bypasses single-POST limits, e.g. Cloudflare ~100MB).
 * Assemble merges parts then runs the same FILES ajax flow as the package AjaxController.
 */
class H5pEditorChunkUploadController extends Controller
{
    public function storeChunk(Request $request)
    {
        $this->ensureEnabled();

        $maxChunk = (int) config('laravel-h5p.h5p_editor_chunk_max_chunk_bytes', 5242880);
        $maxChunkKb = max(1, (int) ceil($maxChunk / 1024));

        $validated = $request->validate([
            'upload_id'    => ['required', 'uuid'],
            'chunk_index'  => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:50000'],
            'filename'     => ['required', 'string', 'max:512'],
            'chunk'        => ['required', 'file', 'max:' . $maxChunkKb],
        ]);
        $maxTotal = (int) config('laravel-h5p.h5p_editor_chunk_max_total_bytes', 536870912);

        if ($request->file('chunk')->getSize() > $maxChunk) {
            return response()->json(['success' => false, 'message' => 'Chunk too large.'], 422);
        }

        $uploadId = $validated['upload_id'];
        $dir = $this->chunkDir($uploadId);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return response()->json(['success' => false, 'message' => 'Could not create upload directory.'], 500);
        }

        $safeName = basename($validated['filename']);
        $metaPath = $dir . DIRECTORY_SEPARATOR . 'meta.json';
        if (!is_file($metaPath)) {
            file_put_contents($metaPath, json_encode(['filename' => $safeName], JSON_THROW_ON_ERROR));
        } else {
            $meta = json_decode((string) file_get_contents($metaPath), true, 512, JSON_THROW_ON_ERROR);
            if (($meta['filename'] ?? '') !== $safeName) {
                return response()->json(['success' => false, 'message' => 'Filename mismatch for this upload.'], 422);
            }
        }

        $partPath = $dir . DIRECTORY_SEPARATOR . (int) $validated['chunk_index'] . '.part';
        $chunk = $request->file('chunk');
        $chunk->move(dirname($partPath), basename($partPath));

        $stored = $this->sumStoredBytes($dir);
        if ($stored > $maxTotal) {
            $this->deleteChunkDir($dir);

            return response()->json(['success' => false, 'message' => 'Upload exceeds allowed size.'], 422);
        }

        return response()->json(['success' => true, 'chunkIndex' => (int) $validated['chunk_index']]);
    }

    public function assemble(Request $request)
    {
        $this->ensureEnabled();

        $validated = $request->validate([
            'upload_id'    => ['required', 'uuid'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:50000'],
            'field'        => ['required', 'string'],
            'contentId'    => ['nullable'],
            'filename'     => ['required', 'string', 'max:512'],
        ]);

        $maxTotal = (int) config('laravel-h5p.h5p_editor_chunk_max_total_bytes', 536870912);
        $uploadId = $validated['upload_id'];
        $total = (int) $validated['total_chunks'];
        $dir = $this->chunkDir($uploadId);

        if (!is_dir($dir)) {
            return response()->json(['success' => false, 'message' => 'Upload session not found.'], 404);
        }

        $metaPath = $dir . DIRECTORY_SEPARATOR . 'meta.json';
        $safeName = basename($validated['filename']);
        if (is_file($metaPath)) {
            $meta = json_decode((string) file_get_contents($metaPath), true, 512, JSON_THROW_ON_ERROR);
            if (($meta['filename'] ?? '') !== $safeName) {
                return response()->json(['success' => false, 'message' => 'Filename mismatch.'], 422);
            }
        }

        for ($i = 0; $i < $total; $i++) {
            if (!is_file($dir . DIRECTORY_SEPARATOR . $i . '.part')) {
                return response()->json(['success' => false, 'message' => 'Missing chunk ' . $i . '.'], 422);
            }
        }

        $merged = $dir . DIRECTORY_SEPARATOR . 'merged.bin';
        $out = fopen($merged, 'wb');
        if ($out === false) {
            return response()->json(['success' => false, 'message' => 'Could not create merged file.'], 500);
        }

        $totalSize = 0;
        try {
            for ($i = 0; $i < $total; $i++) {
                $part = $dir . DIRECTORY_SEPARATOR . $i . '.part';
                $in = fopen($part, 'rb');
                if ($in === false) {
                    throw new \RuntimeException('Could not read chunk ' . $i);
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
                $totalSize += filesize($part);
                if ($totalSize > $maxTotal) {
                    throw new \RuntimeException('Total size exceeds limit');
                }
            }
        } catch (\Throwable $e) {
            fclose($out);
            if (is_file($merged)) {
                @unlink($merged);
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        fclose($out);

        if (!is_file($merged) || $totalSize === 0) {
            return response()->json(['success' => false, 'message' => 'Merged file is empty.'], 422);
        }

        $mime = (function () use ($merged) {
            if (function_exists('finfo_open')) {
                $f = finfo_open(FILEINFO_MIME_TYPE);

                return $f ? finfo_file($f, $merged) : 'application/octet-stream';
            }

            return 'application/octet-stream';
        })();

        $_FILES['file'] = [
            'name'     => $safeName,
            'type'     => $mime,
            'tmp_name' => $merged,
            'error'    => UPLOAD_ERR_OK,
            'size'     => $totalSize,
        ];

        $_POST['field'] = $validated['field'];
        $_POST['contentId'] = $validated['contentId'] ?? 0;

        $token = $request->input('_token');
        $contentId = $request->input('contentId', 0);

        ob_start();
        try {
            $h5p = App::make('LaravelH5p');
            $editor = $h5p::$h5peditor;
            $editor->ajax->action(\H5PEditorEndpoints::FILES, $token, $contentId);
        } catch (\Throwable $e) {
            ob_end_clean();
            if (is_file($merged)) {
                @unlink($merged);
            }
            $this->deleteChunkDir($dir);
            unset($_FILES['file']);

            report($e);

            return response()->json(['success' => false, 'message' => 'H5P could not process the merged file.'], 500);
        }

        $output = ob_get_clean();

        if (is_file($merged)) {
            @unlink($merged);
        }
        $this->deleteChunkDir($dir);
        unset($_FILES['file']);

        return response($output, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Cache-Control', 'no-cache');
    }

    protected function chunkDir(string $uploadId): string
    {
        return storage_path('app/h5p-chunk-uploads/' . $uploadId);
    }

    protected function sumStoredBytes(string $dir): int
    {
        $sum = 0;
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.part') ?: [] as $path) {
            $sum += (int) @filesize($path);
        }

        return $sum;
    }

    protected function deleteChunkDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteChunkDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    protected function ensureEnabled(): void
    {
        if (!config('laravel-h5p.h5p_editor_chunk_upload_enabled', false)) {
            abort(404);
        }
    }
}
