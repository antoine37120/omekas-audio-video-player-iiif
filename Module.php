<?php
namespace AudioPlayer;

use Omeka\Module\AbstractModule;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Mvc\Controller\AbstractController;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $data = [
            'waveform_stroke_color' => $settings->get('audioplayer_waveform_stroke_color', 'rgba(0, 0, 0, 0.18)'),
            'waveform_stroke_width' => $settings->get('audioplayer_waveform_stroke_width', '1'),
            'annotation_min_time_to_display' => $settings->get('audioplayer_annotation_min_time_to_display', '15'),
            'annotation_properties_to_display' => $settings->get('audioplayer_annotation_properties_to_display', 'time,text,creator.id'),
            'media_url_pattern' => $settings->get('audioplayer_media_url_pattern', ''),
            'waveform_url_pattern' => $settings->get('audioplayer_waveform_url_pattern', ''),
            'subtitles_url_pattern' => $settings->get('audioplayer_subtitles_url_pattern', ''),
            'format_property' => $settings->get('audioplayer_format_property', 'dcterms:format'),
            'cote_property' => $settings->get('audioplayer_cote_property', 'crem:cote'),
            'debug_display' => $settings->get('audioplayer_debug_display', false),
            'colors' => $settings->get('audioplayer_colors', ''),
            'playback_rates' => $settings->get('audioplayer_playback_rates', '[0.5, 1, 1.5, 2, 4]'),
        ];
        return $renderer->partial('audio-player/admin/config-form', $data);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $params = $controller->getRequest()->getPost();
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->set('audioplayer_waveform_stroke_color', $params['waveform_stroke_color']);
        $settings->set('audioplayer_waveform_stroke_width', $params['waveform_stroke_width']);
        $settings->set('audioplayer_annotation_min_time_to_display', $params['annotation_min_time_to_display']);
        $settings->set('audioplayer_annotation_properties_to_display', $params['annotation_properties_to_display']);
        $settings->set('audioplayer_media_url_pattern', $params['media_url_pattern']);
        $settings->set('audioplayer_waveform_url_pattern', $params['waveform_url_pattern']);
        $settings->set('audioplayer_subtitles_url_pattern', $params['subtitles_url_pattern']);
        $settings->set('audioplayer_format_property', $params['format_property']);
        $settings->set('audioplayer_cote_property', $params['cote_property']);
        $settings->set('audioplayer_debug_display', (bool) ($params['debug_display'] ?? false));
        $settings->set('audioplayer_colors', $params['colors'] ?? '');
        $settings->set('audioplayer_playback_rates', $params['playback_rates'] ?? '[0.5, 1, 1.5, 2, 4]');
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        // Ajouter les règles ACL pour permettre l'accès public au contrôleur API
        $this->addAclRules();
    }

    /**
     * Ajouter les règles ACL pour le module
     */
    protected function addAclRules()
    {
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');

        // Permettre à tout le monde (y compris les visiteurs non connectés)
        // d'accéder à l'action iiif du contrôleur API
        $acl->allow(
            null, // null = tous les rôles, y compris les utilisateurs non authentifiés
            \AudioPlayer\Controller\Site\AnnotationController::class,
            ['index', 'get', 'create', 'update', 'delete', 'iiif']
        );

        // Permettre l'accès public au lecteur et à l'embed
        $acl->allow(
            null,
            \AudioPlayer\Controller\Site\PlayerController::class,
            ['show', 'embed']
        );

        // Permettre aux administrateurs globaux d'accéder à l'interface d'administration
        $acl->allow(
            ['global_admin'],
            Controller\Admin\AnnotationController::class
        );
    }

    // onBootstrap removed as it is not needed for block layout registration via config

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = "
        CREATE TABLE IF NOT EXISTS `media_markers` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `resource_id` int(11) NOT NULL,
          `public_id` varchar(250) NOT NULL,
          `time` double NOT NULL,
          `time_end` double NOT NULL,
          `title` varchar(250) NOT NULL,
          `date` datetime DEFAULT NULL,
          `description` longtext NOT NULL,
          `author_id` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $connection->exec($sql);
    }
}
