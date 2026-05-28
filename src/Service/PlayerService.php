<?php
namespace AudioPlayer\Service;

use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Settings\Settings;
use Laminas\Http\Client;
use Laminas\Http\Exception\RuntimeException;
use Laminas\Http\Client\Exception\RuntimeException as HttpClientRuntimeException;

class PlayerService
{
    /**
     * @var Settings
     */
    protected $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return (bool) $this->settings->get('audioplayer_debug_display', false);
    }

    /**
     * Check if a resource is of type audio or video based on dcterms:format and crem:cote
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return bool
     */
    public function isAudioVideo(AbstractResourceEntityRepresentation $resource): bool
    {
        return empty($this->getIncompatibilityReasons($resource));
    }

    /**
     * Get the reasons why a resource is not considered a valid audio/video resource
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array
     */
    public function getIncompatibilityReasons(AbstractResourceEntityRepresentation $resource): array
    {
        $reasons = [];
        $formatProperty = $this->settings->get('audioplayer_format_property', 'dcterms:format');
        $idProperty = $this->settings->get('audioplayer_id_property', 'crem:cote');

        $formats = $resource->value($formatProperty, ['all' => true]);
        $idPropertyValue = $resource->value($idProperty);

        $allowedFormats = ['audio', 'video', 'vidéo', 'sound', 'moving image'];

        if (empty($formats)) {
            $reasons[] = sprintf('La propriété de format (%s) est manquante.', $formatProperty);
        } else {
            $foundMatch = false;
            foreach ($formats as $format) {
                $value = strtolower(trim((string) $format));
                if (in_array($value, $allowedFormats) 
                    || strpos($value, 'audio/') === 0 
                    || strpos($value, 'video/') === 0
                ) {
                    $foundMatch = true;
                    break;
                }
            }
            if (!$foundMatch) {
                $formatValues = array_map(function($f) { return (string) $f; }, $formats);
                $reasons[] = sprintf('Le format trouvé (%s) ne correspond pas aux formats attendus (%s).', 
                    implode(', ', $formatValues), 
                    implode(', ', $allowedFormats)
                );
            }
        }

        if (empty($idPropertyValue)) {
            $reasons[] = sprintf('La propriété d\'ID (%s) est manquante ou vide.', $idProperty);
        }

        return $reasons;
    }

    /**
     * Get the resource code from the configured ID property.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return string
     */
    private function getResourceCode(AbstractResourceEntityRepresentation $resource): string
    {
        $idProperty = $this->settings->get('audioplayer_id_property', 'crem:cote');
        $value = $resource->value($idProperty);
        return $value ? (string) $value : '';
    }

    /**
     * Generate a signed HMAC token for MMS media access.
     *
     * @param string $code The item code (crem:cote)
     * @return string|null The token string or null if secret is not configured
     */
    public function generateToken(string $code): ?string
    {
        $secret = $this->settings->get('audioplayer_mms_shared_secret', '');
        if (empty($secret)) {
            return null;
        }
        $payload = json_encode([
            'app' => 'omekas',
            'code' => $code,
            'exp' => time() + 3600,
        ]);
        $payloadBase64 = base64_encode($payload);
        $signature = hash_hmac('sha256', $payloadBase64, $secret);
        return $payloadBase64 . '.' . $signature;
    }

    /**
     * Append a token parameter to a URL.
     *
     * @param string $url
     * @param string|null $token
     * @return string
     */
    private function appendToken(string $url, ?string $token): string
    {
        if ($token === null) {
            return $url;
        }
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'token=' . urlencode($token);
    }

    /**
     * Get the media URL based on base URL and crem:cote
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return string
     */
    public function getMediaUrl(AbstractResourceEntityRepresentation $resource): string
    {
        $pattern = $this->settings->get('audioplayer_media_url_pattern', '');

        if (empty($pattern)) {
            $url = method_exists($resource, 'originalUrl') ? $resource->originalUrl() : '';
        } else {
            $url = $this->replaceTokens($pattern, $resource);
        }

        $code = $this->getResourceCode($resource);
        return $this->appendToken($url, $this->generateToken($code));
    }

    /**
     * Get the waveform URL based on pattern
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return string
     */
    public function getWaveformUrl(AbstractResourceEntityRepresentation $resource): string
    {
        $pattern = $this->settings->get('audioplayer_waveform_url_pattern', '');

        if (empty($pattern)) {
            // Fallback to media URL (already tokenized) + /waveform.json
            return rtrim($this->getMediaUrl($resource), '/') . '/waveform.json';
        }

        $url = $this->replaceTokens($pattern, $resource);
        $code = $this->getResourceCode($resource);
        return $this->appendToken($url, $this->generateToken($code));
    }

    /**
     * Get subtitles JSON from external API and transform it for the web component.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return string JSON string representing an array of subtitle objects
     */
    public function getSubtitlesJson(AbstractResourceEntityRepresentation $resource): string
    {
        $pattern = $this->settings->get('audioplayer_subtitles_url_pattern', '');
        if (empty($pattern)) {
            return '[]';
        }

        $url = $this->replaceTokens($pattern, $resource);
        if (empty($url)) {
            return '[]';
        }

        try {
            $client = new Client($url, [
                'timeout' => 5, // Short timeout
            ]);
            $response = $client->send();

            if (!$response->isSuccess()) {
                return '[]';
            }

            $data = json_decode($response->getBody(), true);
            if (!is_array($data)) {
                return '[]';
            }

            $code = $this->getResourceCode($resource);
            $token = $this->generateToken($code);

            $subtitles = [];
            foreach ($data as $item) {
                if (isset($item['url']) && isset($item['language_code'])) {
                    $label = !empty($item['language_label']) ? $item['language_label'] : $item['language_code'];

                    $subtitles[] = [
                        'url' => $this->appendToken($item['url'], $token),
                        'language' => $item['language_code'],
                        'label' => $label,
                    ];
                }
            }

            return json_encode($subtitles);
        } catch (HttpClientRuntimeException $e) {
            return '[]';
        } catch (\Exception $e) {
            return '[]';
        }
    }


    /**
     * Get the media type (audio or video) based on the format property.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return string 'audio' or 'video'
     */
    public function getMediaType(AbstractResourceEntityRepresentation $resource): string
    {
        $formatProperty = $this->settings->get('audioplayer_format_property', 'dcterms:format');
        $formats = $resource->value($formatProperty, ['all' => true]);

        $audioTerms = ['audio', 'sound'];
        $videoTerms = ['video', 'vidéo', 'moving image'];

        foreach ($formats as $format) {
            $value = strtolower(trim((string) $format));
            if (in_array($value, $audioTerms) || strpos($value, 'audio/') === 0) {
                return 'audio';
            }
            if (in_array($value, $videoTerms) || strpos($value, 'video/') === 0) {
                return 'video';
            }
        }

        // Fallback to mediaType prefix if no match in property
        if (method_exists($resource, 'mediaType')) {
            $type = $resource->mediaType();
            if (strpos($type, 'video/') === 0) {
                return 'video';
            }
        }

        return 'audio';
    }

    /**
     * Replace tokens in a pattern with resource metadata values.
     *
     * @param string $pattern
     * @param AbstractResourceEntityRepresentation $resource
     * @return string
     */
    protected function replaceTokens(string $pattern, AbstractResourceEntityRepresentation $resource): string
    {
        if (empty($pattern)) {
            return '';
        }

        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($resource) {
            $term = $matches[1];
            $value = $resource->value($term);
            return $value ? (string) $value : '';
        }, $pattern);
    }

    /**
     * Get the primary audio/video media for an item.
     *
     * @param ItemRepresentation $item
     * @return \Omeka\Api\Representation\MediaRepresentation|null
     */
    public function getPrimaryMedia(ItemRepresentation $item)
    {
        // Return the item's primary media if set
        return $item->primaryMedia();
    }
}
