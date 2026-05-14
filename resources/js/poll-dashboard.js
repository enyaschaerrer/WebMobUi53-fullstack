import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './components/AppPollDashboard.vue';

const el = document.getElementById('app');
const props = JSON.parse(el.dataset.props);

const app = createApp(App, props);
app.use(createPinia());
app.mount(el);