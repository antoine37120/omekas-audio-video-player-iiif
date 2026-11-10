<?php
namespace AudioPlayer\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PlayerServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $settings = $container->get('Omeka\Settings');
        $connection = $container->get('Omeka\Connection');
        return new PlayerService($settings, $connection);
    }
}
