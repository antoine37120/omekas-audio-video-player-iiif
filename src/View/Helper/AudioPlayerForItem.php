<?php
namespace AudioPlayer\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use AudioPlayer\Service\PlayerService;

class AudioPlayerForItem extends AbstractHelper
{
    /**
     * @var PlayerService
     */
    protected $playerService;

    public function __construct(PlayerService $playerService)
    {
        $this->playerService = $playerService;
    }

    /**
     * Render the audio player for a given item ID
     *
     * @param mixed $itemId
     * @return string
     */
    public function __invoke($itemId)
    {
        if (empty($itemId)) {
            return '';
        }

        $response = $this->getView()->api()->read('items', $itemId);
        if (!$response) {
            return '';
        }
        $item = $response->getContent();
        if (!$item) {
            return '';
        }

        $media = $this->playerService->getPrimaryMedia($item);
        if (!$media) {
            return '';
        }

        $reasons = $this->playerService->getIncompatibilityReasons($media);
        if (!empty($reasons)) {
            return '';
        }

        $mediaUrl = $this->playerService->getMediaUrl($media);
        $waveformUrl = $this->playerService->getWaveformUrl($media);
        $subtitlesJson = $this->playerService->getSubtitlesJson($media);

        return $this->getView()->partial('common/audio-video-player', [
            'media' => $media,
            'mediaUrl' => $mediaUrl,
            'waveformUrl' => $waveformUrl,
            'subtitlesJson' => $subtitlesJson,
            'playerService' => $this->playerService,
        ]);
    }
}
