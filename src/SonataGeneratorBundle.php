<?php

namespace SonataGenerator;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Welp\MailchimpBundle\DependencyInjection\SonataGeneratorExtension;

class SonataGeneratorBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new SonataGeneratorExtension();
    }
}