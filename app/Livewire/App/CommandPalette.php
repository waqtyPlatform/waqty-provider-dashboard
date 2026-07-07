<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Services\NavigationService;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * ⌘K / Ctrl-K quick switcher. Opens on the browser `open-command-palette`
 * event (topbar button + keydown). Filters the same role/business-type-aware
 * navigation tree the sidebar uses, so it never lists a forbidden route.
 */
class CommandPalette extends Component
{
    public string $search = '';

    /** @return array<int, array{label:string, href:string, group:string, icon:?string}> */
    #[Computed]
    public function destinations(): array
    {
        $nav = app(NavigationService::class)->build();
        $items = [];

        foreach (['primary', 'footer'] as $section) {
            foreach ($nav[$section] as $group) {
                if (! empty($group['href'])) {
                    $items[] = ['label' => $group['label'], 'href' => $group['href'], 'group' => $group['label'], 'icon' => $group['icon'] ?? null];
                }
                foreach ($group['children'] as $child) {
                    // Skip section headers (no href) and not-yet-built ("soon") items.
                    if (empty($child['href']) || ! empty($child['soon'])) {
                        continue;
                    }
                    $items[] = ['label' => $child['label'], 'href' => $child['href'], 'group' => $group['label'], 'icon' => $group['icon'] ?? null];
                }
            }
        }

        return $items;
    }

    /** @return array<int, array{label:string, href:string, group:string, icon:?string}> */
    #[Computed]
    public function results(): array
    {
        $q = trim(mb_strtolower($this->search));
        $items = $this->destinations();

        if ($q === '') {
            return array_slice($items, 0, 8);
        }

        return array_slice(array_values(array_filter(
            $items,
            fn ($i) => str_contains(mb_strtolower($i['label']), $q) || str_contains(mb_strtolower($i['group']), $q),
        )), 0, 12);
    }

    public function render()
    {
        return view('livewire.app.command-palette');
    }
}
