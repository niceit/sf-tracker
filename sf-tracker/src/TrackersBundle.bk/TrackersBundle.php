<?php

namespace TrackersBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TrackersBundle extends Bundle
{
    public function getParent(){
        return 'FOSUserBundle';
    }
}
