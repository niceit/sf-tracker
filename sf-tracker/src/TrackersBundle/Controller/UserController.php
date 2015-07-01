<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use TrackersBundle\Entity\UserDetail;

class UserController extends Controller
{
    public function __construct()
    {

    }
    /**
     * @Route("/user", name="_user")
     * @Template("TrackersBundle:User:index.html.twig")
     */
    public function indexAction()
    {
        return array('active'=>'users');
    }


    /**
     * @Route("/user/add", name="_user_add")
     * @Template("TrackersBundle:User:add.html.twig")
     */
    public function addAction()
    {
        $user_detail = new UserDetail();
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $account = $requestData->get('account');


            // Create a new user

            $user->setUsername($account['userName']);
            $user->setEmail($account['email']);
            $user->setPlainPassword('password');
            $user->setEnabled(true);

            $manager = $this->getDoctrine()->getEntityManager();
            $manager->persist($user);
            $manager->flush();
            if(!empty($user) && $user->getId()!=''){
                $user_detail->setUser_id($user->getId());
                $user_detail->setSituation('');
                $user_detail->setFirstname('');
                $user_detail->setLastname('');
                $user_detail->setAvatar('');
                $user_detail->setStreet1('');
                $user_detail->setStreet2('');
                $user_detail->setState('');
                $user_detail->setCity('');
                $user_detail->setPhone('');
                $user_detail->setCountry('');

                $em = $this->getDoctrine()->getManager();
                $em->persist($user_detail);
                $em->flush();
            }
        }


        return array();
    }


}


