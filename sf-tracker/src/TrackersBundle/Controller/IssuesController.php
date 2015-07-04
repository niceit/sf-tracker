<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use TrackersBundle\Entity\Project_issues;
use TrackersBundle\Entity\Project_issue_assignments;
use TrackersBundle\Entity\Projects;
use TrackersBundle\Entity\Pagination;
use TrackersBundle\Entity\UserDetail;
use Symfony\Component\Filesystem\Filesystem;



class IssuesController extends Controller
{
    /**
     * @Route("/issue", name="_listIssue")
     * @Template("TrackersBundle:Issues:index.html.twig")
     */
    public function indexAction()
    {

        return array();
    }

    /**
     * @Route("/ajax_list_open_issues", name="_ajaxListOpenIssues")
     */
    public function ajax_open_issuesAction()
    {
        $requestData = $this->getRequest()->request;
        $page = $requestData->get('page');
        $project_id = $requestData->get('project_id');
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');

        $limit =  $this->container->getParameter( 'limit_open_issues');
        $offset = $page*$limit;
        $total = (int)round( count($repository->findBy(array('projectId'=>$project_id, 'status' => 'OPEN' ))) / $limit);

        $pagination = new Pagination();
        $paginations = $pagination->render($page,$total,'load_open');
        $issues = $repository->findBy( array( 'projectId'=>$project_id , 'status' => 'OPEN' ),array('created' => 'ASC'),$limit,$offset);

        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $arr = array();
        foreach( $issues as $issue ){
            $user = $repository_user->findBy(array('user_id'=>$issue->getCreatedBy()));
            $arr[] = array(
                'id'    => $issue->getId(),
                'title' => $issue->getTitle(),
                'created' => $issue->getCreated(),
                'users'  =>$user
            );
        }

        echo $this->render('TrackersBundle:Issues:ajax_open.html.twig', array( 'issues' => $arr , 'paginations'=>$paginations ,'project_id' => $project_id));
        die();
    }


    /**
     * @Route("/ajax_list_close_issues", name="_ajaxListCloseIssues")
     */
    public function ajax_close_issuesAction()
    {
        $requestData = $this->getRequest()->request;
        $page = $requestData->get('page');
        $project_id = $requestData->get('project_id');
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');

        $limit =  $this->container->getParameter( 'limit_open_issues');
        $offset = $page*$limit;
        $total = (int)round( count($repository->findBy(array('projectId'=>$project_id, 'status' => 'CLOSED' ))) / $limit);

        $pagination = new Pagination();
        $paginations = $pagination->render($page,$total,'load_close');
        $issues = $repository->findBy( array( 'projectId'=>$project_id , 'status' => 'CLOSED' ),array('created' => 'ASC'),$limit,$offset);

        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $arr = array();
        foreach( $issues as $issue ){
            $user = $repository_user->findBy(array('user_id'=>$issue->getCreatedBy()));
            $arr[] = array(
                'id'    => $issue->getId(),
                'title' => $issue->getTitle(),
                'created' => $issue->getCreated(),
                'users'  =>$user
            );
        }

        echo $this->render('TrackersBundle:Issues:ajax_close.html.twig', array( 'issues' => $arr , 'paginations'=>$paginations ,'project_id' => $project_id ));
        die();
    }



    /**
     * @Route("/ajax_list_assigned_issues", name="_ajaxListAssignedIssues")
     */
    public function ajax_assigned_issuesAction()
    {
        $requestData = $this->getRequest()->request;
        $page = $requestData->get('page');
        $project_id = $requestData->get('project_id');
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');

        $limit =  $this->container->getParameter( 'limit_open_issues');
        $offset = $page*$limit;
        $total = (int)round( count($repository->findBy(array('projectId'=>$project_id, 'status' => 'OPEN' ))) / $limit);

        $pagination = new Pagination();
        $paginations = $pagination->render($page,$total,'load_assigned');
        $issues = $repository->findBy( array( 'projectId'=>$project_id , 'status' => 'OPEN' ),array('created' => 'ASC'),$limit,$offset);

        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $arr = array();
        foreach( $issues as $issue ){
            $user = $repository_user->findBy(array('user_id'=>$issue->getCreatedBy()));
            $arr[] = array(
                'id'    => $issue->getId(),
                'title' => $issue->getTitle(),
                'created' => $issue->getCreated(),
                'users'  =>$user
            );
        }

        echo $this->render('TrackersBundle:Issues:ajax_assigned.html.twig', array( 'issues' => $arr , 'paginations'=>$paginations ,'project_id' => $project_id ));
        die();
    }
    /**
     * @Route("/issues/{id}/{project_id}", name="_project_issues")
     * @Template("TrackersBundle:Issues:project_issue.html.twig")
     */
    public function project_issuesAction($id,$project_id)
    {
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $issue = $repository->find($id);

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository->find($project_id);

        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $users = $repository_user->findBy(array('user_id'=>$issue->getCreatedBy()));
        $user = $repository_user->find($users[0]->getId());

        return array( 'issue' => $issue , 'project' => $project , 'user' => $user );

    }

