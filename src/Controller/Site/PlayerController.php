<?php
namespace AudioPlayer\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Api\Manager;
use AudioPlayer\Service\PlayerService;

class PlayerController extends AbstractActionController
{
    protected $api;
    protected $playerService;

    public function __construct(Manager $api, PlayerService $playerService)
    {
        $this->api = $api;
        $this->playerService = $playerService;
    }

    public function showAction()
    {
        $itemId = $this->params()->fromQuery('item_id');
        $media = null;
        $mediaUrl = '';
        $waveformUrl = '';

        $reasons = [];

        if ($itemId) {
            $response = $this->api->read('items', $itemId);
            $item = $response->getContent();
            
            if ($item) {
                $media = $this->playerService->getPrimaryMedia($item);
                if (!$media) {
                    $reasons[] = 'Aucun média principal trouvé pour cet item.';
                }
            } else {
                $reasons[] = 'Item non trouvé.';
            }
        }

        if ($media) {
            $mediaReasons = $this->playerService->getIncompatibilityReasons($media);
            $reasons = array_merge($reasons, $mediaReasons);
            if (empty($mediaReasons)) {
                $mediaUrl = $this->playerService->getMediaUrl($media);
                $waveformUrl = $this->playerService->getWaveformUrl($media);
                $subtitlesJson = $this->playerService->getSubtitlesJson($media);
            } else {
                $media = null;
            }
        }

        return new ViewModel([
            'media' => $media,
            'mediaUrl' => $mediaUrl,
            'waveformUrl' => $waveformUrl,
            'subtitlesJson' => $subtitlesJson ?? '[]',
            'reasons' => $reasons,
            'isDebug' => $this->playerService->isDebug(),
            'playerService' => $this->playerService,
        ]);
    }

    public function embedAction()
    {
        $id = $this->params()->fromRoute('id');
        $media = null;
        $mediaUrl = '';
        $waveformUrl = '';

        $reasons = [];

        if ($id) {
            // On considère que l'id est celui de l'item
            $response = $this->api->read('items', $id);
            $item = $response->getContent();
            if ($item) {
                $media = $this->playerService->getPrimaryMedia($item);
                if (!$media) {
                    $reasons[] = 'Aucun média principal trouvé pour cet item.';
                }
            } else {
                $reasons[] = 'Item non trouvé.';
            }
        }

        if ($media) {
            $mediaReasons = $this->playerService->getIncompatibilityReasons($media);
            $reasons = array_merge($reasons, $mediaReasons);
            if (empty($mediaReasons)) {
                $mediaUrl = $this->playerService->getMediaUrl($media);
                $waveformUrl = $this->playerService->getWaveformUrl($media);
                $subtitlesJson = $this->playerService->getSubtitlesJson($media);
            } else {
                $media = null;
            }
        }

        $view = new ViewModel([
            'media' => $media,
            'mediaUrl' => $mediaUrl,
            'waveformUrl' => $waveformUrl,
            'subtitlesJson' => $subtitlesJson ?? '[]',
            'reasons' => $reasons,
            'isDebug' => $this->playerService->isDebug(),
            'playerService' => $this->playerService,
        ]);

        // Désactive le layout par défaut d'Omeka S pour n'avoir que le contenu du template
        $view->setTerminal(true);

        return $view;
    }
}
