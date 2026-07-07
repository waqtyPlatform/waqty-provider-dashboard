<?php

declare(strict_types=1);

use App\Livewire\Settings\AuditLog;
use Livewire\Livewire;

it('renders the seeded audit rows as plain text', function () {
    Livewire::test(AuditLog::class)
        ->assertSee('تسجيل دخول')
        ->assertSee('سارة أحمد')
        ->assertSee('10:32 AM');
});

it('filters rows by action or user, case-insensitively', function () {
    $c = Livewire::test(AuditLog::class)->set('search', 'علي');

    expect($c->get('filtered'))->toHaveCount(1)
        ->and($c->get('filtered')[0]['action'])->toBe('تحديث خدمة');

    $c->set('search', 'سارة');
    expect($c->get('filtered'))->toHaveCount(3);
});

it('returns no rows when the search matches nothing', function () {
    $c = Livewire::test(AuditLog::class)->set('search', 'nonexistent-term');

    expect($c->get('filtered'))->toBeEmpty();
});
