import './axios';
import Alpine from 'alpinejs';
import authStore from './stores/auth';
import chatStore from './stores/chat';

Alpine.store('auth', authStore);
Alpine.store('chat', chatStore);

window.Alpine = Alpine;
Alpine.start();
