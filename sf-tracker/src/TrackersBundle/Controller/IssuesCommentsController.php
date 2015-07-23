<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Acl\Exception\Exception;
use TrackersBundle\Models\Document;
use Symfony\Component\HttpFoundation\Session\Session;

use TrackersBundle\Entity\Projects_issues_attachments;
use TrackersBundle\Entity\Projects_issues_comments;
use TrackersBundle\Entity\Pagination;
use Symfony\Component\HttpFoundation\Response;
use TrackersBundle\Entity\Users_activity;
use TrackersBundle\Entity\Notifications;



class IssuesCommentsController extends Controller
{
    /**
     * @Route("/uploadfile/{id}/{issue_id}", name="_uploadfile")
     */
    public function uploadFileAction($id,$issue_id,Request $request)
    {
        $files = $request->files->get('files');
        if (!empty($files)) {
            foreach ($files as $image) {
                $fs = new Filesystem();
                $file = 'upload/project_issues_comment/' . $id;
                $dir = 'project_issues_comment/' . $id;
                if (!$fs->exists($file)) {
                    $fs->mkdir($file);
                }
                $arr = array ();
                $file_type = $image->getClientOriginalExtension();
                $name_array = explode('.', $image->getClientOriginalName());
                $name_file = strtolower($name_array[0] . "-" . md5(rand(0, 100)));
                $document = new Document();
                $document->setFile($image);
                $document->setSubDirectory($dir);
                $document->setNameFile($name_file);
                $document->setTypeFile(strtolower($file_type));
                $document->processFile();
                $name_image = $document->getSubDirectory() . "/" . $name_file . "." . $file_type;
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
                $Session = $request->getSession();
                $Session->start();
                if ($Session->get('comment_id'))
                    $arrComment = $Session->get('comment_id');
                else
                    $arrComment = array ();
                $arrComment[] = $projects_issues_attachments->getId();
                $Session->set('comment_id', $arrComment);
                $arr['id'] = $projects_issues_attachments->getId();
                $arr['name'] = $image->getClientOriginalName();
                $arr['size'] = number_format($image->getClientSize());
                echo json_encode($arr);
                die();
            }
        }
        die();
    }

    /**
     * @Route("/getuploadfile", name="_getuploadfile")
     */
    public function getUploadFileAction()
    {
        $Session = new Session();
        echo json_encode($Session->get('comment_id'));
        $Session->remove('comment_id');
        die();
    }

    /**
     * @Route("/savecomment", name="_savecomment")
     */
    public function saveCommentAction()
    {
        $requestData = $this->getRequest()->request;
        $comment = $requestData->get('comment');
        $issue_id = $requestData->get('issue_id');
        $project_id = $requestData->get('project_id');
        $file_id = $requestData->get('file_id');

        $projects_issues_comments = new Projects_issues_comments();
        $projects_issues_comments->setCreatedBy($this->getUser()->getId());
        $projects_issues_comments->setProjectId($project_id);
        $projects_issues_comments->setIssueId($issue_id);
        $projects_issues_comments->setComment($comment);
        $projects_issues_comments->setCreatedAt(new \DateTime("now"));

        $em = $this->getDoctrine()->getManager();
        $em->persist($projects_issues_comments);
        $em->flush();

        $users_activity = new Users_activity();
        $users_activity->setUserId($this->getUser()->getId());
        $users_activity->setParentId($project_id);
        $users_activity->setItemId($issue_id);
        $users_activity->setTypeId(2);
        $users_activity->setActionId($projects_issues_comments->getId());
        $users_activity->setCreatedAt(new \DateTime("now"));
        $em->persist($users_activity);
        $em->flush();

        if (!empty($file_id)){
            foreach ($file_id as $file){
                $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
                $files = $repository->find($file);
                $files->setCommentId($projects_issues_comments->getId());
                $em->persist($files);
                $em->flush();
            }
        }

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $users = $repository->findBy(array('user_id' => $this->getUser()->getId()));
        $user = $users[0];

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository->find($project_id);
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $issue = $repository->find($issue_id);

        // Get user detail
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $users = $repository->findBy(array('user_id' => $this->getUser()));
        $user = $users[0];

        $repository_attachments = $this->getDoctrine()->getRepository('TrackersBundle:Project_issue_assignments');
        $attachments = $repository_attachments->findBy(array ('issueId' => $issue->getId()));
        // notifiaction user attachment
        $notifications = new Notifications();
        $text = $user->getFirstname().' '.$user->getLastname()." is comment issue with content ".$comment;

        // send user create project----
        if ($this->getUser()->getId() != $project->getOwnerId()){
            $notifications = new Notifications();
            $notifications->setUserId($project->getOwnerId());
            $notifications->setIssueId($issue->getId());
            $notifications->setProjectId($project_id);
            $notifications->setCreated(new \DateTime("now"));
            $notifications->setIsRead(false);
            $notifications->setText($text);
            $em->persist($notifications);
            $em->flush();
        }
        // send user do issues----
        if (!empty($attachments)){
            foreach ($attachments as $attachment){
                if ($this->getUser()->getId() != $attachment->getUserId()){
                    $notifications = new Notifications();
                    $notifications->setUserId($attachment->getUserId());
                    $notifications->setIssueId($issue->getId());
                    $notifications->setProjectId($project_id);
                    $notifications->setCreated(new \DateTime("now"));
                    $notifications->setIsRead(false);
                    $notifications->setText($text);
                    $em->persist($notifications);
                    $em->flush();
                }

            }
        }
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $users = $repository->findBy(array('user_id'=>$this->getUser()->getId()));
        $user = $repository->find($users[0]->getId());
        $arr_comment = array(
            'full_name' => $user->getFirstname()." ".$user->getLastname(),
            'created'   => $projects_issues_comments->getCreatedAt(),
            'comment'   => $projects_issues_comments->getComment(),
        );

        echo json_encode($arr_comment);
        die();
    }

