<div class="mx-auto max-w-2xl p-6">
    <a href="{{ route('help') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('help.title') }}
    </a>

    <x-ui.page-header :title="__('help.bug.title')" :subtitle="__('help.bug.subtitle')" />

    @if ($submitted)
        <x-ui.card>
            <div class="flex flex-col items-center py-8 text-center">
                <div class="mb-4 grid size-14 place-items-center rounded-full bg-success/10 text-success"><x-icon name="check" class="size-7" /></div>
                <h3 class="text-lg font-semibold text-fg">{{ __('help.bug.submitted') }}</h3>
                <p class="mt-1 max-w-sm text-sm text-fg-muted">{{ __('help.bug.submittedDesc') }}</p>
                <x-ui.button class="mt-5" variant="secondary" wire:click="reset_form">{{ __('help.bug.another') }}</x-ui.button>
            </div>
        </x-ui.card>
    @else
        <x-ui.card>
            <form wire:submit="submit" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('help.bug.category') }}</label>
                        <select wire:model="category" class="w-full rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            @foreach (\App\Livewire\Help\BugReport::CATEGORIES as $c)
                                <option value="{{ $c }}">{{ __('help.bug.cat.'.$c) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('help.bug.severity') }}</label>
                        <select wire:model="severity" class="w-full rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            @foreach (\App\Livewire\Help\BugReport::SEVERITIES as $s)
                                <option value="{{ $s }}">{{ __('help.bug.sev.'.$s) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('help.bug.description') }}</label>
                    <textarea wire:model="description" rows="4" placeholder="{{ __('help.bug.descPh') }}"
                        class="w-full rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-primary-500/20 {{ $errors->has('description') ? 'border-error' : 'border-line focus:border-primary-500' }}"></textarea>
                    @error('description') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('help.bug.steps') }}</label>
                    <textarea wire:model="steps" rows="3" placeholder="{{ __('help.bug.stepsPh') }}"
                        class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('steps') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('help.bug.screenshot') }}</label>
                    <input type="file" wire:model="screenshot" accept="image/*"
                        class="block w-full text-sm text-fg-muted file:me-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100" />
                    <div wire:loading wire:target="screenshot" class="mt-1.5 text-xs text-fg-subtle">…</div>
                    @error('screenshot') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end border-t border-line pt-4">
                    <x-ui.button type="submit" icon="check">{{ __('help.bug.submit') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif
</div>
