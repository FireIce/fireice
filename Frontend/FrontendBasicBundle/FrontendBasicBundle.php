<?php

namespace fireice\Frontend\FrontendBasicBundle; 

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FrontendBasicBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }
} 
