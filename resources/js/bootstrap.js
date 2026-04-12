import './axios';
import Alpine from 'alpinejs';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import authStore from './stores/auth';
import chatStore from './stores/chat';

marked.setOptions({ breaks: true, gfm: true });
window.renderMarkdown = (text) => DOMPurify.sanitize(marked.parse(text || ''));

Alpine.store('auth', authStore);
Alpine.store('chat', chatStore);

window.Alpine = Alpine;
Alpine.start();
