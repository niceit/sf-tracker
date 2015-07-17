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

        $query = $em->createQuery("SELECT n.firstname , n.lastname , u.id FROM TrackersBundle:UserDetail n, TrackersBundle:User u WHERE  u.id = n.user_id AND ( n.firstname LIKE  :firstname OR n.lastname LIKE  :lastname OR u.username LIKE  :username  OR u.email LIKE  :email) AND n.user_id != :user_id AND u.id NOT IN ( SELECT up.userId FROM TrackersBundle:User_projects up WHERE up.projectId = :projectid) ")
            ->setParameter('firstname', '%'.$user_name.'%')
            ->setParameter('lastname', '%'.$user_name.'%')
            ->setParameter('username', '%'.$user_name.'%')
            ->setParameter('email', '%'.$user_name.'%')
            ->setParameter('user_id', $this->getUser()->getId())
            ->setParameter('projectid', $project_id);

        $entities = $query->getResult();
        if(!empty($entities)){
            $html = '<ul>';
            foreach($entities as $entitie){
                $repository = $this->getDoctrine()->getRepository('TrackersBundle:User');
                $user = $repository->find($entitie['id']);
                $full_name = "' ".$user->getUsername()." - ".$entitie['firstname'].' '. $entitie['lastname']."'";
                $html .= '<li class="row-'.$entitie['id'].'"><a onclick="fcadd_user('.$entitie['id'].', '.$full_name.');" href="javascript:void(0);">'.$user->getUsername().' - '.$entitie['firstname'].' '. $entitie['lastname'].'</a></li>';
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
            $image = $requestData->get('image');
            $image_name = $account['avatar_old'];
            // avatar-----------------------------
            if($image != '')
                $image_name = $image;

            //---- Update user
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $this->getUser();
            $user->setEmail($account['email']);
            if( $account['password'] != '' )
             $user->setPlainPassword($account['password']);
            $userManager->updateUser($user);


            //---- Update user_detail
            $userS = $repository_user->find($user_detail);
            $userS->setFirstname($account['firstName']);
            $userS->setLastname($account['lastName']);
            $userS->setSituation($account['situation']);
            $userS->setStreet1($account['street_1']);
            $userS->setStreet2($account['street_2']);
            $userS->setState($account['state']);
            $userS->setAvatar($image_name);
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

        if(file_exists($this->get('kernel')->getRootDir() . '/../web'.$UserDetail->getAvatar()) ){
           $is_avatar = true;
        }else $is_avatar = false;



        return array( 'users' => $this->getUser() ,'is_avatar' => $is_avatar, 'UserDetail' => $UserDetail, 'countrys' => $countrys , 'user_detail' => $users[0]->getId() );
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
   public function url($str)
    {
        $VMAP = array(
            'ế' => 'e',
            'ề' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ệ' => 'e',
            #---------------------------------E^
            'Ế' => 'e',
            'Ề' => 'e',
            'Ể' => 'e',
            'Ễ' => 'e',
            'Ệ' => 'e',
            #---------------------------------e
            'é' => 'e',
            'è' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ẹ' => 'e',
            'ê' => 'e',
            #---------------------------------E
            'É' => 'e',
            'È' => 'e',
            'Ẻ' => 'e',
            'Ẽ' => 'e',
            'Ẹ' => 'e',
            'Ê' => 'e',
            #---------------------------------i
            'í' => 'i',
            'ì' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ị' => 'i',
            #---------------------------------I
            'Í' => 'i',
            'Ì' => 'i',
            'Ỉ' => 'i',
            'Ĩ' => 'i',
            'Ị' => 'i',
            #---------------------------------o^
            'ố' => 'o',
            'ồ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ộ' => 'o',
            #---------------------------------O^
            'Ố' => 'o',
            'Ồ' => 'o',
            'Ổ' => 'o',
            'Ô' => 'o',
            'Ộ' => 'o',
            #---------------------------------o*
            'ớ' => 'o',
            'ờ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ợ' => 'o',
            #---------------------------------O*
            'Ớ' => 'o',
            'Ờ' => 'o',
            'Ở' => 'o',
            'Ỡ' => 'o',
            'Ợ' => 'o',
            #---------------------------------u*
            'ứ' => 'u',
            'ừ' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ự' => 'u',
            #---------------------------------U*
            'Ứ' => 'u',
            'Ừ' => 'u',
            'Ử' => 'u',
            'Ữ' => 'u',
            'Ự' => 'u',
            #---------------------------------y
            'ý' => 'y',
            'ỳ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'ỵ' => 'y',
            #---------------------------------Y
            'Ý' => 'y',
            'Ỳ' => 'y',
            'Ỷ' => 'y',
            'Ỹ' => 'y',
            'Ỵ' => 'y',
            #---------------------------------DD
            'Đ' => 'd',
            'đ' => 'd',
            #---------------------------------o
            'ó' => 'o',
            'ò' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ọ' => 'o',
            'ô' => 'o',
            'ơ' => 'o',
            #---------------------------------O
            'Ó' => 'o',
            'Ò' => 'o',
            'Ỏ' => 'o',
            'Õ' => 'o',
            'Ọ' => 'o',
            'Ô' => 'o',
            'Ơ' => 'o',
            #---------------------------------u
            'ú' => 'u',
            'ù' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ụ' => 'u',
            'ư' => 'u',
            #---------------------------------U
            'Ú' => 'u',
            'Ù' => 'u',
            'Ủ' => 'u',
            'Ũ' => 'u',
            'Ụ' => 'u',
            'Ư' => 'u',

            #---------------------------------a^
            'ấ' => 'a',
            'ầ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ậ' => 'a',
            #---------------------------------A^
            'Ấ' => 'a',
            'Ầ' => 'a',
            'Ẩ' => 'a',
            'Ẫ' => 'a',
            'Ậ' => 'a',
            #---------------------------------a(
            'ắ' => 'a',
            'ằ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'ặ' => 'a',
            #---------------------------------A(
            'Ắ' => 'a',
            'Ằ' => 'a',
            'Ẳ' => 'a',
            'Ẵ' => 'a',
            'Ặ' => 'a',
            #---------------------------------A
            'Á' => 'a',
            'À' => 'a',
            'Ả' => 'a',
            'Ã' => 'a',
            'Ạ' => 'a',
            'Â' => 'a',
            'Ă' => 'a',
            #---------------------------------a
            'ả' => 'a',
            'ã' => 'a',
            'ạ' => 'a',
            'â' => 'a',
            'ă' => 'a',
            'à' => 'a',
            'á' => 'a'
        );
        $str=strtr($str, $VMAP);
        $str=strtr($str, array(
            ' '=>'-','/'=>'-',"'"=>'','"'=>'','“'=>'','”'=>'','’'=>'',
            ':'=>'','.'=>'',','=>'','®'=>'(R)','©'=>'(C)'));
        return strtolower($str);
    }
}


