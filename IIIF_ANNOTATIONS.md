# IIIF Annotations pour AudioPlayer

## Description

Le module AudioPlayer fournit maintenant un endpoint pour récupérer les annotations d'un média au format IIIF (International Image Interoperability Framework) Presentation API 3.0.

## URL de l'endpoint

```
/s/{site-slug}/audio-player/annotation/iiif/{media-id}
```

### Paramètres

- `{site-slug}` : Le slug du site Omeka S
- `{media-id}` : L'ID du média pour lequel récupérer les annotations

## Exemple d'utilisation

```
https://votre-site.com/s/mon-site/audio-player/annotation/iiif/123
```

## Format de réponse

Le endpoint retourne un document JSON-LD conforme à IIIF Presentation API 3.0 :

```json
{
  "@context": "http://iiif.io/api/presentation/3/context.json",
  "id": "https://votre-site.com/s/mon-site/audio-player/annotation/iiif/123",
  "type": "AnnotationPage",
  "items": [
    {
      "@context": "http://www.w3.org/ns/anno.jsonld",
      "id": "annotation-public-id",
      "type": "Annotation",
      "motivation": "commenting",
      "body": {
        "type": "TextualBody",
        "value": "Description de l'annotation",
        "format": "text/plain",
        "language": "fr",
        "label": "Titre de l'annotation"
      },
      "target": "https://url-du-media.mp3#t=10.5,25.3",
      "created": "2025-11-26 18:00:00",
      "creator": {
        "id": "user:42",
        "type": "Person",
        "name": "Jean Dupont",
        "label": {
          "none": ["Jean Dupont"]
        }
      }
    }
  ]
}
```

## Détails techniques

### Types d'annotations

- **Annotations de point** : Lorsque `time` == `time_end`, le fragment temporel est `#t={time}`
- **Annotations de plage** : Lorsque `time` != `time_end`, le fragment temporel est `#t={time},{time_end}`

### Champs de la base de données

Les données sont récupérées depuis la table `media_markers` :

- `id` : ID interne de l'annotation
- `resource_id` : ID du média (utilisé pour filtrer)
- `public_id` : ID public de l'annotation (utilisé comme `id` dans IIIF)
- `time` : Temps de début (en secondes)
- `time_end` : Temps de fin (en secondes)
- `title` : Titre de l'annotation (optionnel)
- `description` : Description de l'annotation
- `date` : Date de création (optionnel)
- `author_id` : ID de l'auteur (optionnel)

### Content-Type

Le endpoint retourne le content-type approprié pour IIIF :

```
Content-Type: application/ld+json;profile="http://iiif.io/api/presentation/3/context.json"
```

## Intégration dans votre application

### JavaScript

```javascript
// Récupérer les annotations IIIF pour un média
fetch('/s/mon-site/audio-player/annotation/iiif/123')
  .then(response => response.json())
  .then(data => {
    console.log('Annotations IIIF:', data);
    // Traiter les annotations
    data.items.forEach(annotation => {
      console.log('Annotation:', annotation.body.value);
      console.log('Target:', annotation.target);
    });
  });
```

### Utilisation avec des bibliothèques IIIF

Ce format est compatible avec les bibliothèques JavaScript qui supportent IIIF, comme :

- [Mirador](https://projectmirador.org/)
- [Universal Viewer](https://universalviewer.io/)
- [Annona](https://ncsu-libraries.github.io/annona/)

## Gestion des erreurs

### 400 Bad Request
```json
{
  "error": "Missing media ID"
}
```

### 404 Not Found
```json
{
  "error": "Media not found"
}
```

## Notes

- Les annotations sont triées par ordre chronologique (`time ASC`)
- La langue par défaut est définie sur `fr` (français)
- Le champ `id` de l'AnnotationPage est automatiquement généré avec l'URL complète
