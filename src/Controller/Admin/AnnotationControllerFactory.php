<?php
namespace AudioPlayer\Controller\Admin;

use AudioPlayer\Service\AnnotationService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AnnotationControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new AnnotationController(
            $services->get(AnnotationService::class)
        );
    }
}
