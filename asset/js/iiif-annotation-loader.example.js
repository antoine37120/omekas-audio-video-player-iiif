/**
 * Exemple d'utilisation de l'API IIIF Annotations
 * 
 * Ce fichier démontre comment récupérer et utiliser les annotations IIIF
 * pour un média dans une application cliente.
 */

class IIIFAnnotationLoader {
    constructor(siteSlug, baseUrl = window.location.origin) {
        this.siteSlug = siteSlug;
        this.baseUrl = baseUrl;
    }

    /**
     * Récupère les annotations IIIF pour un média
     * @param {number} mediaId - L'ID du média
     * @returns {Promise<Object>} - Les données IIIF
     */
    async loadAnnotations(mediaId) {
        const url = `${this.baseUrl}/s/${this.siteSlug}/audio-player/annotation/iiif/${mediaId}`;

        try {
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur lors du chargement des annotations IIIF:', error);
            throw error;
        }
    }

    /**
     * Parse le fragment temporel d'une cible IIIF
     * @param {string} target - La cible de l'annotation (URL avec fragment)
     * @returns {Object} - {start: number, end: number, isPoint: boolean}
     */
    parseTimeFragment(target) {
        const match = target.match(/#t=([0-9.]+)(?:,([0-9.]+))?/);

        if (!match) {
            return null;
        }

        const start = parseFloat(match[1]);
        const end = match[2] ? parseFloat(match[2]) : start;

        return {
            start: start,
            end: end,
            isPoint: start === end
        };
    }

    /**
     * Convertit les annotations IIIF en format simplifié pour l'utilisation
     * @param {Object} iiifData - Les données IIIF
     * @returns {Array} - Tableau d'annotations simplifiées
     */
    convertToSimpleFormat(iiifData) {
        return iiifData.items.map(annotation => {
            const timeFragment = this.parseTimeFragment(annotation.target);

            return {
                id: annotation.id,
                title: annotation.body.label || '',
                description: annotation.body.value,
                start: timeFragment.start,
                end: timeFragment.end,
                isPoint: timeFragment.isPoint,
                created: annotation.created || null,
                creator: annotation.creator || null
            };
        });
    }

    /**
     * Charge et convertit les annotations en un seul appel
     * @param {number} mediaId - L'ID du média
     * @returns {Promise<Array>} - Tableau d'annotations simplifiées
     */
    async loadAndConvert(mediaId) {
        const iiifData = await this.loadAnnotations(mediaId);
        return this.convertToSimpleFormat(iiifData);
    }
}

// Exemple d'utilisation avec un lecteur audio
class AudioPlayerWithAnnotations {
    constructor(audioElement, mediaId, siteSlug) {
        this.audio = audioElement;
        this.mediaId = mediaId;
        this.loader = new IIIFAnnotationLoader(siteSlug);
        this.annotations = [];
        this.currentAnnotation = null;
    }

    async init() {
        // Charger les annotations
        this.annotations = await this.loader.loadAndConvert(this.mediaId);
        console.log('Annotations chargées:', this.annotations);

        // Écouter les changements de temps
        this.audio.addEventListener('timeupdate', () => {
            this.checkAnnotations();
        });

        // Créer les marqueurs visuels
        this.createMarkers();
    }

    checkAnnotations() {
        const currentTime = this.audio.currentTime;

        // Trouver l'annotation active
        const activeAnnotation = this.annotations.find(ann =>
            currentTime >= ann.start && currentTime <= ann.end
        );

        if (activeAnnotation !== this.currentAnnotation) {
            this.currentAnnotation = activeAnnotation;
            this.onAnnotationChange(activeAnnotation);
        }
    }

    onAnnotationChange(annotation) {
        if (annotation) {
            console.log('Annotation active:', annotation.title, annotation.description);
            // Afficher l'annotation dans l'interface
            this.displayAnnotation(annotation);
        } else {
            // Masquer l'annotation
            this.hideAnnotation();
        }
    }

    displayAnnotation(annotation) {
        // Exemple d'affichage dans un élément HTML
        const container = document.getElementById('annotation-display');
        if (container) {
            container.innerHTML = `
                <div class="annotation">
                    <h3>${annotation.title}</h3>
                    <p>${annotation.description}</p>
                    <small>
                        ${this.formatTime(annotation.start)} - ${this.formatTime(annotation.end)}
                    </small>
                </div>
            `;
            container.style.display = 'block';
        }
    }

    hideAnnotation() {
        const container = document.getElementById('annotation-display');
        if (container) {
            container.style.display = 'none';
        }
    }

    createMarkers() {
        // Créer des marqueurs visuels sur une timeline
        const timeline = document.getElementById('timeline');
        if (!timeline || !this.audio.duration) return;

        this.annotations.forEach(annotation => {
            const marker = document.createElement('div');
            marker.className = 'annotation-marker';
            marker.style.left = `${(annotation.start / this.audio.duration) * 100}%`;
            marker.title = annotation.title;

            marker.addEventListener('click', () => {
                this.audio.currentTime = annotation.start;
            });

            timeline.appendChild(marker);
        });
    }

    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}

// Exemple d'utilisation
document.addEventListener('DOMContentLoaded', async () => {
    const audioElement = document.getElementById('audio-player');
    const mediaId = audioElement.dataset.mediaId; // Récupérer l'ID depuis un attribut data
    const siteSlug = 'mon-site'; // Ou récupérer dynamiquement

    if (audioElement && mediaId) {
        const player = new AudioPlayerWithAnnotations(audioElement, mediaId, siteSlug);
        await player.init();
    }
});

// Export pour utilisation en module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { IIIFAnnotationLoader, AudioPlayerWithAnnotations };
}
