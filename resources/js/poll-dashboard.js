import './bootstrap';
import { createApp } from 'vue';
import App from './AppPollDashboard.vue';

const el = document.getElementById('app');

// window.__PROPS__ contient les données passées depuis Blade
createApp(App, window.__PROPS__ ?? {})
    .mount(el);