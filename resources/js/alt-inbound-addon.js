import AltInbound from './components/AltInbound.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('alt-inbound::Index', AltInbound);
});
