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
            'id_property' => $settings->get('audioplayer_id_property', 'crem:cote'),
            'height_audio' => $settings->get('audioplayer_height_audio', '250'),
            'height_video' => $settings->get('audioplayer_height_video', '450'),
            'height_annotations' => $settings->get('audioplayer_height_annotations', '150'),
            'debug_display' => $settings->get('audioplayer_debug_display', false),
            'colors' => $settings->get('audioplayer_colors', ''),
            'playback_rates' => $settings->get('audioplayer_playback_rates', '[0.5, 1, 1.5, 2, 4]'),
            'help_text' => $settings->get('audioplayer_help_text', '<h3>Help</h3><ul><li>Double-click on the timeline to create a new annotation.</li><li>Drag items to move them.</li><li>Drag edges of items to resize them.</li><li>Click an item to seek the audio.</li></ul>'),
            'mms_shared_secret' => $settings->get('audioplayer_mms_shared_secret', ''),
            'mms_app_id' => $settings->get('audioplayer_mms_app_id', 'omekas'),
            'subtitle_field_mapping' => $settings->get('audioplayer_subtitle_field_mapping', '{"url":"url","language":"language_code","label":"language_label"}'),
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
        $settings->set('audioplayer_id_property', $params['id_property']);
        $settings->set('audioplayer_height_audio', $params['height_audio'] ?? '250');
        $settings->set('audioplayer_height_video', $params['height_video'] ?? '450');
        $settings->set('audioplayer_height_annotations', $params['height_annotations'] ?? '150');
        $settings->set('audioplayer_debug_display', (bool) ($params['debug_display'] ?? false));
        $settings->set('audioplayer_colors', $params['colors'] ?? '');
        $settings->set('audioplayer_playback_rates', $params['playback_rates'] ?? '[0.5, 1, 1.5, 2, 4]');
        $settings->set('audioplayer_help_text', $params['help_text'] ?? '');
        $settings->set('audioplayer_mms_shared_secret', $params['mms_shared_secret']);
        $settings->set('audioplayer_mms_app_id', $params['mms_app_id']);
        $settings->set('audioplayer_subtitle_field_mapping', $params['subtitle_field_mapping'] ?? '{"url":"url","language":"language_code","label":"language_label"}');
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        // Ajouter les règles ACL pour permettre l'accès public au contrôleur API
        $this->addAclRules();

        $serviceManager = $event->getApplication()->getServiceManager();
        $sharedEventManager = $serviceManager->get('SharedEventManager');

        // Add the help_text field to the site settings form
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            function ($event) {
                $form = $event->getTarget();
                $form->add([
                    'name' => 'audioplayer_help_text',
                    'type' => \Laminas\Form\Element\Textarea::class,
                    'options' => [
                        'element_group' => 'general',
                        'label' => 'Help text on audio player IIIF (HTML)', // @translate
                        'info' => 'HTML content displayed in the help popup of the audio/video player.', // @translate
                    ],
                    'attributes' => [
                        'id' => 'audioplayer_help_text',
                        'class' => 'wysiwyg',
                        'rows' => 6,
                    ],
                ]);
            }
        );

        // Add input filter for the help_text field
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_input_filters',
            function ($event) {
                $inputFilter = $event->getParam('inputFilter');
                $inputFilter->add([
                    'name' => 'audioplayer_help_text',
                    'required' => false,
                    'allow_empty' => true,
                ]);
            }
        );

        // Load CKEditor and custom JS on the site edit page
        $eventManager = $event->getApplication()->getEventManager();
        $eventManager->attach('render', function (MvcEvent $e) {
            $routeMatch = $e->getRouteMatch();
            if (!$routeMatch) {
                return;
            }
            $isSiteAdmin = $routeMatch->getParam('__SITEADMIN__');
            $action = $routeMatch->getParam('action');
            if ($isSiteAdmin && $action === 'edit') {
                $services = $e->getApplication()->getServiceManager();
                $viewHelperManager = $services->get('ViewHelperManager');
                $assetUrl = $viewHelperManager->get('assetUrl');
                $headScript = $viewHelperManager->get('headScript');

                // Load CKEditor library
                $headScript->appendFile($assetUrl('vendor/ckeditor/ckeditor.js', 'Omeka'));
                $headScript->appendFile($assetUrl('vendor/ckeditor/adapters/jquery.js', 'Omeka'));

                // Load our custom CKEditor init
                $headScript->appendFile($assetUrl('js/admin-site-settings.js', 'AudioPlayer'));
            }
        });
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
