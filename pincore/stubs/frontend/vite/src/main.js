import { getBoot } from './boot.js';
import './assets/app.css';

const boot = getBoot();
const root = document.getElementById('app');

if (root) {
    root.innerHTML = `
        <h1>${boot.title ?? 'Pinoox App'}</h1>
        <p>Vite-only frontend — add your JavaScript here.</p>
    `;
}
