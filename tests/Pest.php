<?php

use App\Models\User;
use App\Modules\Identity\Domain\Role;
use App\Shared\RichText\RichTextDocumentValidator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)
    ->beforeEach(function () {
        test()->withoutVite();
        test()->seed(RolesAndPermissionsSeeder::class);
    })
    ->in('Feature');
pest()->in('Unit', 'Arch');

function userWithRole(Role $role, array $attributes = []): User
{
    $user = User::factory()->create(['password' => 'password-123', ...$attributes]);
    $user->assignRole($role->value);

    return $user;
}

/**
 * @return array{type: 'doc', content: list<array{type: 'paragraph', content: list<array{type: 'text', text: string}>}>}
 */
function richTextDocument(string $text): array
{
    return RichTextDocumentValidator::fromPlainText($text);
}
