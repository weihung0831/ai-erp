import './axios';
import Alpine from 'alpinejs';
import authStore from './stores/auth';

Alpine.store('auth', authStore);

window.Alpine = Alpine;
Alpine.start();
