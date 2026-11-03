<?php

namespace App\Modules\Courses\Domain;

interface FileStorage
{
    public function store(string $directory, string $temporaryPath, string $filename): string;

    public function delete(string $path): void;
}
