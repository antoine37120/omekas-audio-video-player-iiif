<?php
namespace AudioPlayer\Site\ResourcePageBlockLayout;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use AudioPlayer\Service\PlayerService;

class AudioPlayerResourcePageBlockLayoutFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $playerService = $container->get(PlayerService::class);
        return new AudioPlayerResourcePageBlockLayout($playerService);
    }
}
