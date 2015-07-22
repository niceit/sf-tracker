<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use TrackersBundle\Models\Document;
use TrackersBundle\Entity\Projects_issues_attachments;
use TrackersBundle\Entity\Project_Task_Comments;

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
                    $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
                    $files = $repository->find($file);
                    $files->setTaskId($task_id);
                    $files->setCommentId($project_Task_Comments->getId());
                    $em->persist($files);
                    $em->flush();
                }
            }
        }
        echo 1;
        die();

    }

}
