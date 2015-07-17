<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use TrackersBundle\Entity\Project_category_task;

class CategoryTaskController extends Controller
{
    /**
     * @Route("/ajaxaddtask", name="_ajaxAddcategoryGroup")
     */
    public function ajaxaddAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $name = $requestData->get('name');
            $project_id = $requestData->get('project_id');
            $Project_category_task = new Project_category_task();
            $Project_category_task->setName($name);
            $Project_category_task->setProjectId($project_id);
            $Project_category_task->setCreatedBy($this->getUser()->getId());
            $Project_category_task->setCreated(new \DateTime('now'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($Project_category_task);
            $em->flush();

        }
        echo $Project_category_task->getId();
        die();
    }
}
