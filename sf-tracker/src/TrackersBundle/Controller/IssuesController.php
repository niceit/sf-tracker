<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use TrackersBundle\Entity\Project_issues;
use TrackersBundle\Entity\Project_issue_assignments;
use TrackersBundle\Entity\Projects;

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
