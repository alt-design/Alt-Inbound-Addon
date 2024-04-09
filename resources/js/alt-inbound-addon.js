import AltInbound from './components/AltInbound.vue';

Statamic.booting(() => {
    Statamic.$components.register('alt-inbound', AltInbound);
});
