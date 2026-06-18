// config globale (axios/fetch, base URL API, Sanctum...)
import './bootstrap';

import { createApp } from 'vue';

// le composant racine
import App from './AppPollDashboard.vue';

// la <div id="app"> de la page Blade
const el = document.getElementById('app');

// crée l'app + passe les props injectées par Blade
createApp(App, window.__PROPS__ ?? {})
    // monte l'app dans la div
    .mount(el);