<?php

namespace SonataGenerator;

use SonataGenerator\DependencyInjection\SonataGeneratorExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SonataGeneratorBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new SonataGeneratorExtension();
    }
}