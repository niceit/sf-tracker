<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use TrackersBundle\Models\Document;
use TrackersBundle\Entity\Project_Task_Attachments;
use TrackersBundle\Entity\Project_Task_Comments;
use TrackersBundle\Entity\Pagination;
use Symfony\Component\HttpFoundation\Response;

class TaskCommentsController extends Controller
{
    /**
     * @Route("/uploadfiletaskcomment/{task_id}", name="_uploadfiletaskcomments")
     */
    public function uploadFileAction ($task_id, Request $request)
    {
        $files = $request->files->get('files');
        if (!empty($files)){
            foreach ($files as $image){
                $fs = new Filesystem();
                $file = 'upload/task-comment/'.$task_id;
                $dir = 'task-comment/'.$task_id;
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

                $projects_task_attachments = new Project_Task_Attachments();

                $projects_task_attachments->setTaskId(0);
                $projects_task_attachments->setUploadedBy($this->getUser()->getId());
                $projects_task_attachments->setFilesize($image->getClientSize());
                $projects_task_attachments->setFilename($image->getClientOriginalName());
                $projects_task_attachments->setFileextension($file_type);
                $projects_task_attachments->setFileurl($name_image);
                $projects_task_attachments->setCreatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->persist($projects_task_attachments);
                $em->flush();

            }
        } else {
            echo 'Error..';
            die();
        }

        $arr = array('name' => $image->getClientOriginalName(), 'size' => number_format($image->getClientSize()), 'id' => $projects_task_attachments->getId());
        echo json_encode($arr);
        die();
    }

    /**
     * @Route("/ajaxAddComment", name="_ajaxAddComment")
     */
    public function saveCommentAction ()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $file_id = $requestData->get('file');
            $project_id = $requestData->get('project_id');
            $task_id = $requestData->get('task_id');
            $comment = $requestData->get('comment');

            $project_Task_Comments = new Project_Task_Comments();
            $project_Task_Comments->setCreatedBy($this->getUser()->getId());
            $project_Task_Comments->setProjectId($project_id);
            $project_Task_Comments->setTaskId($task_id);
            $project_Task_Comments->setComment($comment);
            $project_Task_Comments->setCreatedAt(new \DateTime("now"));

            $em = $this->getDoctrine()->getManager();
            $em->persist($project_Task_Comments);
            $em->flush();

            if (!empty($file_id)){
                foreach ($file_id as $file){
                    $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_Task_Attachments');
                    $files = $repository->find($file);
                    $files->setCommentId($project_Task_Comments->getId());
                    $em->persist($files);
                    $em->flush();
                }
            }
        }
        echo 1;
        die();

    }

    /**
     * @Route("/ajaxGetCommentTask", name="_ajaxGetCommentTask")
     */
    public function ajaxGetCommentTaskAction ()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $page = $requestData->get('page');
            $task_id = $requestData->get('task_id');

            $arr_comments = array();
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_Task_Comments');
            $tasksAll = $repository->findBy(array('taskId' => $task_id ));

            $limit =  $this->container->getParameter( 'limit_comment_issues');
            $offset = $page*$limit;

            $total = (int)( count($tasksAll) / $limit);
            $count = count($tasksAll);
            if ($count > $limit &&  $count  % $limit != 0){
                $total = $total + 1;
            }

            $pagination = new Pagination();
            $paginations = $pagination->render($page, $total, 'loadcomments');

            $em = $this->container->get('doctrine')->getManager();
            $query = $em->createQuery("SELECT t, u.firstname , u.lastname , u.avatar  FROM TrackersBundle:Project_Task_Comments t , TrackersBundle:UserDetail u  WHERE  t.createdBy = u.user_id AND t.taskId =:taskId   ORDER BY t.createdAt DESC ")
                ->setMaxResults($limit)
                ->setFirstResult($offset)
                ->setParameter('taskId', $task_id);
            $tasks = $query->getResult();

            foreach ($tasks as $task){
                // is avatar
                if ($task['avatar'] != ''){
                    if (file_exists($this->get('kernel')->getRootDir() . '/../web'.$task['avatar']) ) {
                        $is_avatar = true;
                    } else $is_avatar = false;
                } else $is_avatar = false;

                if ($this->getUser()->getId() == $task[0]->getCreatedBy() ){
                    $is_role = true;
                } else {
                    $is_role = false;
                }
                $repository_comment = $this->getDoctrine()->getRepository('TrackersBundle:Project_Task_Attachments');
                $file_attach = $repository_comment->findBy(array('commentId' => $task[0]->getId() ));

                $arr_comments[] = array(
                    'fullName' => $task['firstname'] . ' ' . $task['lastname'],
                    'avatar' => $task['avatar'],
                    'is_avatar' => $is_avatar,
                    'id' => $task[0]->getId(),
                    'comment' => $task[0]->getComment(),
                    'createdAt' => $task[0]->getCreatedAt(),
                    'is_role' => $is_role,
                    'attachments' => $file_attach
                );
            }

            $template = $this->render('TrackersBundle:Task:ajaxGetCommentTask.html.twig', array ('tasks' => $arr_comments,'task_id' => $task_id , 'page' => $page, 'paginations' => $paginations, 'user_id' => $this->getUser()->getId()));
            return new Response($template->getContent());
            die();
        }
        die('Error...');
    }

    /**
     * @Route("/ajaxRemoveCommentTask", name="_ajaxRemoveCommentTask")
     */
    public function ajaxRemoveCommentTaskAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('id');

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_Task_Comments');
            $comments = $repository->find($id);
            $em = $this->getDoctrine()->getManager();
            $em->remove($comments);
            $em->flush();

            echo 1;
            exit;
        }
        echo 0;
        exit;
    }

    /**
     * @Route("/ajaxUpdateCommentTask", name="_ajaxUpdateCommentTask")
     */
    public function ajaxUpdateCommentTaskAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $file_id = $requestData->get('file');
            $comment_id = $requestData->get('comment_id');
            $comment = $requestData->get('comment');

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_Task_Comments');
            $comments = $repository->find($comment_id);

            $comments->setComment($comment);
            $comments->setUpdatedAt(new \DateTime("now"));

            $em = $this->getDoctrine()->getManager();
            $em->persist($comments);
            $em->flush();

            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("UPDATE TrackersBundle:Project_Task_Attachments u SET u.commentId = NULL  WHERE  u.commentId = :commentId ")
                ->setParameter('commentId', $comment_id);
            $query->execute();

            if (!empty($file_id)){
                foreach ($file_id as $file){
                    $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_Task_Attachments');
                    $files = $repository->find($file);
                    $files->setCommentId($comment_id);
                    $em->persist($files);
                    $em->flush();
                }
            }
            echo 1;
        }

        die();
    }



}
