<?php

namespace App\Modules\Identity\Domain;

final readonly class PermissionRecord
{
    public function __construct(
        public string $name,
        public string $label,
        public string $module,
        public ?string $description = null,
    ) {}

    /** @return array{name: string, label: string, module: string, description: ?string} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'module' => $this->module,
            'description' => $this->description,
        ];
    }
}
