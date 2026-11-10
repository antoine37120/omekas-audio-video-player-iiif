<?php
namespace AudioPlayer\View\Helper;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AudioPlayerAccessDeniedFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new AudioPlayerAccessDenied();
    }
}
