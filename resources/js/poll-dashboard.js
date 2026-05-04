import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './components/AppPollDashboard.vue';

const app = createApp(App, window.__PROPS__);
app.use(createPinia());
app.mount('#app');