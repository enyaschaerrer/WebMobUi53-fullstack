import './bootstrap';
import { createApp } from 'vue';
import App from './AppPollVote.vue';

const el = document.getElementById('app');

// window.__PROPS__ contient le token et l'url de login passés depuis Blade
createApp(App, window.__PROPS__ ?? {})
    .mount(el);