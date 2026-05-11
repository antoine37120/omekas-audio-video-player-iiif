# AudioPlayer Module pour Omeka S

Module Omeka S pour la lecture audio et vidéo enrichie avec support des annotations temporelles au format IIIF. Ce module utilise un composant web personnalisé pour l'affichage et l'interaction avec les médias.

## Fonctionnalités

- **Lecteur Audio/Vidéo Interactif** : Un lecteur moderne pour les médias audio et vidéo avec affichage de la forme d'onde (waveform).
- **Annotations temporelles** : Création et gestion d'annotations avec marqueurs temporels (points ou plages).
- **Export IIIF** : Export des annotations au format IIIF Presentation API 3.0 (W3C Web Annotation).
- **API REST** : API complète pour la gestion des annotations (CRUD) et l'export IIIF.
- **Bloc de mise en page** : Un bloc "Lecteur Audio/Vidéo module custom" pour intégrer facilement le lecteur sur les pages de ressources (Items et Médias).
- **Interface d'Administration** : Tableau de bord dédié pour la gestion globale des annotations.
- **Support des sous-titres** : Intégration de sous-titres via une API externe ou des patterns d'URL.
- **Mode Embed** : Possibilité d'intégrer le lecteur via une iframe.

## Installation

1. Copiez le dossier `AudioPlayer` dans le répertoire `modules` de votre installation Omeka S.
2. Activez le module depuis l'interface d'administration.
3. Le module créera automatiquement la table `media_markers` dans la base de données lors de l'installation.

## Configuration

Le module propose plusieurs options de configuration dans l'administration (Modules > AudioPlayer > Config) :

- **Couleurs et Style** : Personnalisation de la couleur et de l'épaisseur de la forme d'onde, ainsi que des couleurs du lecteur.
- **Vitesse de lecture** : Configuration des taux de lecture disponibles (ex: `[0.5, 1, 1.5, 2, 4]`).
- **Patterns d'URL** : Définition des modèles d'URL pour récupérer dynamiquement :
    - Les fichiers médias (audio/vidéo)
    - Les fichiers de forme d'onde (waveform JSON)
    - Les fichiers de sous-titres (JSON)
- **Propriétés de métadonnées** : Configuration des termes de propriétés utilisés pour identifier le format (ex: `dcterms:format`) et la cote (ex: `crem:cote`).
- **Mode Debug** : Option pour afficher les raisons d'incompatibilité d'un média directement dans l'interface.

## Structure de la base de données

### Table `media_markers`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | int(11) | ID auto-incrémenté |
| `resource_id` | int(11) | ID du média Omeka S |
| `public_id` | varchar(250) | Identifiant public de l'annotation (utilisé en IIIF) |
| `time` | double | Temps de début (secondes) |
| `time_end` | double | Temps de fin (secondes) |
| `title` | varchar(250) | Titre de l'annotation |
| `date` | datetime | Date de création |
| `description` | longtext | Description/Contenu de l'annotation |
| `author_id` | int(11) | ID de l'utilisateur auteur |

## Utilisation

### Bloc de mise en page

Pour afficher le lecteur sur vos pages de ressources :

1. Accédez à l'administration d'un site.
2. Allez dans **Pages de ressources** (sous Thème).
3. Modifiez la page de ressource souhaitée (Items ou Media).
4. Ajoutez le bloc **"Lecteur Audio/Vidéo module custom"**.
5. Le lecteur s'affichera automatiquement si le média correspond aux critères de format définis (audio/video).

### Interface d'Administration des Annotations

Dans le menu principal de l'administration (section Modules/Global), cliquez sur **Audio Player Annotations** pour accéder au tableau de bord :
- **Lister** toutes les annotations présentes dans le système.
- **Rechercher et Filtrer** par titre, ID de ressource ou auteur.
- **Supprimer** des annotations obsolètes.

## API REST et IIIF

Le module expose des endpoints pour interagir avec les annotations.

### 1. API Site (Contextualisée au site)
Base URL : `/s/{site-slug}/audio-player/annotation`

- **GET /index/{media-id}** : Liste les annotations pour un média.
- **GET /get/{id}** : Récupère une annotation spécifique.
- **POST /create** : Crée une annotation.
- **POST /update/{id}** : Met à jour une annotation.
- **POST /delete/{id}** : Supprime une annotation.
- **GET /iiif/{media-id}** : Export au format IIIF Presentation 3.0.

### 2. API Globale (via /api)
Base URL : `/api/audio-player/annotation` (similaire à l'API site mais sans contexte de site).

### Exemple de corps JSON (POST/PUT)
```json
{
  "resource_id": 123,
  "public_id": "annotation-12345",
  "time": 10.5,
  "time_end": 25.0,
  "title": "Ma superbe annotation",
  "description": "Détails de l'observation...",
  "author_id": 1
}
```

Pour plus de détails techniques sur IIIF, consultez [IIIF_ANNOTATIONS.md](IIIF_ANNOTATIONS.md). Pour des exemples d'utilisation en JavaScript, consultez [API_USAGE.md](API_USAGE.md).

## Structure du code

```
AudioPlayer/
├── Module.php                          # Classe principale du module
├── config/
│   └── module.config.php              # Configuration des routes et services
├── src/
│   ├── Controller/
│   │   ├── Admin/                     # Dashboard d'administration
│   │   └── Site/                      # Lecteur et API site
│   ├── Service/
│   │   ├── AnnotationService.php      # Gestion CRUD et IIIF
│   │   └── PlayerService.php          # Logique d'affichage et URLs
│   ├── Site/
│   │   └── ResourcePageBlockLayout/   # Bloc de mise en page Omeka S
│   └── View/
│       └── Helper/                    # Helper audioPlayer pour les vues
├── view/
│   ├── audio-player/                  # Vues du module
│   └── common/
│       └── audio-video-player.phtml   # Template du Web Component
└── asset/
    └── vendor/                        # Dépendances du lecteur (JS/CSS)
```

## Développement

### Ajouter une fonctionnalité
1. Logique métier : `src/Service/AnnotationService.php`
2. Contrôleur : `src/Controller/Site/AnnotationController.php`
3. Route : `config/module.config.php`

### Web Component
Le lecteur est basé sur un composant web personnalisé. Les assets se trouvent dans `asset/vendor/audio-video-player-iiif`.

## Compatibilité

- Omeka 4.x
- PHP 8.3 ou supérieur
- Navigateurs modernes (support des Web Components)

## Licence

GPL v3

## Support

Pour signaler un bug ou demander une fonctionnalité, veuillez créer une issue dans le dépôt du projet.
