<?php
namespace AudioPlayer\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\MediaRepresentation;
use AudioPlayer\Service\PlayerService;

class AudioPlayer extends AbstractHelper
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
     * Render the audio player for a given media resource
     *
     * @param MediaRepresentation|null $media
     * @return string
     */
    public function __invoke(?MediaRepresentation $media = null)
    {
        if (!$media) {
            return '';
        }

        $reasons = $this->playerService->getIncompatibilityReasons($media);

        // Condition checking based on dcterms:format and crem:cote
        if (empty($reasons)) {
            return $this->getView()->partial('common/audio-video-player', [
                'media' => $media,
                'mediaUrl' => $this->playerService->getMediaUrl($media),
                'waveformUrl' => $this->playerService->getWaveformUrl($media),
                'subtitlesJson' => $this->playerService->getSubtitlesJson($media),
                'playerService' => $this->playerService,
            ]);
        }

        // If debug mode is enabled, display the reasons
        if ($this->playerService->isDebug()) {
            $html = '<div class="audio-player-debug alert alert-warning">';
            $html .= '<p><strong>' . $this->getView()->translate('AudioPlayer Debug (View Helper):') . '</strong></p>';
            $html .= '<ul>';
            foreach ($reasons as $reason) {
                $html .= '<li>' . $this->getView()->escapeHtml($this->getView()->translate($reason)) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
            return $html;
        }

        return '';
    }
}