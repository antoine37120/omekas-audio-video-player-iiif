<?php
namespace AudioPlayer;

use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\Site\PlayerController::class => Controller\Site\PlayerControllerFactory::class,
            Controller\Site\AnnotationController::class => Controller\Site\AnnotationControllerFactory::class,
            Controller\Admin\AnnotationController::class => Controller\Admin\AnnotationControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\PlayerService::class => Service\PlayerServiceFactory::class,
            Service\AnnotationService::class => Service\AnnotationServiceFactory::class,
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'audioPlayer' => View\Helper\AudioPlayerFactory::class,
        ],
    ],
    'resource_page_block_layouts' => [
        'factories' => [
            Site\ResourcePageBlockLayout\AudioPlayerResourcePageBlockLayout::class => Site\ResourcePageBlockLayout\AudioPlayerResourcePageBlockLayoutFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'audio-player' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/audio-player',
                            'defaults' => [
                                '__SITE__' => true,
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'annotation' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/annotation[/:action[/:id]]',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'id' => '[a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'AudioPlayer\Controller\Site',
                                        'controller' => Controller\Site\AnnotationController::class,
                                        'action' => 'index',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'annotation-iiif' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/annotation/iiif/:id',
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'AudioPlayer\Controller\Site',
                                        'controller' => 'AnnotationController',
                                        'action' => 'iiif',
                                    ],
                                ],
                            ],
                            'embed' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/embed/:id',
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'AudioPlayer\Controller\Site',
                                        'controller' => Controller\Site\PlayerController::class,
                                        'action' => 'embed',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'child_routes' => [
                    'audio-player' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/audio-player',
                            'defaults' => [
                                '__NAMESPACE__' => 'AudioPlayer\Controller\Admin',
                                'controller' => Controller\Admin\AnnotationController::class,
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'index',
                                    ],
                                ],
                            ],
                            'id' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/:id[/:action]',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'id' => '\d+',
                                    ],
                                    'defaults' => [
                                        'action' => 'index',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'AdminGlobal' => [
            [
                'label' => 'Audio Player Annotations',
                'route' => 'admin/audio-player',
                'resource' => Controller\Admin\AnnotationController::class,
            ],
        ],
    ],
];


