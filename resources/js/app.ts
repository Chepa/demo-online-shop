import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import ShopLayout from './Layouts/ShopLayout.vue';

const app = createApp(ShopLayout);
app.use(createPinia());
app.use(router);
app.mount('#app');

