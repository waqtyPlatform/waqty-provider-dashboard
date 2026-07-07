<?php

declare(strict_types=1);

namespace App\Livewire\App;

use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Global toast host. Any component may `->dispatch('notify', type: '...', message: '...')`.
 * Port of ToastProvider/useToast (src/components/ui/index.tsx): success/info/warning
 * auto-dismiss after 5s (handled in the view via Alpine); errors persist.
 */
class Toasts extends Component
{
    /** @var array<int, array{id:string, type:string, message:string}> */
    public array $toasts = [];

    /** Bridge a `session()->flash('notify', ...)` (set before a redirect) into a toast. */
    public function mount(): void
    {
        if ($message = session('notify')) {
            $this->notify(session('notify_type', 'success'), (string) $message);
        }
    }

    #[On('notify')]
    public function notify(string $type = 'info', string $message = ''): void
    {
        $this->toasts[] = [
            'id' => (string) Str::uuid(),
            'type' => in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info',
            'message' => $message,
        ];
    }

    public function dismiss(string $id): void
    {
        $this->toasts = array_values(array_filter($this->toasts, fn ($t) => $t['id'] !== $id));
    }

    public function render()
    {
        return view('livewire.app.toasts');
    }
}
