<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use TrackersBundle\Entity\UserDetail;

class RegisterController extends Controller
{


    /**
     * @Route("/registersubmit", name="_registersubmit")
     */
    public function  registerAction(){
        $user_detail = new UserDetail();
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $arr_err = array();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $firstName = $requestData->get('firstName');
            $lastName = $requestData->get('lastName');
            $userName = $requestData->get('userName');
            $email = $requestData->get('email');
            $repeatPassword = $requestData->get('repeatPassword');
            $password = $requestData->get('password');


            if(trim($firstName) == ''){
                $arr_err['firstname'] = 'Please enter your first name';
            }
            if(trim($lastName) == ''){
                $arr_err['lastname'] = 'Please enter your last name';
            }
            if(trim($userName) == ''){
                $arr_err['username'] = 'Please enter your user name';
            }
            if(trim($email) == ''){
                $arr_err['email'] = 'Please enter your email';
            }
            if(trim($password) == ''){
                $arr_err['password'] = 'Please enter your password';
            }
            if(trim($repeatPassword) == ''){
                $arr_err['repeatPassword'] = 'Please enter your repeat Password';
            }
            if(trim($password) != trim($repeatPassword)){
                $arr_err['repeatPassword'] = 'Please provide a password';
            }
            if($arr_err != null){
                echo json_encode($arr_err);
                exit;
            }
            // Create a new user

            $user->setUsername($userName);
            $user->setEmail($email);
            $user->setPlainPassword($password);
            $user->setEnabled(true);

            $manager = $this->getDoctrine()->getEntityManager();
            $manager->persist($user);
            $manager->flush();
            if(!empty($user) && $user->getId()!=''){
                $user_detail->setUser_id($user->getId());
                $user_detail->setSituation('N/A');
                $user_detail->setFirstname($firstName);
                $user_detail->setLastname($lastName);
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
                $arr_err['ok'] = 1;
                /*
                $mailer = $this->get('mailer');
                $message = $mailer->createMessage()
                    ->setSubject('Mail register')
                    ->setFrom('phamquocvinh99@gmail.com')
                    ->setTo($email)
                    ->setBody($this->renderView('TrackerBundle:Mail:registeremail.txt.twig'));

                $mailer->send($message);
                */
            }
        }
        echo json_encode($arr_err);
        exit;
    }


    /**
     * @Route("/isusername", name="_is_username")
     */
    public function  isusernameAction(){
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $username = trim($requestData->get('username'));
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserByUsername($username);
            if (!empty($user)){
                echo 1;
                exit;
            }
        }
        echo 0;
        exit;
    }
    /**
     * @Route("/isemail", name="_is_email")
     */
    public  function is_email(){
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $email = trim($requestData->get('email'));
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserByEmail($email);
            if (!empty($user)){
                echo 1;
                exit;
            }

        }
        echo 0;
        exit;
    }
}
