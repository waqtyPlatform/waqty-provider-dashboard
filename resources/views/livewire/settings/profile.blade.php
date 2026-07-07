<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.profile.title')" :subtitle="__('settings.profile.desc')" />

    <div class="space-y-6">
        {{-- Identity summary --}}
        <x-ui.card>
            <div class="flex items-center gap-4">
                <div class="grid size-14 shrink-0 place-items-center rounded-full bg-primary-50 text-primary-700">
                    <x-icon name="building-2" class="size-6" />
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-fg">{{ $name }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <x-ui.badge color="info">{{ $role }}</x-ui.badge>
                        <span class="truncate text-sm text-fg-subtle">{{ $email }}</span>
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Detail rows --}}
        <x-ui.card padding="p-0">
            <dl class="divide-y divide-line">
                @foreach ([
                    ['building-2', __('settings.profile.businessName'), $name],
                    ['mail', __('settings.profile.email'), $email],
                    ['shield', __('settings.profile.role'), $role],
                    ['sparkles', __('settings.profile.businessType'), $businessType],
                ] as [$icon, $label, $value])
                    <div class="flex items-center gap-4 px-5 py-4">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface-3 text-fg-subtle">
                            <x-icon :name="$icon" class="size-4" />
                        </span>
                        <dt class="w-40 shrink-0 text-sm font-medium text-fg-subtle">{{ $label }}</dt>
                        <dd class="min-w-0 flex-1 truncate text-start text-sm font-medium text-fg">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.card>
    </div>
</div>
