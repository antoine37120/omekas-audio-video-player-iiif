# Audio Player Annotation API Usage

This document describes how to interact with the Annotation API in the Audio Player module using JavaScript `fetch`.

**Base URL**: `/api/audio-player/annotation`

## 1. Check Permissions

Check if the current user can create, edit, or delete annotations.

**Endpoint**: `GET /permissions`
**Parameters**: 
- `annotation_id` (optional): ID of the specific annotation to check permissions for.

```javascript
async function checkPermissions(annotationId = null) {
    const url = new URL('/api/audio-player/annotation/permissions', window.location.origin);
    if (annotationId) {
        url.searchParams.append('annotation_id', annotationId);
    }
    
    const response = await fetch(url.toString(), {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });
    
    // Returns { create: bool, edit: bool, delete: bool }
    return await response.json();
}
```

## 2. Create Annotation

Create a new annotation. User must be logged in.

**Endpoint**: `POST /`

```javascript
async function createAnnotation(resourceId, data) {
    const response = await fetch('/api/audio-player/annotation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            resource_id: resourceId,
            time: data.time, // Start time in seconds (float)
            time_end: data.time_end, // End time in seconds (float)
            title: data.title, // Title string
            description: data.description, // Description string
            public_id: data.public_id // Unique string ID
        })
    });

    if (!response.ok) {
        const error = await response.json();
        console.error('Create failed:', error);
        return null;
    }

    // Returns { id: new_db_id, status: 'created' }
    return await response.json();
}
```

## 3. Update Annotation

Update an existing annotation. User must be the author or a global admin.

**Endpoint**: `PUT /{id}`

```javascript
async function updateAnnotation(id, data) {
    const response = await fetch(`/api/audio-player/annotation/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    });

    if (!response.ok) {
        const error = await response.json();
        console.error('Update failed:', error);
        return false;
    }

    return true; // { status: 'updated' }
}
```

## 4. Delete Annotation

Delete an annotation. User must be the author or a global admin.

**Endpoint**: `DELETE /{id}`

```javascript
async function deleteAnnotation(id) {
    const response = await fetch(`/api/audio-player/annotation/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        }
    });

    if (!response.ok) {
        const error = await response.json();
        console.error('Delete failed:', error);
        return false;
    }

    return true; // { status: 'deleted' }
}
```
