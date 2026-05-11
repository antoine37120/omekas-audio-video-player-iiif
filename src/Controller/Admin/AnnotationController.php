<?php
namespace AudioPlayer\Controller\Admin;

use AudioPlayer\Service\AnnotationService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

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
        $params = $this->params()->fromQuery();
        $page = (int) $this->params()->fromQuery('page', 1);
        $perPage = (int) $this->params()->fromQuery('per_page', 5);

        $result = $this->service->search($params, $page, $perPage);

        $this->paginator($result['total_count'], $page, $perPage); // Populates the view helper

        return new ViewModel([
            'annotations' => $result['items'],
            'params' => $params,
            'perPage' => $perPage,
        ]);
    }

    public function deleteAction()
    {
        $id = $this->params()->fromRoute('id');
        if ($id) {
            $this->service->delete($id);
            $this->messenger()->addSuccess('Annotation deleted successfully.');
        }
        
        return $this->redirect()->toRoute('admin/audio-player');
    }
}
