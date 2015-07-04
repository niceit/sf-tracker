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

class IssuesCommentsController extends Controller
{
    /**
     * @Route("/uploadfile/{id}/{issue_id}", name="_uploadfile")
     */
    public function uploadfileAction($id,$issue_id,Request $request)
    {
        $image = $request->files->get('file');

        $fs = new Filesystem();
        $file = 'upload/project_issues/'.$id;
        $dir = 'project_issues/'.$id;
        if(!$fs->exists($file)){
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
        $document->setTypeFile($file_type);
        $document->processFile();
        $name_image = $document->getSubDirectory() . "/" . $name_file. "." . $file_type;

        $projects_issues_attachments = new Projects_issues_attachments();

        $projects_issues_attachments->setIssueId($issue_id);
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
        if($Session->get('comment_id'))
            $arrComment = $Session->get('comment_id');
        else
            $arrComment = array();
        $arrComment[] = $projects_issues_attachments->getId();
        $Session->set('comment_id',$arrComment );


        $arr['id'] = $projects_issues_attachments->getId();
        echo $projects_issues_attachments->getId();//json_encode($arr);
        die();
    }
    /**
     * @Route("/getuploadfile", name="_getuploadfile")
     */
    public function getuploadfileAction()
    {
        $Session = new Session();
        echo json_encode($Session->get('comment_id'));
        $Session->remove('comment_id');
        die();
    }

    /**
     * @Route("/savecomment", name="_savecomment")
     */
    public function savecommentAction()
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
        if(!empty($file_id)){
            foreach($file_id as $file){
                $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
                $files = $repository->find($file);
                $files->setCommentId($projects_issues_comments->getId());
                $em->persist($files);
                $em->flush();
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
    public function ajaxgetcommentAction()
    {
        $requestData = $this->getRequest()->request;
        $issueId = $requestData->get('issueId');
        $page = $requestData->get('page');
        $arr_comments = array();
        $repository_comment = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_comments');

        $limit =  $this->container->getParameter( 'limit_comment_issues');
        $offset = $page*$limit;

        $total = (int)( count($repository_comment->findBy(array( 'issueId' => $issueId ), array('createdAt' => 'DESC'))) / $limit);
        $count = count($repository_comment->findBy(array( 'issueId' => $issueId ), array('createdAt' => 'DESC')));
        if($count > $limit &&  $count  % $limit != 0){
            $total = $total + 1;
        }



        $pagination = new Pagination();
        $paginations = $pagination->render($page,$total,'loadcomments');


        $comments = $repository_comment->findBy( array( 'issueId' => $issueId ), array('createdAt' => 'DESC'),$limit,$offset );

        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        foreach($comments as $comment){
        $repository_attachments = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
        $attachments = $repository_attachments->findBy( array( 'commentId' => $comment->getId() ), array('createdAt' => 'ASC') );


        $users_comment = $repository_user->findBy(array('user_id'=>$comment->getCreatedBy()));

        $is_update = false;
        if($comment->getCreatedBy()==$this->getUser()->getId())
        {
        $is_update = true;
        }
            $arr_comments[] = array(
                'fullname'  => $users_comment[0]->getFirstname()." ".$users_comment[0]->getLastname(),
                'created_at' => $comment->getCreatedAt(),
                'comment' => $comment->getComment(),
                'id'    => $comment->getId(),
                'attachments' => $attachments,
                'id_update' => $is_update
            );
        }
        $template =  $this->render('TrackersBundle:Comments:ajax_getcomment.html.twig', array( 'comments' => $arr_comments , 'paginations' => $paginations , 'page' => $page));
        return new Response($template->getContent());
        exit();
    }
    /**
     * @Route("/ajaxromovecomment", name="_ajaxromovecomment")
     */
    public function ajax_romove_commentAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('id');


            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_comments');
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

}
