<?php

namespace App\Services\TrackingDocument;

use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackingDocumentFileService
{
    private const TYPE_MAP = [
        'partlist' => ['path' => 'partlist_file_path', 'name' => 'partlist_original_name', 'directory' => 'tracking-documents/partlist'],
        'umh' => ['path' => 'umh_file_path', 'name' => 'umh_original_name', 'directory' => 'tracking-documents/umh'],
        'a00' => ['path' => 'a00_document_file_path', 'name' => 'a00_document_original_name', 'directory' => 'tracking-documents/a00'],
        'a04' => ['path' => 'a04_document_file_path', 'name' => 'a04_document_original_name', 'directory' => 'tracking-documents/a04'],
        'a05' => ['path' => 'a05_document_file_path', 'name' => 'a05_document_original_name', 'directory' => 'tracking-documents/a05'],
    ];

    public function storeUploadedFile(UploadedFile $file, string $type): array
    {
        $config = $this->getConfig($type);
        $path = $file->storeAs(
            $config['directory'],
            now()->format('YmdHis') . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension()
        );

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ];
    }

    public function replaceUploadedFile(DocumentRevision $revision, UploadedFile $file, string $type): array
    {
        $config = $this->getConfig($type);
        $currentPath = $revision->{$config['path']};
        if ($currentPath && Storage::exists($currentPath)) {
            Storage::delete($currentPath);
        }

        return $this->storeUploadedFile($file, $type);
    }

    public function clearFileReference(string $type): array
    {
        $config = $this->getConfig($type);

        return [
            $config['path'] => null,
            $config['name'] => null,
        ];
    }

    public function buildFileUpdatePayload(string $type, array $stored): array
    {
        $config = $this->getConfig($type);

        return [
            $config['path'] => $stored['path'],
            $config['name'] => $stored['name'],
        ];
    }

    public function collectProjectFilePaths(DocumentProject $project): Collection
    {
        return $project->revisions()
            ->get(array_column(self::TYPE_MAP, 'path'))
            ->flatMap(function ($revision) {
                return collect(self::TYPE_MAP)
                    ->pluck('path')
                    ->map(fn ($field) => $revision->{$field})
                    ->all();
            })
            ->filter()
            ->unique()
            ->values();
    }

    public function collectRevisionFilePaths(DocumentRevision $revision): Collection
    {
        return collect(self::TYPE_MAP)
            ->pluck('path')
            ->map(fn ($field) => $revision->{$field})
            ->filter()
            ->unique()
            ->values();
    }

    public function deletePaths(iterable $paths): void
    {
        foreach ($paths as $path) {
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }

    public function deletePathIfUnused(string $path): void
    {
        if ($path === '') {
            return;
        }

        $isStillUsed = DocumentRevision::query()
            ->where(function ($query) use ($path) {
                foreach (collect(self::TYPE_MAP)->pluck('path') as $index => $field) {
                    if ($index === 0) {
                        $query->where($field, $path);
                    } else {
                        $query->orWhere($field, $path);
                    }
                }
            })
            ->exists();

        if (! $isStillUsed && Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    public function downloadRevisionFile(DocumentRevision $revision, string $type): Response
    {
        $config = $this->getConfig($type);
        $path = $revision->{$config['path']};
        $name = $revision->{$config['name']};

        if (! $path || ! Storage::exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::download($path, $name);
    }

    public function inlineRevisionFile(DocumentRevision $revision, string $type): Response
    {
        $config = $this->getConfig($type);
        $path = $revision->{$config['path']};
        $name = $revision->{$config['name']};

        if (! $path || ! Storage::exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $absolutePath = Storage::path($path);
        $mimeType = Storage::mimeType($path) ?: 'application/octet-stream';
        $safeName = str_replace('"', '\\"', (string) ($name ?: basename($path)));

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $safeName . '"',
        ]);
    }

    private function getConfig(string $type): array
    {
        if (! isset(self::TYPE_MAP[$type])) {
            abort(404);
        }

        return self::TYPE_MAP[$type];
    }
}