    /**
     * @Route("/issue/add/{id}", name="_addIssues")
     * @Template("TrackersBundle:Issues:add.html.twig")
     */
    public function addAction($id)
    {
        $issue = new Project_issues();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $form = $requestData->get('form');

            $issue->setTitle($form['title']);
            $issue->setDescription($form['description']);
            $issue->setStatus($form['status']);
            $issue->setCreated(new \DateTime("now"));
            $issue->setModified(new \DateTime("now"));
            $issue->setProjectId($id);
            $issue->setCreatedBy($this->getUser()->getId());


            $em = $this->getDoctrine()->getManager();
            $em->persist($issue);
            $em->flush();

            $project_issue_assignments = new Project_issue_assignments();
            $project_issue_assignments->setUserId($this->getUser()->getId());
            $project_issue_assignments->setIssueId($issue->getId());
            $em->persist($project_issue_assignments);
            $em->flush();

            $this->get('session')->getFlashBag()->add('notice', 'More successful Issues!');
            return $this->redirectToRoute('_addIssues',array('id'=>$id));
        }

        $form = $this->createFormBuilder($issue)
            ->add('title', 'text', array('label'=>'Tile','required'    => true))
            ->add('description', 'textarea', array('required' => false,
                'label'=>'Description','attr' => array('class' => 'editor', 'id'=>'editor')))
            ->add('status', 'choice', array(
                'choices' => array('OPEN' => 'OPEN', 'HOLD' => 'HOLD', 'INPROGRESS' => 'INPROGRESS', 'CLOSED' => 'CLOSED','FINISHED' => 'FINISHED'),
               // 'preferred_choices' => array('OPEN'),
                'label'=>'Status'
            ))
            ->getForm();

        return array('form' => $form->createView());
    }


    /**
     * @Route("/issue/edit/{id}", name="_editIssues")
     * @Template("TrackersBundle:Issues:edit.html.twig")
     */
    public function editAction($id)
    {
        $issue = new Project_issues();
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $form = $requestData->get('form');

            $issue = $repository->find($id);
            $issue->setTitle($form['title']);
            $issue->setDescription($form['description']);
            $issue->setStatus($form['status']);
            $issue->setCreated(new \DateTime("now"));
            $issue->setModified(new \DateTime("now"));
            $issue->setProjectId($id);
            $issue->setCreatedBy($this->getUser()->getId());


            $em = $this->getDoctrine()->getManager();
            $em->persist($issue);
            $em->flush();


            $this->get('session')->getFlashBag()->add('notice', 'More update Issues!');
        }

        $issues_id = $repository->find($id);
        $issue->setTitle($issues_id->getTitle());
        $issue->setDescription($issues_id->getDescription());
        $issue->setStatus($issues_id->getStatus());

        $form = $this->createFormBuilder($issue)
            ->add('title', 'text', array('label'=>'Tile','required'    => true))
            ->add('description', 'textarea', array('required' => false,
                'label'=>'Description','attr' => array('class' => 'editor', 'id'=>'editor')))
            ->add('status', 'choice', array(
                'choices' => array('OPEN' => 'OPEN', 'HOLD' => 'HOLD', 'INPROGRESS' => 'INPROGRESS', 'CLOSED' => 'CLOSED','FINISHED' => 'FINISHED'),
                 'preferred_choices' => array($issues_id->getStatus()),
                'label'=>'Status'
            ))
            ->getForm();

        return array('form' => $form->createView());
    }

}
