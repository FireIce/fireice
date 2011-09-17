<?php

namespace fireice\FireiceSiteTree\TreeBundle; 

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TreeBundle extends Bundle
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
