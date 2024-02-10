import AltBlocker from './components/AltBlocker.vue';

Statamic.booting(() => {
    Statamic.$components.register('alt-blocker', AltBlocker);
});
