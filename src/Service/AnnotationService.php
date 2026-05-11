<?php
namespace AudioPlayer\Service;

use Omeka\Connection\Connection;

class AnnotationService
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct($connection)
    {
        if ($connection instanceof \Omeka\Connection\Connection) {
            $this->connection = $connection->getWrappedConnection();
        } elseif ($connection instanceof \Doctrine\DBAL\Connection) {
            $this->connection = $connection;
        } else {
            throw new \InvalidArgumentException('Invalid connection type provided');
        }
    }

    public function readByPublicId($publicId)
    {
        $sql = 'SELECT media_markers.*, user.name as author_name 
                FROM media_markers 
                LEFT JOIN user ON media_markers.author_id = user.id 
                WHERE media_markers.public_id = ?';
        return $this->connection->fetchAssociative($sql, [$publicId]);
    }

    public function create(array $data)
    {
        // Générer un public_id unique s'il n'est pas fourni
        $publicId = $data['public_id'] ?? bin2hex(random_bytes(16));
        $description = $data['description'] ?? $data['text'] ?? '';

        $sql = 'INSERT INTO media_markers (resource_id, public_id, time, time_end, title, date, description, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $params = [
            $data['resource_id'],
            $publicId,
            $data['time'],
            $data['end_time'],
            $data['title'],
            $data['date'] ?? null,
            $description,
            $data['author_id'] ?? null
        ];
        $this->connection->executeStatement($sql, $params);
        return $this->connection->lastInsertId();
    }

    public function read($id)
    {
        $sql = 'SELECT media_markers.*, user.name as author_name 
                FROM media_markers 
                LEFT JOIN user ON media_markers.author_id = user.id 
                WHERE media_markers.id = ?';
        return $this->connection->fetchAssociative($sql, [$id]);
    }

    public function update($id, array $data)
    {
        $sets = [];
        if (isset ($data['end_time'])) {
            $data  ['time_end'] = $data['end_time'];
        }
        $params = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['resource_id', 'public_id', 'time', 'time_end', 'title', 'date', 'description', 'author_id'])) {
                $sets[] = "`$key` = ?";
                $params[] = $value;
            }
        }
        
        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE media_markers SET ' . implode(', ', $sets) . ' WHERE public_id = ?';
        $this->connection->executeStatement($sql, $params);
        return true;
    }

    public function delete($id)
    {
        $sql = 'DELETE FROM media_markers WHERE public_id = ?';
        $this->connection->executeStatement($sql, [$id]);
    }

    public function findByResource($resourceId)
    {
        $sql = 'SELECT media_markers.*, user.name as author_name 
                FROM media_markers 
                LEFT JOIN user ON media_markers.author_id = user.id 
                WHERE media_markers.resource_id = ? 
                ORDER BY media_markers.time ASC';
        return $this->connection->fetchAllAssociative($sql, [$resourceId]);
    }

    /**
     * Search annotations.
     * 
     * @param array $criteria
     * @param int $page
     * @param int $perPage
     * @return array ['items' => [], 'total_count' => int]
     */
    public function search(array $criteria = [], $page = 1, $perPage = 10)
    {
        $select = 'SELECT media_markers.*, user.name as author_name';
        $from = 'FROM media_markers LEFT JOIN user ON media_markers.author_id = user.id';
        $where = ['1 = 1'];
        $params = [];

        if (!empty($criteria['q'])) {
            $where[] = '(media_markers.title LIKE ? OR media_markers.description LIKE ?)';
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
        }

        if (!empty($criteria['resource_id'])) {
            $where[] = 'media_markers.resource_id = ?';
            $params[] = $criteria['resource_id'];
        }

        if (!empty($criteria['author_id'])) {
            $where[] = 'media_markers.author_id = ?';
            $params[] = $criteria['author_id'];
        }

        $whereClause = implode(' AND ', $where);
        
        $offset = ($page - 1) * $perPage;

        // Count query
        $countSql = "SELECT COUNT(*) $from WHERE $whereClause";
        $stmt = $this->connection->executeQuery($countSql, $params);
        $totalCount = (int) $stmt->fetchOne();

        // Data query
        $dataSql = "$select $from WHERE $whereClause ORDER BY media_markers.date DESC LIMIT $perPage OFFSET $offset";
        $items = $this->connection->fetchAllAssociative($dataSql, $params);

        return [
            'items' => $items,
            'total_count' => $totalCount,
        ];
    }

    public function findAll()
    {
        return $this->search();
    }

    /**
     * Convert a single annotation to IIIF Presentation API 3.0 format
     * 
     * @param array $annotation
     * @param string $mediaUrl The URL of the media file
     * @param callable|null $permissionsCallback Callback to add user permissions to each item
     * @return array IIIF-compliant annotation
     */
    public function mapToIIIF(array $annotation, $mediaUrl = '', $permissionsCallback = null)
    {
        $startTime = (float)$annotation['time'];
        $endTime = (float)$annotation['time_end'];
        
        // Build the target with media fragment
        $target = $mediaUrl;
        if ($startTime == $endTime) {
            // Point annotation
            $target .= "#t={$startTime}";
        } else {
            // Range annotation
            $target .= "#t={$startTime},{$endTime}";
        }
        
        $iiifAnnotation = [
            '@context' => 'http://www.w3.org/ns/anno.jsonld',
            'id' => $annotation['public_id'],
            'type' => 'Annotation',
            'motivation' => 'commenting',
            'body' => [
                'type' => 'TextualBody',
                'value' => $annotation['description'],
                'format' => 'text/plain',
                'language' => 'fr'
            ],
            'target' => $target
        ];

        // Add permissions if callback provided
        if ($permissionsCallback) {
            $iiifAnnotation['omeka:permissions'] = $permissionsCallback($annotation['public_id'], $annotation);
        }
        
        // Add optional fields if present
        if (!empty($annotation['title'])) {
            $iiifAnnotation['body']['label'] = $annotation['title'];
        }
        
        if (!empty($annotation['date'])) {
            $iiifAnnotation['created'] = $annotation['date'];
        }
        
        if (!empty($annotation['author_id'])) {
            $creator = [
                'id' => 'user:' . $annotation['author_id'],
                'type' => 'Person'
            ];
            
            if (!empty($annotation['author_name'])) {
                $creator['name'] = $annotation['author_name'];
                $creator['label'] = [
                    'none' => [$annotation['author_name']]
                ];
            }
            
            $iiifAnnotation['creator'] = $creator;
        }
        
        return $iiifAnnotation;
    }

    /**
     * Convert annotations to IIIF Presentation API 3.0 format
     * 
     * @param int $resourceId The media resource ID
     * @param string $mediaUrl The URL of the media file
     * @param callable|null $permissionsCallback Callback to add user permissions to each item
     * @return array IIIF-compliant annotation page
     */
    public function convertToIIIF($resourceId, $mediaUrl = '', $permissionsCallback = null)
    {
        $annotations = $this->findByResource($resourceId);
        
        $iiifAnnotations = [];
        foreach ($annotations as $annotation) {
            $iiifAnnotations[] = $this->mapToIIIF($annotation, $mediaUrl, $permissionsCallback);
        }
        
        return [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => '', // Will be set by controller with full URL
            'type' => 'AnnotationPage',
            'items' => $iiifAnnotations
        ];
    }
}
