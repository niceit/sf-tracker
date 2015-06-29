<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class UserController extends Controller
{
    public function __construct()
    {

    }
    /**
     * @Route("/", name="_home")
     * @Template("TrackersBundle:User:index.html.twig")
     */
    public function indexAction()
    {

        $usr = $this->get('security.context')->getToken()->getUser();
        echo  $usr->getUsername();

        if($this->isGranted('IS_AUTHENTICATED_ANONYMOUSLY')){
            echo 1;
        }

       // print_r($this->getUser());
       // var_dump(granted("IS_AUTHENTICATED_REMEMBERED"));
        return array();
    }
}
