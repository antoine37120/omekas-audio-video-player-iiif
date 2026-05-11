<?php
namespace AudioPlayer\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;
use Laminas\View\Renderer\PhpRenderer;
use AudioPlayer\Service\PlayerService;

class AudioPlayerResourcePageBlockLayout implements ResourcePageBlockLayoutInterface
{
    /**
     * @var PlayerService
     */
    protected $playerService;

    public function __construct(PlayerService $playerService)
    {
        $this->playerService = $playerService;
    }

    public function getLabel(): string
    {
        return 'Lecteur Audio/Vidéo module custom'; // @translate
    }

    public function getCompatibleResourceNames(): array
    {
        return ['items', 'media'];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        $media = null;
        $reasons = [];

        // If the resource is an item, get its primary media
        if ($resource->resourceName() === 'items') {
            $media = $this->playerService->getPrimaryMedia($resource);
            if (!$media) {
                $reasons[] = 'Aucun média principal (audio ou vidéo) trouvé pour cet item.';
            }
        } elseif ($resource->resourceName() === 'media') {
            // If the resource is already a media, use it directly
            $media = $resource;
        }

        if ($media) {
            $mediaReasons = $this->playerService->getIncompatibilityReasons($media);
            $reasons = array_merge($reasons, $mediaReasons);
        }

        // Check if we should display the player or debug info
        if (empty($reasons) && $media) {
            return $view->partial('audio-player/site/resource-page-block-layout', [
                'media' => $media,
                'resource' => $resource,
            ]);
        }

        // If debug mode is enabled, display the reasons
        if ($this->playerService->isDebug() && !empty($reasons)) {
            return $view->partial('audio-player/site/resource-page-block-layout', [
                'media' => null,
                'resource' => $resource,
                'reasons' => $reasons,
            ]);
        }

        return '';
    }
}
