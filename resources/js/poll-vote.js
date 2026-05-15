import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './AppPollVote.vue';

const el = document.getElementById('app');

// window.__PROPS__ contient le token et l'url de login passés depuis Blade
createApp(App, window.__PROPS__ ?? {})
    .use(createPinia())
    .mount(el);