<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use TrackersBundle\Entity\Project_category_task;
use TrackersBundle\Entity\Project_task;
use Symfony\Component\HttpFoundation\Response;
use TrackersBundle\Entity\UserTask;
use TrackersBundle\Entity\Projects_issues_attachments;
use TrackersBundle\Entity\Notifications;
use TrackersBundle\Models\Document;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;


class TaskController extends Controller
{
    /**
     * @Route("/task/{project_id}", name="_indexTask")
     * @Template("TrackersBundle:Task:index.html.twig")
     */
    public function indexAction($project_id)
    {
        $repository_projects = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository_projects->find($project_id);
        if (!empty($project) && $project->getOwnerId() != $this->getUser()->getId() ){
            return $this->redirectToRoute('_home');
        } else {
            if (empty($project))
                return $this->redirectToRoute('_home');
        }

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_category_task');
        $categorys = $repository->findBy(array( 'projectId' => $project_id ) ,array('created' => 'DESC') );
        $arr = array();
        foreach ( $categorys as $category ){
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_task');
            $tasks = $repository->findBy(array( 'projectId' => $project_id , 'categoryTaskId' => $category->getId() ) ,array('created' => 'DESC') );

            $arr_asign = array();
            $em = $this->getDoctrine()->getEntityManager();
            foreach ( $tasks as $task ){
                $query = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id   FROM TrackersBundle:UserDetail n, TrackersBundle:UserTask u WHERE  u.userId = n.user_id AND  u.taskId = :taskId ")
                    ->setParameter('taskId', $task->getId());

                if ($task->getDuetime() != null)
                    $dueTime = $task->getDuetime();
                else
                    $dueTime = '';

                $arr_asign[] = array(
                    'id'  =>  $task->getId(),
                    'title'  =>  $task->getTitle(),
                    'user_assign'  =>  $query->getResult(),
                    'duetime' => $dueTime
                );
            }

            $arr[] = array(
                'tasks' => $arr_asign,
                'id' => $category->getId(),
                'name' => $category->getName()
            );
        }

        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id   FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND  u.projectId = :projectid ")
            ->setParameter('projectid', $project_id);

        return array( 'project_id' => $project_id , 'categorys' => $arr , 'users' => $query->getResult() );
    }

    /**
     * @Route("/category-task/{task_id}", name="_showCategoryTask")
     * @Template("TrackersBundle:Task:show.html.twig")
     */
    public function showCategoryAction($task_id)
    {
        return array();
    }

    /**
     * @Route("/show-task/{task_id}", name="_showTask")
     * @Template("TrackersBundle:Task:show.html.twig")
     */
    public function showAction($task_id)
    {
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_task');
        $task = $repository->find($task_id);
        return array('task' => $task , 'due_date' => $task->getDuetime());
    }

