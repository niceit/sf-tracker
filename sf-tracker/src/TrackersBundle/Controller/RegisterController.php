<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Config\Definition\Exception\Exception;
use TrackersBundle\Entity\UserDetail;
use Symfony\Component\HttpFoundation\Request;

class RegisterController extends Controller
{

    /**
     * @Route("/signup", name="_singup")
     * @Template("TrackersBundle:Registration:register.html.twig")
     */
    public function signupAction()
    {
        return array();
    }

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
            // a:1:{i:0;s:8:"ROLE_USER"}
            //a:2:{i:0;s:8:"ROLE_USER";i:1;s:16:"ROLE_ADMIN"}
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

                try{

                }catch (Exception $e){
                    $mailer = $this->get('mailer');
                    $message = \Swift_Message::newInstance();

                    $template = $this->render('TrackersBundle:Mail:register_email.html.twig', array('register_info' => array()));

                    $message->setSubject("Register")
                        ->setFrom('contact@tracker.com')
                        ->setTo(array($email))
                        //->setTo(array('soufiane@illusio.fr'))
                        //->setBcc(array('admin@illusio.fr'))
                        ->addPart($template->getContent(), 'text/html');

                    if ($mailer->send($message)){

                    }
                }
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

    /**
     * @Route("/forgotpassword", name="_forgotpassword")
     * @Template("TrackersBundle:Registration:forgotpassword.html.twig")
     */
    public function forgotpasswordAction(Request $request)
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $email = trim($requestData->get('email'));
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserByEmail($email);
            if(empty($user)){
                $this->get('session')->getFlashBag()->add('error', 'Email does not exist!');
            }else{
                $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
                $userS = $repository_user->findBy(array('user_id'=>$user->getId()));
                $user = $userS[0];


                $user->setForgotPasswordToken(md5($userS[0]->getUser_id().rand(0,1000000)));
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->get('session')->getFlashBag()->add('notice', 'Please check the mail we have sent you a confirmation link.');

            }
        }

         $request->getHost().$this->generateUrl('_changepassword',array('token'=>'sssss','username'=>'sssss'));

        return array();
    }

    /**
     * @Route("/changepassword/{token}/{username}", name="_changepassword")
     * @Template("TrackersBundle:Registration:changepassword.html.twig")
     */
    public function changepasswordAction($token ,$username )
    {
        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $userS = $repository_user->findBy(array('forgotpassword_token'=>$token));

        if(empty($userS) || $token == ''){
            return $this->redirectToRoute('fos_user_security_login');
        }

        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $Password = trim($requestData->get('Password'));
            $ConfirmPassword = trim($requestData->get('ConfirmPassword'));
            if(trim( $Password ) == trim( $ConfirmPassword ) && trim($Password) != '' )
            {
                $repository = $this->getDoctrine()->getRepository('TrackersBundle:User');

                $userManager = $this->container->get('fos_user.user_manager');
                $user =  $project = $repository->find($userS[0]->getUser_id());
                $user->setPlainPassword($Password);
                $userManager->updateUser($user);

                $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
                $users = $repository_user->findBy(array('user_id'=>$user->getId()));
                $user_detail = $users[0];
                $user_detail->setForgotPasswordToken(md5($userS[0]->getUser_id().rand(0,1000000)));
                $em = $this->getDoctrine()->getManager();
                $em->persist($user_detail);
                $em->flush();
                $this->get('session')->getFlashBag()->add('notice', 'Change password successful.');
                return $this->redirectToRoute('fos_user_security_login');

            }else{
                $this->get('session')->getFlashBag()->add('error', 'Incorrect password confirmation!');
            }

        }


        return array();
    }

}
