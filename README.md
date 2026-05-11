# AudioPlayer Module pour Omeka S

Module Omeka S pour la lecture audio/vidéo avec support des annotations temporelles au format IIIF.

## Fonctionnalités

- **Lecteur Audio/Vidéo** : Affichage d'un lecteur pour les médias audio et vidéo
- **Annotations temporelles** : Création et gestion d'annotations avec marqueurs temporels
- **Export IIIF** : Export des annotations au format IIIF Presentation API 3.0
- **API REST** : API complète pour la gestion des annotations (CRUD)
- **Interface d'Administration** : Tableau de bord pour gérer toutes les annotations.

## Interface d'Administration

Le module propose une interface d'administration dédiée pour gérer les annotations :

1. Accédez à l'administration d'Omeka S.
2. Dans le menu de navigation (section Modules/Global), cliquez sur **Audio Player Annotations**.
3. Vous pouvez :
    - **Lister** toutes les annotations (titre, description, temps, auteur, ressource).
    - **Filtrer** par titre/contenu, ID de ressource ou ID d'auteur.
    - **Paginer** les résultats avec choix du nombre d'éléments par page (5, 10, 25, 50, 100).
    - **Supprimer** des annotations.

## Installation

1. Copiez le dossier `AudioPlayer` dans le répertoire `modules` de votre installation Omeka S
2. Activez le module depuis l'interface d'administration
3. Le module créera automatiquement la table `media_markers` dans la base de données

## Structure de la base de données

### Table `media_markers`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | int(11) | ID auto-incrémenté |
| `resource_id` | int(11) | ID du média Omeka S |
| `public_id` | varchar(250) | Identifiant public de l'annotation |
| `time` | double | Temps de début (secondes) |
| `time_end` | double | Temps de fin (secondes) |
| `title` | varchar(250) | Titre de l'annotation |
| `date` | datetime | Date de création |
| `description` | longtext | Description de l'annotation |
| `author_id` | int(11) | ID de l'auteur |

## API REST

Le module expose deux types d'API :

### 1. API Site (Contextualisée au site)
Base URL : `/s/{site-slug}/audio-player/annotation`
Utilisée pour l'affichage *frontend* des annotations sur un site spécifique.

### 2. API Globale (Administration/App externe)
Base URL : `/api/audio-player/annotation`
Utilisée pour la gestion *backend* ou des applications tierces.

#### Endpoints API Globale

- **GET /permissions** : Vérifier les permissions de l'utilisateur courant.
- **POST /** : Créer une annotation (Auth requise).
- **PUT /{id}** : Modifier une annotation (Auteur ou Admin seulement).
- **DELETE /{id}** : Supprimer une annotation (Auteur ou Admin seulement).
- **GET /iiif/{media-id}** : Export IIIF pour un média donné.

Example de corps JSON pour POST/PUT :
```json
{
  "resource_id": 123,
  "public_id": "uuid-...",
  "time": 10.5,
  "time_end": 20.0,
  "title": "Titre",
  "description": "Contenu",
  "author_id": 1
}
```

Voir [IIIF_ANNOTATIONS.md](IIIF_ANNOTATIONS.md) pour plus de détails sur le format IIIF.

## Utilisation dans les pages de ressources

Le module fournit un bloc de mise en page pour les pages de ressources :

1. Allez dans l'administration du site
2. Configurez les pages de ressources (Thème > Pages de ressources)
3. Ajoutez le bloc "Lecteur Audio/Vidéo"

## Structure du code

```
AudioPlayer/
├── Module.php
├── config/
│   └── module.config.php
├── src/
│   ├── Controller/
│   │   ├── Admin/
│   │   │   ├── AnnotationController.php        # Contrôleur Admin (Dashboard)
│   │   │   └── AnnotationControllerFactory.php
│   │   ├── Api/
│   │   │   ├── AnnotationController.php        # Contrôleur API Global
│   │   │   └── AnnotationControllerFactory.php
│   │   └── Site/
│   │       ├── AnnotationController.php        # Contrôleur Site
│   │       ├── AnnotationControllerFactory.php
│   │       ├── PlayerController.php
│   │       └── PlayerControllerFactory.php
│   ├── Paginator/
│   │   └── Adapter/
│   │       └── Callback.php                   # Adaptateur de pagination custom
│   ├── Service/
│   │   ├── AnnotationService.php              # Logique métier (Search, CRUD, IIIF)
│   │   ├── AnnotationServiceFactory.php
│   │   └── PlayerService.php
│   ├── Site/
│   │   └── ResourcePageBlockLayout/
│   └── View/
│       └── Helper/
├── view/
│   ├── audio-player/
│   │   ├── admin/
│   │   │   └── annotation/
│   │   │       └── index.phtml                # Vue Dashboard Admin
│   │   └── site/
└── asset/
```

## Développement

### Ajouter une nouvelle fonctionnalité

1. Modifiez `AnnotationService.php` pour ajouter la logique métier
2. Ajoutez une action dans le Contrôleur approprié (Admin, Api, ou Site)
3. Configurez la route dans `module.config.php`

### Format IIIF

Le module utilise IIIF Presentation API 3.0 avec le modèle W3C Web Annotation :

- **AnnotationPage** : Conteneur pour les annotations
- **Annotation** : Chaque annotation individuelle
- **TextualBody** : Corps textuel de l'annotation
- **Media Fragment** : Cible temporelle (`#t=start,end`)

## Compatibilité

- Omeka S 3.x ou supérieur
- PHP 7.4 ou supérieur
- MySQL/MariaDB

## Licence

[À définir]

## Support

Pour signaler un bug ou demander une fonctionnalité, veuillez créer une issue dans le dépôt du projet.