    /**
     * @Route("/ajaxgetcomment", name="_ajaxgetcomment")
     */
    public function ajaxGetCommentAction()
    {
        $requestData = $this->getRequest()->request;
        $issueId = $requestData->get('issueId');
        $project_id = $requestData->get('project_id');
        $page = $requestData->get('page');

        $repository_project_issues = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $issue = $repository_project_issues->find($issueId);
        $arr_comments = array();
        $repository_comment = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_comments');

        $limit =  $this->container->getParameter( 'limit_comment_issues');
        $offset = $page*$limit;

        $total = (int)( count($repository_comment->findBy(array( 'issueId' => $issueId ), array('createdAt' => 'DESC'))) / $limit);
        $count = count($repository_comment->findBy(array( 'issueId' => $issueId ), array('createdAt' => 'DESC')));
        if ($count > $limit &&  $count  % $limit != 0){
            $total = $total + 1;
        }

        $pagination = new Pagination();
        $paginations = $pagination->render($page, $total, 'loadcomments');

        $comments = $repository_comment->findBy( array( 'issueId' => $issueId ), array('createdAt' => 'DESC'),$limit,$offset );

        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        foreach ($comments as $comment){
        $repository_attachments = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
        $attachments = $repository_attachments->findBy( array( 'commentId' => $comment->getId() ), array('createdAt' => 'ASC') );

        $users_comment = $repository_user->findBy(array('user_id'=>$comment->getCreatedBy()));
        $is_update = false;
        if ($comment->getCreatedBy()==$this->getUser()->getId())
        {
            $is_update = true;
        }

        if (!empty($users_comment) && $users_comment[0]->getAvatar() != '' ){
            if (file_exists($this->get('kernel')->getRootDir() . '/../web'.$users_comment[0]->getAvatar()) ) {
                $is_avatar = true;
            } else $is_avatar = false;
        } else $is_avatar = false;

        if ($issue->getStatus() == 'CLOSED')
            $is_update = false;
            $arr_comments[] = array(
                'fullname'  => $users_comment[0]->getFirstname()." ".$users_comment[0]->getLastname(),
                'created_at' => $comment->getCreatedAt(),
                'comment' => $comment->getComment(),
                'id'    => $comment->getId(),
                'attachments' => $attachments,
                'id_update' => $is_update,
                'is_avatar' => $is_avatar,
                'avatar' => $users_comment[0]->getAvatar()
            );
        }

        $repository_Closed_Issues = $this->getDoctrine()->getRepository('TrackersBundle:Project_Closed_Issues');
        $Closed_Issues = $repository_Closed_Issues->findBy(array('userId' => $this->getUser()->getId(), 'issueId' => $issueId ));
        $array_date = array();

        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        foreach ($Closed_Issues as $closed){
            $users = $repository_user->findBy(array ('user_id' => $issue->getCreatedBy()));

            $date1 = $closed->getStartDate();
            $date2 = $closed->getEndDate();
            $diff = $date2->diff($date1);
            if ($diff->format('%i') > 0 )
                $h = $diff->format('%d')*24 + $diff->format('%h') . " hours " . $diff->format('%i') . " minute";
            else
                $h = $diff->format('%d')*24 + $diff->format('%h') . " hours ";
            $array_date[] = array(
                'full_name' => $users[0]->getFirstname()." ".$users[0]->getLastname(),
                'start_date' => $closed->getStartDate(),
                'end_date' => $closed->getEndDate(),
                'total_time' => $h

            );
        }

        $template =  $this->render('TrackersBundle:Comments:ajax_getcomment.html.twig', array( 'comments' => $arr_comments , 'issueId' => $issueId, 'project_id' => $project_id,  'paginations' => $paginations , 'page' => $page, 'status_issue' => $issue->getStatus(), 'completes' => $array_date ));
        return new Response($template->getContent());
        exit();
    }

    /**
     * @Route("/ajaxromovecomment", name="_ajaxromovecomment")
     */
    public function ajaxRomoveCommentAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('id');

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_comments');
            $comments = $repository->find($id);
            $em = $this->getDoctrine()->getManager();
            $em->remove($comments);
            $em->flush();

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Users_activity');
            $comments = $repository->findBy(array('actionId'=>$id));
            $comment = $repository->find($comments[0]->getId());
            $em = $this->getDoctrine()->getManager();
            $em->remove($comment);
            $em->flush();

            echo 1;
            exit;
        }
        echo 0;
        exit;
    }

    /**
     * @Route("/ajaxupdatecomment", name="_ajaxupdatecomment")
     */
    public function ajaxUpdateCommentAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('id');
            $page = $requestData->get('page');
            $file_id = $requestData->get('file');
            $content= $requestData->get('comment');

            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_comments');
            $comment = $repository->find($id);
            $comment->setComment($content);
            $comment->setUpdatedAt(new \DateTime('now'));
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("UPDATE TrackersBundle:Projects_issues_attachments u SET u.commentId = NULL  WHERE  u.commentId = :commentId ")
                ->setParameter('commentId', $id);
            $query->execute();

            if (!empty($file_id)){
                foreach ($file_id as $file){
                    $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
                    $files = $repository->find($file);
                    $files->setIssueId($comment->getIssueId());
                    $files->setCommentId($id);
                    $em->persist($files);
                    $em->flush();
                }
            }

            echo $page;
            die();
        }
        echo 0;
        die();
    }


}
