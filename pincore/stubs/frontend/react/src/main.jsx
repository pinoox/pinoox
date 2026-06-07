import { createRoot } from 'react-dom/client';
import App from './App.jsx';

const mount = document.querySelector(window.__PINOOX_MOUNT__ || '#app');
if (mount) {
    createRoot(mount).render(<App />);
}
