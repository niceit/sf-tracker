<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use TrackersBundle\Entity\Projects;
use TrackersBundle\Entity\User_projects;
use TrackersBundle\Models\Document;
use TrackersBundle\Entity\Pagination;
use Symfony\Component\HttpFoundation\Response;

use TrackersBundle\Entity\Project_issuesRepository;
use TrackersBundle\Entity\Users_activity;

class ProjectsController extends Controller
{
    /**
     * @Route("/project", name="_listProjects")
     * @Template("TrackersBundle:Projects:index.html.twig")
     */
    public function indexAction()
    {
        return array();
    }
    /**
     * @Route("/project/{id}/{tab}", name="_detailProjects")
     * @Template("TrackersBundle:Projects:show.html.twig")
     */
    public function showAction($id,$tab)
    {
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository->find($id);

        if ($project->getOwnerId() == $this->getUser()->getId()){
            $is_add = true;
        } else $is_add = false;

        if (!empty($project) && $project->getImage() != '' ){
            if (file_exists($this->get('kernel')->getRootDir() . '/../web'.$project->getImage())) {
                $is_image = true;
            } else $is_image = false;
        } else $is_image = false;

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Users_activity');
        $number_activity = count($repository->findBy(array ('parentId' => $id)));

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $number_open = count($repository->findBy(array ('projectId' => $id, 'status' => 'OPEN')));

        $number_close = count($repository->findBy(array ('projectId' => $id, 'status' => 'CLOSED')));
       // $number_assigned = count($repository->findBy(array ('projectId' => $id, 'assignedTo' => $this->getUser()->getId())));
        $em = $this->container->get('doctrine')->getManager();
        $query = $em->createQuery("SELECT p FROM TrackersBundle:Project_issues p , TrackersBundle:Project_issue_assignments pm  WHERE  p.projectId =:projectId AND  p.id = pm.issueId AND pm.userId =:userId   ORDER BY p.created DESC ")
            ->setParameter('projectId', $id)
            ->setParameter('userId', $this->getUser()->getId());
        $project_issues = $query->getResult();
        $number_assigned = count($project_issues);
        return array('id'=>$id, 'is_image' => $is_image, 'number_activity' => $number_activity , 'number_open' => $number_open, 'number_close' => $number_close , 'number_assigned' => $number_assigned, 'project' => $project , 'is_add' => $is_add , 'tab' => $tab);
    }

    /**
     * @Route("/ajax_project", name="_ajaxlistProjects")
     */
    public function ajaxProjectAction()
    {
        $requestData = $this->getRequest()->request;
        $page = $requestData->get('page');

        $em = $this->getDoctrine()->getEntityManager();

        $limit = $this->container->getParameter( 'limit_project');
        $offset = $page*$limit;

        $query = $em->createQuery("SELECT p FROM TrackersBundle:Projects p WHERE  p.owner_id = :user_id OR p.id IN ( SELECT up.projectId FROM TrackersBundle:Project_issues up WHERE up.assignedTo = :assigned_to ) ORDER BY p.created ")
            ->setParameter('user_id', $this->getUser()->getId())
            ->setParameter('assigned_to', $this->getUser()->getId());

        $total = (int)round( count($query->getResult()) / $limit);
        $count = count($query->getResult());
        if ($count > $limit &&  $count  % $limit != 0){
            $total = $total + 1;
        }

        $query = $em->createQuery("SELECT p FROM TrackersBundle:Projects p WHERE  p.owner_id = :user_id OR p.id IN ( SELECT up.projectId FROM TrackersBundle:Project_issues up WHERE up.assignedTo = :assigned_to)  ORDER BY p.created")
            ->setParameter('user_id', $this->getUser()->getId())
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('assigned_to', $this->getUser()->getId());

        $projects = $query->getResult();
        $pagination = new Pagination();
        $paginations = $pagination->render($page,$total,'list_projects');

        echo $this->render('TrackersBundle:Projects:ajax_list.html.twig', array( 'projects' => $projects , 'paginations' => $paginations ,'user_id' => $this->getUser()->getId()));
        die();
    }

