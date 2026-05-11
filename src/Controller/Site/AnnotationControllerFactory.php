<?php
namespace AudioPlayer\Controller\Site;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use AudioPlayer\Service\AnnotationService;

class AnnotationControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $annotationService = $container->get(AnnotationService::class);
        return new AnnotationController($annotationService);
    }
}