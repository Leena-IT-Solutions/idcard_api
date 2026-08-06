import { photoStudio } from './photo-studio.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('photoStudio', photoStudio);
});