    /**
     * @Route("/projects/add", name="_addProjects")
     * @Template("TrackersBundle:Projects:add.html.twig")
     */
    public function addAction(Request $request)
    {
        $arr_err = array();
        $project = new Projects();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $form = $requestData->get('form');
            $image = $requestData->get('image');
            $check_user = $requestData->get('check_user');

            $project->setName($form['name']);
            $project->setDescription($form['description']);
            $project->setImage($image);
            $project->setStatus($form['status']);
            $project->setOwnerId($this->getUser()->getId());
            $project->setCreated(new \DateTime('now'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();

            if (!empty($check_user)){
                foreach ($check_user as $check){
                    $user_projects = new User_projects();
                    $user_projects->setProjectId($project->getId());
                    $user_projects->setUserId($check);
                    $user_projects->setCreated(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user_projects);
                    $em->flush();
                }
            }
            $this->get('session')->getFlashBag()->add('notice', 'More successful project!');
            return $this->redirectToRoute('_addProjects');
        }
        $form = $this->createFormBuilder($project)
            ->add('name', 'text', array('label'=>'Name', 'required' => true))
            ->add('description', 'textarea', array ('required' => false, 'label' => 'Description', 'attr' => array ('class' => 'editor', 'id' => 'editor')))
            ->add('status', 'choice', array(
                'choices' => array('1' => 'Open', '0' => 'Archived'),
                'preferred_choices' => array('0'),
                'attr' =>array('class'=>'select-box'),
                'label'=>'Status'
            ))
            ->getForm();
        return array('form' => $form->createView(), 'err' => $arr_err);
    }
    /**
     * @Route("/adduserproject", name="_addUerProject")
     */
    public function addUserProjectAction()
    {
        $requestData = $this->getRequest()->request;
        $project_id = $requestData->get('project_id');
        $user_id = $requestData->get('user_id');
        $full_name = $requestData->get('full_name');

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:User_projects');
        $user_project = $repository->findBy(array('projectId' => $project_id , 'userId' => $user_id));
        if (!empty($user_project)){
            die();
        }
        $user_projects = new User_projects();
        $user_projects->setProjectId($project_id);
        $user_projects->setUserId($user_id);
        $user_projects->setCreated(new \DateTime('now'));
        $em = $this->getDoctrine()->getManager();
        $em->persist($user_projects);
        $em->flush();

        echo json_encode(array('user_id' => $user_id, 'project_user_id' => $user_projects->getId(), 'full_name' => $full_name));
        die();
    }

    /**
     * @Route("/pro/edit/{id}", name="_editProjects")
     * @Template("TrackersBundle:Projects:edit.html.twig")
     */
    public function editAction($id,Request $request)
    {
        $arr_err = array();
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = new Projects();

        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $form = $requestData->get('form');
            $check_user = $requestData->get('check_user');
            $image = $requestData->get('image');

            $project = $repository->find($id);
            $project->setName($form['name']);
            $project->setDescription($form['description']);

            if($image=='')
                $project->setImage($requestData->get('old_image'));
            else
                $project->setImage($image);

            $project->setStatus($form['status']);
            $project->setModified(new \DateTime('now'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();

            $repository_User_projects = $this->getDoctrine()->getRepository('TrackersBundle:User_projects');
            $user_projectss = $repository_User_projects->findBy(array('projectId' => $id ));

            foreach ($user_projectss as $user_project){
                $repositorys = $this->getDoctrine()->getRepository('TrackersBundle:User_projects');
                $project_user = $repositorys->find($user_project->getId());
                $em = $this->getDoctrine()->getManager();
                $em->remove($project_user);
                $em->flush();
            }

            if (!empty($check_user)){
                foreach ($check_user as $check){
                    $user_projects = new User_projects();
                    $user_projects->setProjectId($project->getId());
                    $user_projects->setUserId($check);
                    $user_projects->setCreated(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user_projects);
                    $em->flush();
                }
            }
            $this->get('session')->getFlashBag()->add('notice', 'More edit successful project!');
        }
        $project_id = $repository->find($id);
        $project->setName($project_id->getName());
        $project->setDescription($project_id->getDescription());
        $form = $this->createFormBuilder($project)
            ->add('name', 'text', array('label' => 'Name','required' => true))
            ->add('description', 'textarea', array ('required' => false, 'label' => 'Description', 'attr' => array ('class' => 'editor', 'id' => 'editor')))
            ->add('status', 'choice', array(
                'choices' => array('1' => 'Open', '0' => 'Archived'),
                'preferred_choices' => array($project_id->getStatus()),
                'label' => 'Status'
            ))
            ->getForm();

        if ($project_id->getImage() != ''){
            if (file_exists($this->get('kernel')->getRootDir() . '/../web'.$project_id->getImage()) ){
                $is_image = true;
            } else $is_image = false;
        } else $is_image = false;

        $repository_User_projects = $this->getDoctrine()->getRepository('TrackersBundle:User_projects');
        $user_projects = $repository_User_projects->findBy(array('projectId' => $id ));

        $arr_user = array();
        $em = $this->getDoctrine()->getEntityManager();
        foreach ($user_projects as $user){
            $query = $em->createQuery("SELECT n.firstname , n.lastname , u.username FROM TrackersBundle:UserDetail n, TrackersBundle:User u WHERE  u.id = n.user_id  AND  n.user_id = :user_id ")
                ->setParameter('user_id', $user->getUserId());
            $users = $query->getResult();

            $arr_user[] = array(
                'user_id' => $user->getUserId(),
                'full_name' => $users[0]['username']." - ".$users[0]['firstname']." ".$users[0]['lastname']
            );
        }
        return array('form' => $form->createView(), 'user_projects' => $arr_user, 'title' => $project->getName(), 'err' => $arr_err, 'image_old' => $project_id->getImage(), 'id' => $id, 'is_image' => $is_image);
    }

    /**
     * @Route("/add_userprojects", name="_Add_UserProjects")
     */
    public function addUserAction()
    {
        $requestData = $this->getRequest()->request;
        $project_id = $requestData->get('project_id');
        $em = $this->getDoctrine()->getEntityManager();

        $query = $em->createQuery("SELECT n.firstname , n.lastname , u.id, n.avatar FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND u.projectId = :project_id")
            ->setParameter('project_id', $project_id);
        $entities = $query->getResult();

        $array = array();
        foreach ($entities as $entiti){
            if (file_exists($this->get('kernel')->getRootDir() . '/../web'.$entiti['avatar']) ){
                $is_avatar = $entiti['avatar'];
            } else $is_avatar = false;
            $array[] = array(
                'avatar' => $is_avatar,
                'id' => $entiti['id'],
                'firstname' => $entiti['firstname'],
                'lastname' => $entiti['lastname']
            );
        }
        $template = $this->render('TrackersBundle:Projects:ajax_assigned_user.html.twig', array( 'user_projects' => $array));
        return new Response($template->getContent());
        die();
    }

    /**
     * @Route("/pro/delete/{id}", name="_deleteProjects")
     */
    public function deleteAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository->find($id);
        $em = $this->getDoctrine()->getManager();
        $em->remove($project);
        $em->flush();

        return $this->redirectToRoute('_home');
    }

    /**
     * @Route("/projectdelete", name="_delete_ajax_Projects")
     */
    public function deleteAjaxAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('id');

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
            $project = $repository->find($id);
            $em = $this->getDoctrine()->getManager();
            $em->remove($project);
            $em->flush();

            $repository_pro = $this->getDoctrine()->getRepository('TrackersBundle:User_projects');
            $user_project_s = $repository_pro->findBy(array( 'projectId' => $id));
            $user_projects = $repository_pro->find($user_project_s[0]->getId());
            $em->remove($user_projects);
            $em->flush();
            echo 1;
            exit;
        }
        echo 0;
        exit;
    }

    /**
     * @Route("/removeUerProject", name="_removeUerProject")
     */
    public function removeUerProjectAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('id');

            $repository_pro = $this->getDoctrine()->getRepository('TrackersBundle:User_projects');
            $em = $this->getDoctrine()->getManager();
            $user_projects = $repository_pro->find($id);
            $em->remove($user_projects);
            $em->flush();
            echo $id;
            exit;
        }
        echo 0;
        exit;
    }

    /**
     * @Route("/list_assigned", name="_list_assigned")
     */
    public function listAssignedAction( )
    {
        $requestData = $this->getRequest()->request;
        $project_id = $requestData->get('project_id');

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository->find($project_id);
        if ($project->getOwnerId() != $this->getUser()->getId()){
            die();
        }

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $number_open = count($repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN')));

        $number_close = count($repository->findBy(array ('projectId' => $project_id, 'status' => 'CLOSED')));

        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery("SELECT n.firstname , n.lastname , u.id, n.avatar FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND u.projectId = :project_id")
            ->setParameter('project_id', $project_id);

        $entities = $query->getResult();
        $template = $this->render('TrackersBundle:Projects:ajax_left_assigned_user.html.twig', array( 'user_projects' => $entities , 'number_open' => $number_open ,'number_close' => $number_close , 'project_id' => $project_id));
        return new Response($template->getContent());
        die();
    }




}