    /**
     * @Route("/ajaxAddTask", name="_ajaxAddTask")
     */
    public function ajaxAddTaskAction()
    {
        $arr = array();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $title = $requestData->get('title');
            $id_category = $requestData->get('id_category');
            $project_id = $requestData->get('project_id');
            $user_asign = $requestData->get('user_asign');
            $file_id = $requestData->get('file');
            $due_date = $requestData->get('due_date');
            $Project_task = new Project_task();

            $Project_task->setTitle($title);
            $Project_task->setDescription('');
            $Project_task->setCategoryTaskId($id_category);
            $Project_task->setProjectId($project_id);
            $Project_task->setCreatedBy($this->getUser()->getId());
            $Project_task->setCreated(new \DateTime('now'));
            $Project_task->setModified(new \DateTime('now'));
            if ($due_date == ''){
                $due_start = '';
            } else {
                // date due time
                $arr_due_date = explode('-', $due_date);
                $due_start = new \DateTime();
                $due_start->setDate($arr_due_date[0], $arr_due_date[1], $arr_due_date[2]);
                $Project_task->setDuetime($due_start);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($Project_task);
            $em->flush();

            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            $users = $repository_user->findBy(array('user_id' => $this->getUser()));
            $user = $users[0];
            $text = $user->getFirstname().' '.$user->getLastname()." is task with content ".$title;
            if (!empty($user_asign)){
                foreach ($user_asign as $user_id ){
                    $userTask = new UserTask();
                    $userTask->setUserId($user_id);
                    $userTask->setTaskId($Project_task->getId());
                    $userTask->setCreated(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($userTask);
                    $em->flush();

                    // notifiaction user attachment
                    $notifications = new Notifications();
                    $notifications->setUserId($user_id);
                    $notifications->setTaskId($Project_task->getId());
                    $notifications->setProjectId($project_id);
                    $notifications->setCreated(new \DateTime("now"));
                    $notifications->setIsRead(false);
                    $notifications->setText($text);
                    $em->persist($notifications);
                    $em->flush();
                }
            }

            if (!empty($file_id)){
                foreach ($file_id as $file){
                    $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
                    $files = $repository->find($file);
                    $files->setTaskId($Project_task->getId());
                    $em->persist($files);
                    $em->flush();
                }
            }

            $full_name = '';
            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id   FROM TrackersBundle:UserDetail n  WHERE  n.user_id IN (:string)  ")
                ->setParameter('string', $user_asign);
            $i = 1;
            foreach ($query->getResult() as $row){
                $full_name .= $row['firstname']." ".$row['lastname']." ";
                if (count($query->getResult()) != $i)
                    $full_name .= " - ";
                $i++;
            }

            $arr = array(
                'id' => $Project_task->getId(),
                'title' => $Project_task->getTitle(),
                'duetime' => $Project_task->getDuetime(),
                'full_name' => $full_name,
                'url' => $this->generateUrl( '_showTask', array('task_id' => $Project_task->getId()))
            );
        } else {
            die('Error...');
        }
        echo json_encode($arr);
        die();
    }
    /**
     * @Route("/ajaxEditTask", name="_ajaxEditTask")
     */
    public function ajaxEditTaskAction()
    {
        $arr = array();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $title = $requestData->get('title');
            $task_id = $requestData->get('task_id');
            $user_asign = $requestData->get('user_asign');
            $due_date = trim($requestData->get('due_date'));
            $file_id = $requestData->get('file');

            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("DELETE FROM TrackersBundle:UserTask u WHERE  u.taskId = :taskId ")
                ->setParameter('taskId', $task_id);
            $query->execute();

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_task');
            $task = $repository->find($task_id);
            $task->setTitle($title);
            $task->setModified(new \DateTime('now'));
            if ($due_date == '')
                $due_start = '';
            else {
                // date due time
                $arr_due_date = explode('-', $due_date);
                $due_start = new \DateTime();
                $due_start->setDate($arr_due_date[0], $arr_due_date[1], $arr_due_date[2]);
               $task->setDuetime($due_start);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();

            if (!empty($user_asign)){
                foreach ($user_asign as $user_id ){
                    $userTask = new UserTask();
                    $userTask->setUserId($user_id);
                    $userTask->setTaskId($task->getId());
                    $userTask->setCreated(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($userTask);
                    $em->flush();
                }
            }

            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("UPDATE TrackersBundle:Projects_issues_attachments u SET u.taskId = NULL  WHERE  u.taskId = :taskId ")
                ->setParameter('taskId', $task_id);
            $query->execute();

            if (!empty($file_id)){
                foreach ($file_id as $file){
                    $repository_attach = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
                    $files = $repository_attach->find($file);
                    $files->setTaskId($task_id);
                    $em->persist($files);
                    $em->flush();
                }
            }

            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id   FROM TrackersBundle:UserDetail n  WHERE  n.user_id IN (:string)  ")
                ->setParameter('string', $user_asign);
            $users = $query->getResult();
            $i = 1;
            $full_name = '';
            if (!empty($users)){
                $full_name .= '( ';
                foreach ($users as $row){
                    $full_name .= $row['firstname']." ".$row['lastname']." ";
                    if (count($query->getResult()) != $i)
                        $full_name .= " - ";
                    $i++;
                }
                $full_name .= " )";
            }

            $arr = array(
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'full_name' => $full_name,
                'duetime' => $task->getDuetime(),
                'url' => $this->generateUrl( '_showTask', array('task_id' => $task->getId()))
            );
        }

        echo json_encode($arr);
        die();
    }

    /**
     * @Route("/ajaxRomoveTask", name="_ajaxRomoveTask")
     */
    public function ajaxRemoveTaskAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('task_id');

            $repository_pro = $this->getDoctrine()->getRepository('TrackersBundle:Project_task');

            $em = $this->getDoctrine()->getManager();
            $task = $repository_pro->find($id);
            $em->remove($task);
            $em->flush();
            echo $id;
            exit;
        }
        echo 0;
        die();
    }

