<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ContactController extends Controller
{
    /**
     * @Route("/contact", name="_contact")
     * @Template("TrackersBundle:Contact:index.html.twig")
     */
    public function indexAction()
    {
        return array();
    }
}
