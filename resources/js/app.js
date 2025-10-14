import './bootstrap';
import { initializeHistoryModal } from './components/employmentHistoryModal.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', function () {
    initializeHistoryModal();
});
