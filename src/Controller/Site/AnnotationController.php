<?php
namespace AudioPlayer\Controller\Site;

use AudioPlayer\Service\AnnotationService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class AnnotationController extends AbstractActionController
{
    /**
     * @var AnnotationService
     */
    protected $service;

    public function __construct(AnnotationService $service)
    {
        $this->service = $service;
    }

    public function indexAction()
    {
        $mediaId = $this->params()->fromRoute('id');
        $siteSlug = $this->params()->fromRoute('site-slug');
        if (!$mediaId) {
            return $this->returnJson(['error' => 'Missing resource_id parameter']);
        }

        $mediaUrl = $this->getMediaUrl($mediaId);
        $permissionsCallback = function($publicId, $annotation) {
            return $this->checkPermissions($publicId, $annotation);
        };

        $iiifData = $this->service->convertToIIIF($mediaId, $mediaUrl, $permissionsCallback);

        $iiifData['controller'] = 'site' ;
        // Set the ID to the current request URL
        $iiifData['id'] = $this->url()->fromRoute(
            'site/audio-player/annotation-iiif',
            ['id' => $mediaId, 'site-slug' => $siteSlug],
            ['force_canonical' => true]
        );
        // Set proper content type for IIIF
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/ld+json;profile="http://iiif.io/api/presentation/3/context.json"');


        return $this->returnJson($iiifData);
    }

    public function getAction()
    {
        // On récupère le paramètre 'id' défini dans module.config.php
        $publicId = $this->params()->fromRoute('id');

        error_log('query GET: ' . ($publicId ? $publicId : 'none'));

        // Vous devez avoir une méthode dans votre service qui cherche par public_id
        $annotation = $this->service->readByPublicId($publicId);
        if (!$annotation) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnJson(['error' => 'Annotation not found']);
        }

        $mediaUrl = $this->getMediaUrl($annotation['resource_id']);
        $permissionsCallback = function($publicId, $annotation) {
            return $this->checkPermissions($publicId, $annotation);
        };

        $iiifAnnotation = $this->service->mapToIIIF($annotation, $mediaUrl, $permissionsCallback);

        // Set proper content type for IIIF
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/ld+json;profile="http://iiif.io/api/presentation/3/context.json"');

        return $this->returnJson($iiifAnnotation);
    }


    /**
     * Check permissions for the current user.
     *
     * @param int|null $annotationId
     * @param array $annotation
     * @return array
     */
    protected function checkPermissions($annotationId = null, $annotation = null)
    {
        $user = $this->identity();
        // Si identity() est vide, on tente de forcer la lecture via le service global
        if (!$user) {
            $auth = $this->getEvent()->getApplication()->getServiceManager()->get('Omeka\AuthenticationService');
            if ($auth->hasIdentity()) {
                $user = $auth->getIdentity();
            }
        }
        error_log('User identity: ' . ($user ? $user->getEmail() : 'none'));
        // Default permissions
        $permissions = [
            'create' => false,
            'edit' => false,
            'delete' => false,
            'role' => $user ? $user->getRole() : null
        ];

        if (!$user) {
            return $permissions;
        }

        // Check global admin role
        $isGlobalAdmin = ($user->getRole() === 'global_admin');

        // Create: User must be logged in (which is true here)
        $permissions['create'] = true;

        // Fetch annotation if ID provided but data not loaded
        if ($annotationId && !$annotation) {
            $annotation = $this->service->readByPublicId($annotationId);
        }

        if ($annotation) {
            $isAuthor = ($annotation['author_id'] == $user->getId());

            // Edit/Delete: User must be author OR global_admin
            if ($isGlobalAdmin || $isAuthor) {
                $permissions['edit'] = true;
                $permissions['delete'] = true;
            }
        } elseif ($annotationId && !$annotation) {
            // Annotation not found, permissions remain false for edit/delete
        } else {
            // No specific annotation checked, but user is admin so they technically *could* edit/delete any
            if ($isGlobalAdmin) {
                // This might be too broad for a specific ID check, but for general 'can I edit?' query it's true
                $permissions['edit'] = true;
                $permissions['delete'] = true;
            }
        }

        return $permissions;
    }


    public function createAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setStatusCode(405);
            return $this->returnJson(['error' => 'Method not allowed']);
        }
        $data = json_decode($this->getRequest()->getContent(), true);
        
        if (!isset($data['resource_id']) || !isset($data['time']) || !isset($data['title'])) {
             $this->getResponse()->setStatusCode(400);
             return $this->returnJson(['error' => 'Missing required fields']);
        }

        // Vérification des droits
        $permissions = $this->checkPermissions(null, null);
        if (!$permissions['create']) {
            $this->getResponse()->setStatusCode(403);
            return $this->returnJson(['error' => 'Permission denied']);
        }
        $user = $this->identity();
        $data['author_id'] = $user->getId() ;
        $id = $this->service->create($data);
        
        // Fetch created annotation to return it in IIIF format
        $annotation = $this->service->read($id);
        $mediaUrl = $this->getMediaUrl($annotation['resource_id']);
        $permissionsCallback = function($id, $annotation) {
            return $this->checkPermissions($id, $annotation);
        };

        $iiifAnnotation = $this->service->mapToIIIF($annotation, $mediaUrl, $permissionsCallback);


        // Set proper content type for IIIF
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/ld+json;profile="http://iiif.io/api/presentation/3/context.json"');

        return $this->returnJson($iiifAnnotation);
    }

    public function updateAction()
    {
        if (!$this->getRequest()->isPut() && !$this->getRequest()->isPost()) {
             $this->getResponse()->setStatusCode(405);
             return $this->returnJson(['error' => 'Method not allowed']);
        }

        // On récupère explicitement la valeur du segment défini dans la route
        $publicId = $this->params()->fromRoute('id');

        $data = json_decode($this->getRequest()->getContent(), true);

        // On vérifie si l'annotation existe avant de tenter l'update
        $annotation = $this->service->readByPublicId($publicId);
        if (!$annotation) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnJson(['error' => 'Annotation not found for ID: ' . $publicId]);
        }

        // Vérification des droits
        $permissions = $this->checkPermissions($publicId, $annotation);
        if (!$permissions['edit']) {
            $this->getResponse()->setStatusCode(403);
            return $this->returnJson(['error' => 'Permission denied']);
        }

        $success = $this->service->update($publicId, $data);
        
        // Fetch updated annotation to return it in IIIF format
        $annotation = $this->service->readByPublicId($publicId);
        $mediaUrl = $this->getMediaUrl($annotation['resource_id']);
        $permissionsCallback = function($id, $annotation) {
            return $this->checkPermissions($id, $annotation);
        };

        $iiifAnnotation = $this->service->mapToIIIF($annotation, $mediaUrl, $permissionsCallback);
        // Set proper content type for IIIF
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/ld+json;profile="http://iiif.io/api/presentation/3/context.json"');

        return $this->returnJson($iiifAnnotation);
    }

    public function deleteAction()
    {
        if (!$this->getRequest()->isDelete() && !$this->getRequest()->isPost()) {
             $this->getResponse()->setStatusCode(405);
             return new JsonModel(['error' => 'Method not allowed']);
        }
        $publicId = $this->params()->fromRoute('id');

        // Vérification de l'existence et des droits
        $annotation = $this->service->readByPublicId($publicId);
        if (!$annotation) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnJson(['error' => 'Annotation not found']);
        }

        $permissions = $this->checkPermissions($publicId, $annotation);
        if (!$permissions['delete']) {
            $this->getResponse()->setStatusCode(403);
            return $this->returnJson(['error' => 'Permission denied']);
        }

        $this->service->delete($publicId);
        return $this->returnJson(['status' => 'deleted']);
    }

    /**
     * Get annotations in IIIF format for a media resource
     * URL: /s/{site-slug}/audio-player/annotation/iiif/{media-id}
     */
    public function iiifAction()
    {
        $mediaId = $this->params()->fromRoute('id');
        $siteSlug = $this->params()->fromRoute('site-slug');

        if (!$mediaId) {
            $this->getResponse()->setStatusCode(400);
            return $this->returnJson(['error' => 'Missing media ID']);
        }

        // Get the media entity to retrieve its URL
        $api = $this->getPluginManager()->get('api');
        try {
            $media = $api->read('media', $mediaId)->getContent();
            // Vérifier si le média est public
            if (!$media->isPublic()) {
                $this->getResponse()->setStatusCode(403);
                return $this->returnJson(['error' => 'Media is not public']);
            }
            $mediaUrl = $media->originalUrl();
        } catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnJson(['error' => 'Media not found']);
        }


        // Convert annotations to IIIF format with user permissions
        $permissionsCallback = function($id, $annotation) {
            return $this->checkPermissions($id, $annotation);
        };
        $iiifData = $this->service->convertToIIIF($mediaId, $mediaUrl, $permissionsCallback);

        $iiifData['controller'] = 'site' ;
        // Set the ID to the current request URL
        $iiifData['id'] = $this->url()->fromRoute(
            'site/audio-player/annotation-iiif',
            ['id' => $mediaId, 'site-slug' => $siteSlug],
            ['force_canonical' => true]
        );
        // Set proper content type for IIIF
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/ld+json;profile="http://iiif.io/api/presentation/3/context.json"');

        return $this->returnJson($iiifData);
    }

    /**
     * Get media URL for a resource ID
     */
    protected function getMediaUrl($mediaId)
    {
        $api = $this->getPluginManager()->get('api');
        try {
            $media = $api->read('media', $mediaId)->getContent();
            return $media->originalUrl();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Helper method to return JSON response without layout
     */
    protected function returnJson(array $data)
    {
        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        return $response;
    }
}