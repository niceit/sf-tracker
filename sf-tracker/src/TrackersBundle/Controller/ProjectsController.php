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
     * @Route("/project/{id}", name="_detailProjects")
     * @Template("TrackersBundle:Projects:show.html.twig")
     */
    public function showAction($id)
    {

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository->find($id);

        if($project->getOwnerId() == $this->getUser()->getId()){
            $is_add = true;
        }else $is_add = false;

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Users_activity');
        $number_activity = count($repository->findBy(array ('parentId' => $id)));

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $number_open = count($repository->findBy(array ('projectId' => $id, 'status' => 'OPEN')));

        $number_close = count($repository->findBy(array ('projectId' => $id, 'status' => 'CLOSED')));
        $number_assigned = count($repository->findBy(array ('projectId' => $id, 'assignedTo' => $this->getUser()->getId())));
        return array('id'=>$id, 'number_activity' => $number_activity , 'number_open' => $number_open, 'number_close' => $number_close , 'number_assigned'=>$number_assigned, 'project' => $project , 'is_add' => $is_add );
    }

    /**
     * @Route("/ajax_project", name="_ajaxlistProjects")
     */
    public function ajax_projectAction()
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
        if($count > $limit &&  $count  % $limit != 0){
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

        echo $this->render('TrackersBundle:Projects:ajax_list.html.twig', array( 'projects' => $projects , 'paginations'=>$paginations ,'user_id' => $this->getUser()->getId()));
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

                $image = $request->files->get('image');
                $name_image = '';
                $is_file = true;
                if(($image instanceof UploadedFile) && $image->getError()=='0'){
                    if($image->getSize() < 20000000){
                        $file_type = $image->getClientOriginalExtension();
                        $name_array = explode('.', $image->getClientOriginalName());

                        $valid_filetypes = array('jpg', 'jpeg', 'bmp', 'png');
                        if (in_array(strtolower($file_type), $valid_filetypes)) {

                            $name_file = strtolower($name_array[0] . "-" . md5(rand(0,100)));
                            $document = new Document();
                            $document->setFile($image);
                            $document->setSubDirectory('project');
                            $document->setNameFile( $name_file );
                            $document->setTypeFile($file_type);
                            $document->processFile();
                            $name_image = $document->getSubDirectory() . "/" . $name_file. "." . $file_type;

                        }else{
                            $is_file = false;
                            $arr_err[]= 'Invalid File Type!';
                        }
                    }else{
                        $is_file = false;
                        $arr_err[]= 'Size exceeds limit!';
                    }
                }
                $project->setName($form['name']);
                $project->setImage($name_image);
                $project->setStatus($form['status']);
                $project->setOwnerId($this->getUser()->getId());
                $project->setCreated(new \DateTime('now'));
                if($is_file){
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($project);
                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'More successful project!');
                    return $this->redirectToRoute('_addProjects');
                }



        }
        $form = $this->createFormBuilder($project)
            ->add('name', 'text', array('label'=>'Name','required'    => true))
            ->add('status', 'choice', array(
                'choices' => array('1' => 'Open', '0' => 'Archived'),
                'preferred_choices' => array('0'),
               // 'attr' =>array('class'=>'selector'),
                'label'=>'Status'
            ))
            ->getForm();
        return array('form' => $form->createView(),'err'=>$arr_err);
    }
    /**
     * @Route("/adduserproject", name="_addUerProject")
     */
    public function add_user_projectAction()
    {
        $requestData = $this->getRequest()->request;
        $project_id = $requestData->get('project_id');
        $user_id = $requestData->get('user_id');

        $user_projects = new User_projects();
        $user_projects->setProjectId($project_id);
        $user_projects->setUserId($user_id);
        $user_projects->setCreated(new \DateTime('now'));
        $em = $this->getDoctrine()->getManager();
        $em->persist($user_projects);
        $em->flush();

        echo $requestData->get('user_id');
        die();

    }
    /**
     * @Route("/project/edit/{id}", name="_editProjects")
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

            $image = $request->files->get('image');
            $name_image = '';
            $is_file = true;
            if(($image instanceof UploadedFile) && $image->getError()=='0'){
                if($image->getSize() < 20000000){
                    $file_type = $image->getClientOriginalExtension();
                    $name_array = explode('.', $image->getClientOriginalName());

                    $valid_filetypes = array('jpg', 'jpeg', 'bmp', 'png');
                    if (in_array(strtolower($file_type), $valid_filetypes)) {

                        $name_file = strtolower($name_array[0] . "-" . md5(rand(0,100)));
                        $document = new Document();
                        $document->setFile($image);
                        $document->setSubDirectory('project');
                        $document->setNameFile( $name_file );
                        $document->setTypeFile($file_type);
                        $document->processFile();
                        $name_image = $document->getSubDirectory() . "/" . $name_file. "." . $file_type;

                    }else{
                        $is_file = false;
                        $arr_err[]= 'Invalid File Type!';
                    }
                }else{
                    $is_file = false;
                    $arr_err[]= 'Size exceeds limit!';
                }
            }
            $project = $repository->find($id);
            $project->setName($form['name']);
            if($name_image=='')
                $project->setImage($requestData->get('old_image'));
            else
                $project->setImage($name_image);
            $project->setStatus($form['status']);
            $project->setModified(new \DateTime('now'));
            if($is_file){
                $em = $this->getDoctrine()->getManager();
                $em->persist($project);
                $em->flush();

                /*
                $repository_pro = $this->getDoctrine()->getRepository('TrackersBundle:User_projects');

                $user_project_s = $repository_pro->findBy(array( 'projectId' => $id ));
                $user_projects = $repository_pro->find($user_project_s[0]->getId());

                $user_projects->setModified(new \DateTime('now'));
                $em->persist($user_projects);
                $em->flush();
                */
                $this->get('session')->getFlashBag()->add('notice', 'More edit successful project!');
            }

        }
        $project_id = $repository->find($id);
        $project->setName($project_id->getName());

        $form = $this->createFormBuilder($project)
            ->add('name', 'text', array('label'=>'Name','required'    => true))
            ->add('status', 'choice', array(
                'choices' => array('1' => 'Open', '0' => 'Archived'),
                'preferred_choices' => array($project_id->getStatus()),
                'label'=>'Status'
            ))
            ->getForm();

        return array('form' => $form->createView(),'err'=>$arr_err,'image_old'=>$project_id->getImage(),'id'=>$id);
    }

    /**
     * @Route("/add_userprojects", name="_Add_UserProjects")
     */
    public function add_userAction()
    {
        $requestData = $this->getRequest()->request;
        $project_id = $requestData->get('project_id');
        $em = $this->getDoctrine()->getEntityManager();

        $query = $em->createQuery("SELECT n.firstname , n.lastname , u.id, n.avatar FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND u.projectId = :project_id")
            ->setParameter('project_id', $project_id);

        $entities = $query->getResult();

        $template = $this->render('TrackersBundle:Projects:ajax_assigned_user.html.twig', array( 'user_projects' => $entities  ));
        return new Response($template->getContent());
        die();
    }

    /**
     * @Route("/project/delete/{id}", name="_deleteProjects")
     */
    public function deleteAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository->find($id);
        $em = $this->getDoctrine()->getManager();
        $em->remove($project);
        $em->flush();

    }
    /**
     * @Route("/projectdelete", name="_delete_ajax_Projects")
     */
    public function deleteajaxAction()
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

            $user_project_s = $repository_pro->findBy(array( 'projectId' => $id ));
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

}
