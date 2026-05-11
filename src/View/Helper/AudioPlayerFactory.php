<?php
namespace AudioPlayer\View\Helper;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use AudioPlayer\Service\PlayerService;

class AudioPlayerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $playerService = $container->get(PlayerService::class);
        return new AudioPlayer($playerService);
    }
}
