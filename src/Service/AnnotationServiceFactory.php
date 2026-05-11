<?php
namespace AudioPlayer\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AnnotationServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $connection = $container->get('Omeka\Connection');
        return new AnnotationService($connection);
    }
}
