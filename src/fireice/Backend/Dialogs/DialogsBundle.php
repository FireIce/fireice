<?php

namespace fireice\Backend\Dialogs;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class DialogsBundle extends Bundle
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
