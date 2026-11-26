// Global Online/Offline Event Dispatcher for Livewire
window.addEventListener('offline', () => {
    window.dispatchEvent(new CustomEvent('app:network-offline', { detail: { isOnline: false } }));
});

window.addEventListener('online', () => {
    window.dispatchEvent(new CustomEvent('app:network-online', { detail: { isOnline: true } }));
});
