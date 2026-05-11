<?php
namespace AudioPlayer\Controller\Site;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Omeka\Api\Manager;
use AudioPlayer\Service\PlayerService;

class PlayerControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $api = $container->get('Omeka\ApiManager');
        $playerService = $container->get(PlayerService::class);
        return new PlayerController($api, $playerService);
    }
}