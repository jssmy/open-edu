<?php

namespace App\Modules\Courses\Infrastructure;

use App\Modules\Courses\Domain\FileStorage;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class LocalFileStorage implements FileStorage
{
    public function store(string $directory, string $temporaryPath, string $filename): string
    {
        $path = Storage::disk('local')->putFileAs($directory, new File($temporaryPath), $filename);

        if (! is_string($path)) {
            throw new RuntimeException('No se pudo almacenar el archivo de contenido.');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        $disk = Storage::disk('local');

        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('No se pudo eliminar el archivo de contenido.');
        }
    }
}