    /**
     * @Route("/ajaxGetFromEditTask", name="_ajaxGetFromEditTask")
     */
    public function ajaxGetFromEditTaskAction()
    {
        $arr = array();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $task_id = $requestData->get('task_id');
            $project_id = $requestData->get('project_id');

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_task');
            $arr = $repository->find($task_id);

            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id   FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND  u.projectId = :projectid ")
                ->setParameter('projectid', $project_id);

            $querys = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id   FROM TrackersBundle:UserDetail n, TrackersBundle:UserTask u WHERE  u.userId = n.user_id AND  u.taskId = :taskId ")
                ->setParameter('taskId', $task_id);

            $repository_attach = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
            $attachFile = $repository_attach->findBy(array('taskId' => $task_id));
        }

        $template = $this->render('TrackersBundle:Task:ajaxgetfromedittask.html.twig', array ('task' => $arr, 'users' => $query->getResult(), 'task_user' =>  $querys->getResult(), 'project_id' => $project_id , 'attachFile' => $attachFile));
        return new Response($template->getContent());
        die();
    }

    /**
     * @Route("/uploadfiletask/{id}", name="_uploadfiletask")
     */
    public function uploadFileAction ($id, Request $request)
    {
        $files = $request->files->get('files');
        if (!empty($files)){
            foreach ($files as $image){
                $fs = new Filesystem();
                $file = 'upload/task/'.$id;
                $dir = 'task/'.$id;
                if (!$fs->exists($file)){
                    $fs->mkdir($file);
                }
                $arr = array();
                $file_type = $image->getClientOriginalExtension();
                $name_array = explode('.', $image->getClientOriginalName());

                $name_file = strtolower($name_array[0] . "-" . md5(rand(0,100)));
                $document = new Document();
                $document->setFile($image);
                $document->setSubDirectory($dir);
                $document->setNameFile( $name_file );
                $document->setTypeFile(strtolower($file_type));
                $document->processFile();
                $name_image = $document->getSubDirectory() . "/" . $name_file. "." . $file_type;

                $projects_issues_attachments = new Projects_issues_attachments();

                $projects_issues_attachments->setIssueId(0);
                $projects_issues_attachments->setUploadedBy($this->getUser()->getId());
                $projects_issues_attachments->setFilesize($image->getClientSize());
                $projects_issues_attachments->setFilename($image->getClientOriginalName());
                $projects_issues_attachments->setFileextension($file_type);
                $projects_issues_attachments->setFileurl($name_image);
                $projects_issues_attachments->setCreatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->persist($projects_issues_attachments);
                $em->flush();

            }
        } else {
            echo 'Error..';
            die();
        }

        $arr = array('name' => $image->getClientOriginalName(), 'id' => $projects_issues_attachments->getId());
        echo json_encode($arr);
        die();
    }


}
