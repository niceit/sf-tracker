<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
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


    /**
     * @Route("/profile", name="_Profile")
     * @Template("TrackersBundle:User:profile.html.twig")
     */
    public function profileAction()
    {
        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');

        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $account = $requestData->get('account');
            $user_detail = $requestData->get('user_detail');
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $this->getUser();
            $user->setEmail($account['email']);
           // $user->setPlainPassword($password);
            $userManager->updateUser($user);

            $userS = $repository_user->find($user_detail);
            $userS->setFirstname($account['firstName']);
            $userS->setLastname($account['lastName']);
            $userS->setSituation($account['situation']);
            $userS->setStreet1($account['street_1']);
            $userS->setStreet2($account['street_2']);
            $userS->setState($account['state']);
            $userS->setPhone($account['phone']);
            $userS->setCountry($account['country']);
            $userS->setCity($account['city']);
            $userS->setModified(new \DateTime("now"));

            $em = $this->getDoctrine()->getManager();
            $em->persist($userS);
            $em->flush();


        }



        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Country');
        $countrys = $repository->findAll();


        $users = $repository_user->findBy(array ('user_id' => $this->getUser()->getId()));
        $UserDetail = $repository_user->find($users[0]->getId());


        return array( 'users' => $this->getUser() , 'UserDetail' => $UserDetail, 'countrys' => $countrys , 'user_detail' => $users[0]->getId() );
    }

    /**
     * @Route("/ajaxgetcity", name="_ajaxGetCity")
     */
    public function ajaxgetcityAction()
    {
        $citys = array();

        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('id');
            $city_id = $requestData->get('city');

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:City');
            $citys = $repository->findBy(array ('countryId' => $id));
        }


        $template = $this->render('TrackersBundle:City:ajax_getcity.html.twig', array ('citys' => $citys , 'city_id' => $city_id));
        return new Response($template->getContent());
        die();
    }
}


