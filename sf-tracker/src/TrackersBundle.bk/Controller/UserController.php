<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class UserController extends Controller
{
    /**
     * @Route("/", name="_home")
     * @Template("TrackersBundle:User:index.html.twig")
     */
    public function indexAction()
    {
        return array();
    }
}
