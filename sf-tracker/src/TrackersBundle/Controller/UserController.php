<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use TrackersBundle\Entity\UserDetail;
use TrackersBundle\Entity\User_projects;

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
    /**
     * @Route("/ajaxSearch_user", name="_ajaxSearch_user")
     */
    public function ajaxSearch_userAction()
    {
        $requestData = $this->getRequest()->request;
        $user_name = $requestData->get('user_name');
        $project_id = $requestData->get('project_id');
        $html = '';
        $em = $this->getDoctrine()->getEntityManager();

        $query = $em->createQuery("SELECT n.firstname , n.lastname , u.id FROM TrackersBundle:UserDetail n, TrackersBundle:User u WHERE  u.id = n.user_id AND ( n.firstname LIKE  :firstname OR n.lastname LIKE  :lastname OR u.username LIKE  :username  OR u.email LIKE  :email) AND u.id NOT IN ( SELECT up.userId FROM TrackersBundle:User_projects up WHERE up.projectId = :projectid) ")
            ->setParameter('firstname', '%'.$user_name.'%')
            ->setParameter('lastname', '%'.$user_name.'%')
            ->setParameter('username', '%'.$user_name.'%')
            ->setParameter('email', '%'.$user_name.'%')
            ->setParameter('projectid', $project_id);

        $entities = $query->getResult();
        if(!empty($entities)){
            $html = '<ul>';
            foreach($entities as $entitie){
                $html .= '<li class="row-'.$entitie['id'].'"><a onclick="fcadd_user('.$entitie['id'].');" href="javascript:void(0);">'.$entitie['firstname'].' '. $entitie['lastname'].'</a></li>';
            }
            $html .= '</ul>';
        }

        echo $html;
        exit;
    }

}


