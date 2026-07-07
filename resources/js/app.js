import ApexCharts from 'apexcharts';

// Expose ApexCharts for Alpine-driven chart components (Livewire 4 bundles Alpine).
window.ApexCharts = ApexCharts;

document.addEventListener('alpine:init', () => {
    // Shared UI state (sidebar collapse, mobile nav) so the shell stays in sync.
    window.Alpine.store('ui', {
        sidebarCollapsed: JSON.parse(localStorage.getItem('waqty_sidebar_collapsed') || 'false'),
        mobileNavOpen: false,
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('waqty_sidebar_collapsed', JSON.stringify(this.sidebarCollapsed));
        },
    });

    // Alpine chart helper: <div x-data="chart(optionsJson)"
    //   @some-event.window="update($event.detail.key)"></div>
    // `update(opts)` lets a Livewire-dispatched browser event refresh the chart
    // in place (the chart lives inside wire:ignore so morphing never touches it).
    window.Alpine.data('chart', (options) => ({
        instance: null,
        init() {
            this.instance = new ApexCharts(this.$el, options);
            this.instance.render();
        },
        update(opts) {
            if (opts && this.instance) {
                this.instance.updateOptions(opts, true, true);
            }
        },
        destroy() {
            this.instance?.destroy();
        },
    }));
});
