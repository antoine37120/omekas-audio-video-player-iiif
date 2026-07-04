# AudioPlayer Module pour Omeka S

Module Omeka S pour la lecture audio et vidéo enrichie avec support des annotations temporelles au format IIIF. Ce module utilise un composant web personnalisé pour l'affichage et l'interaction avec les médias.

## Fonctionnalités

- **Lecteur Audio/Vidéo Interactif** : Un lecteur moderne pour les médias audio et vidéo avec affichage de la forme d'onde (waveform).
- **Annotations temporelles** : Création et gestion d'annotations avec marqueurs temporels (points ou plages).
- **Export IIIF** : Export des annotations au format IIIF Presentation API 3.0 (W3C Web Annotation).
- **API REST** : API complète pour la gestion des annotations (CRUD) et l'export IIIF.
- **Bloc de mise en page** : Un bloc "Lecteur Audio/Vidéo module custom" pour intégrer facilement le lecteur sur les pages de ressources (Items et Médias).
- **Interface d'Administration** : Tableau de bord dédié pour la gestion globale des annotations.
- **Support des sous-titres** : Chargement dynamique des sous-titres depuis une API externe retournant un JSON (`url`, `language_code`), avec mapping configurable des champs de l'API. Un pattern d'URL peut aussi être utilisé directement.
- **Sécurisation des médias (HMAC)** : Signature cryptographique (HMAC) des URLs des médias, formes d'onde et sous-titres, pour contrôler l'accès à des ressources distantes (ex. MMS) via un secret partagé et un identifiant d'application.
- **Hauteurs configurables** : Hauteur du lecteur définie séparément pour l'audio, la vidéo, et la zone d'annotations.
- **Mode Embed** : Possibilité d'intégrer le lecteur via une iframe.

## Installation

1. Copiez le dossier `AudioPlayer` dans le répertoire `modules` de votre installation Omeka S.
2. Activez le module depuis l'interface d'administration.
3. Le module créera automatiquement la table `media_markers` dans la base de données lors de l'installation.

## Configuration

Le module propose plusieurs options de configuration dans l'administration (Modules > AudioPlayer > Config) :

- **Couleurs et Style** : Personnalisation de la couleur (`waveform_stroke_color`) et de l'épaisseur (`waveform_stroke_width`) de la forme d'onde, ainsi que des couleurs du lecteur (objet JSON `colors`).
- **Hauteurs du lecteur** : Trois hauteurs indépendantes peuvent être définies :
    - `height_audio` (défaut : `250`) — hauteur du lecteur pour les médias audio sans annotation.
    - `height_video` (défaut : `450`) — hauteur du lecteur pour les médias vidéo sans annotation.
    - `height_annotations` (défaut : `150`) — hauteur supplémentaire ajoutée au conteneur lorsqu'au moins une annotation est présente.
- **Texte d'aide (HTML)** : Un champ avec éditeur WYSIWYG (CKEditor) permet de modifier le message d'aide affiché dans le lecteur (par défaut : instructions sur les annotations). Ce texte peut être défini à deux niveaux :
    - **Niveau site** (prioritaire) : champ *"Help text on audio player IIIF (HTML)"* présent dans les **Paramètres du site**, ce qui permet un message différent par site.
    - **Niveau global** : champ `help_text` de la configuration du module, utilisé comme repli si aucun texte n'est défini au niveau du site.
- **Vitesse de lecture** : Configuration des taux de lecture disponibles (ex: `[0.5, 1, 1.5, 2, 4]`).
- **Patterns d'URL** : Définition des modèles d'URL pour récupérer dynamiquement :
    - Les fichiers médias (audio/vidéo) — `media_url_pattern`
    - Les fichiers de forme d'onde (waveform JSON) — `waveform_url_pattern`
    - Les fichiers de sous-titres (API JSON) — `subtitles_url_pattern`
    
    Les patterns utilisent des jetons (ex. `{bibo:locator}`) pour injecter des valeurs issues des métadonnées.
- **Mapping des sous-titres** : Objet JSON (`subtitle_field_mapping`) mettant en correspondance les champs retournés par l'API de sous-titres avec les clés attendues (`url`, `language_code`, `language_label`).
- **Sécurité MMS (signature HMAC)** : Lorsque les médias sont servis par un système tiers (ex. MMS), les URLs peuvent être signées automatiquement :
    - `mms_shared_secret` — clé secrète utilisée pour générer le jeton HMAC. Laisser vide pour désactiver la signature.
    - `mms_app_id` — identifiant d'application inclus dans la charge utile du jeton (défaut : `omekas`).
- **Propriétés de métadonnées** : Configuration des termes de propriétés utilisés pour identifier le format (`format_property`, ex: `dcterms:format`) et la cote (`id_property`, ex: `crem:cote`).
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

### Utilisation dans les thèmes (View Helpers)

Le module fournit des View Helpers pour intégrer le lecteur directement dans les templates de votre thème PHP (`.phtml`).

#### 1. `audioPlayer($media)`
Affiche le lecteur pour un objet média spécifique (`MediaRepresentation`).
```php
<?php echo $this->audioPlayer($media); ?>
```

#### 2. `audioPlayerForItem($itemId)`
Affiche le lecteur pour le média principal d'un item à partir de son identifiant Omeka S.
```php
<?php echo $this->audioPlayerForItem(123); ?>
```
*Note : Si l'item n'est pas trouvé, s'il n'a pas de média principal ou si le média est incompatible, le helper retourne une chaîne vide.*

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

> **Annotation « point »** : pour créer une annotation sur un instant précis (et non une plage), `time_end` peut être omis. Le module reprend alors automatiquement la valeur de `time` comme temps de fin.

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

- Module version : **1.2.3**
- Omeka 4.x (`^4.0.0`)
- PHP 8.3 ou supérieur
- Navigateurs modernes (support des Web Components)

## Notes de version

### v1.2.3 (2026-07-03)
- Hauteurs de lecteur séparées : `height_audio`, `height_video` et `height_annotations` remplacent l'ancien réglage `player_height` unique.
- Texte d'aide configurable au niveau du site (`audioplayer_help_text` dans les *Paramètres du site*), avec CKEditor et repli sur le réglage global.
- Correctif des annotations « point » : `time_end` reprend `time` en cas d'absence (à la création comme à la mise à jour).

### v1.2.2 (2026-06-16)
- Chargement des sous-titres depuis une API externe via `subtitles_url_pattern` (réponse JSON `url` / `language_code`).
- Mapping configurable des champs de l'API de sous-titres (`subtitle_field_mapping`).

### v1.2.0 – v1.2.1 (2026-06-10)
- Correctifs et améliorations du web component (`player-iiif-vis.js`).

### v1.1.0 (2026-05-28)
- Signature HMAC des URLs média (et forme d'onde / sous-titres) via `mms_shared_secret` et `mms_app_id`, pour sécuriser l'accès aux ressources distantes (ex. MMS).

## Licence

GPL v3

## Support

Pour signaler un bug ou demander une fonctionnalité, veuillez créer une issue dans le dépôt du projet.
