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
                $subtitlesListUrl = $this->playerService->getSubtitlesListUrl($media);
            } else {
                $media = null;
            }
        }

        return new ViewModel([
            'media' => $media,
            'mediaUrl' => $mediaUrl,
            'waveformUrl' => $waveformUrl,
            'subtitlesListUrl' => $subtitlesListUrl ?? '',
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
        $accessDenied = false;

        if ($id) {
            // On considère que l'id est celui de l'item.
            // Un item inaccessible (privé pour cet utilisateur) est un cas
            // d'accès refusé ; un item inexistant reste une absence.
            try {
                $item = $this->api->read('items', $id)->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                $item = null;
                if ($this->itemExists($id)) {
                    $accessDenied = true;
                } else {
                    $reasons[] = 'Item non trouvé.';
                }
            }

            if ($item) {
                $media = $this->playerService->getPrimaryMedia($item);
                if (!$media) {
                    if ($this->playerService->itemHasPrimaryMediaInDatabase($item)) {
                        // L'item est visible mais son média principal ne l'est pas.
                        $accessDenied = true;
                    } else {
                        $reasons[] = 'Aucun média principal trouvé pour cet item.';
                    }
                }
            }
        }

        if ($media) {
            $mediaReasons = $this->playerService->getIncompatibilityReasons($media);
            $reasons = array_merge($reasons, $mediaReasons);
            if (empty($mediaReasons)) {
                $mediaUrl = $this->playerService->getMediaUrl($media);
                $waveformUrl = $this->playerService->getWaveformUrl($media);
                $subtitlesListUrl = $this->playerService->getSubtitlesListUrl($media);
            } else {
                $media = null;
            }
        }

        $view = new ViewModel([
            'media' => $media,
            'mediaUrl' => $mediaUrl,
            'waveformUrl' => $waveformUrl,
            'subtitlesListUrl' => $subtitlesListUrl ?? '',
            'reasons' => $reasons,
            'accessDenied' => $accessDenied,
            'isDebug' => $this->playerService->isDebug(),
            'playerService' => $this->playerService,
        ]);

        // Désactive le layout par défaut d'Omeka S pour n'avoir que le contenu du template
        $view->setTerminal(true);

        return $view;
    }

    /**
     * Indique si l'item existe en base, indépendamment des droits de l'utilisateur.
     * Permet de distinguer « refusé » de « inexistant », l'API confondant les deux.
     */    protected function itemExists($id)
    {
        $connection = $this->getEvent()->getApplication()
            ->getServiceManager()
            ->get('Omeka\Connection');

        return (bool) $connection->fetchOne(
            'SELECT 1 FROM `resource` WHERE id = ?',
            [$id]
        );
    }
}
