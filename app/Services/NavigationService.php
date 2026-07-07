<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BusinessCategory;
use App\Support\CurrentProvider;
use App\Support\RouteAccess;

/**
 * Builds the sidebar/command-palette navigation. Port of buildNavigation
 * (src/lib/navigation.tsx): business-type-aware group labels + role filtering
 * that reuses RouteAccess so nav visibility and route protection never drift.
 */
class NavigationService
{
    public function __construct(private readonly CurrentProvider $provider) {}

    /** @return array{primary: array<int, array>, footer: array<int, array>} */
    public function build(): array
    {
        $role = $this->provider->role()->value;
        $labels = $this->dynamicLabels($this->provider->businessType());

        return [
            'primary' => $this->section((array) config('navigation.primary'), $role, $labels),
            'footer' => $this->section((array) config('navigation.footer'), $role, $labels),
        ];
    }

    /** @return array{bookings:string, customers:string, employees:string} */
    private function dynamicLabels(BusinessCategory $type): array
    {
        $isClinic = $type === BusinessCategory::Clinic;
        $isBarber = $type === BusinessCategory::Barber;

        return [
            'bookings' => __($isClinic || $isBarber ? 'sidebar.appointments' : 'sidebar.bookings'),
            'customers' => __($isClinic ? 'sidebar.patients' : 'sidebar.clients'),
            'employees' => __($isClinic ? 'sidebar.doctors' : ($isBarber ? 'sidebar.barbers' : 'sidebar.stylists')),
        ];
    }

    private function section(array $groups, string $role, array $labels): array
    {
        $out = [];

        foreach ($groups as $group) {
            if (! $this->allowed($group, $role)) {
                continue;
            }

            $children = [];
            foreach ($group['children'] ?? [] as $child) {
                // Section sub-header (non-link) — passes through as a labelled divider.
                if (isset($child['header'])) {
                    $children[] = ['header' => __($child['header'])];

                    continue;
                }
                if (! $this->allowed($child, $role)) {
                    continue;
                }
                $children[] = [
                    'label' => $this->label($child['label'], $labels),
                    'href' => $child['href'],
                    'soon' => ! empty($child['soon']),
                ];
            }

            // Trim orphan headers (a header whose whole section was role-filtered away).
            $children = $this->trimOrphanHeaders($children);

            // Drop groups that ended up empty and have no own destination.
            if ($children === [] && empty($group['href'])) {
                continue;
            }

            $out[] = [
                'id' => $group['id'],
                'label' => isset($group['dynamic']) ? $labels[$group['dynamic']] : __($group['label']),
                'icon' => $group['icon'] ?? null,
                'href' => $group['href'] ?? null,
                'children' => $children,
            ];
        }

        return $out;
    }

    private function label(string $label, array $labels): string
    {
        return str_starts_with($label, '@') ? $labels[substr($label, 1)] : __($label);
    }

    /**
     * Drop headers that have no link children under them (section fully filtered out).
     *
     * @param  array<int, array>  $children
     * @return array<int, array>
     */
    private function trimOrphanHeaders(array $children): array
    {
        $out = [];
        $count = count($children);
        foreach ($children as $i => $child) {
            if (isset($child['header'])) {
                // Keep only if a link follows before the next header.
                $hasLink = false;
                for ($j = $i + 1; $j < $count; $j++) {
                    if (isset($children[$j]['header'])) {
                        break;
                    }
                    $hasLink = true;
                    break;
                }
                if (! $hasLink) {
                    continue;
                }
            }
            $out[] = $child;
        }

        return $out;
    }

    private function allowed(array $node, string $role): bool
    {
        if (isset($node['roles']) && ! in_array($role, $node['roles'], true)) {
            return false;
        }

        if (! empty($node['href']) && ! RouteAccess::allowed($node['href'], $role)) {
            return false;
        }

        return true;
    }
}
