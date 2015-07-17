<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use TrackersBundle\Entity\Project_category_task;
use TrackersBundle\Entity\Project_task;

class TaskController extends Controller
{
    /**
     * @Route("/task/{project_id}", name="_indexTask")
     * @Template("TrackersBundle:Task:index.html.twig")
     */
    public function indexAction($project_id)
    {
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_category_task');
        $categorys = $repository->findBy(array( 'projectId' => $project_id ) ,array('created' => 'DESC') );
        $arr = array();
        foreach($categorys as $category ){
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_task');
            $tasks = $repository->findBy(array( 'projectId' => $project_id , 'categoryTaskId' => $category->getId() ) ,array('created' => 'DESC') );
            $arr[] = array(
                'tasks' => $tasks,
                'id' => $category->getId(),
                'name' => $category->getName()
            );
        }

        return array( 'project_id' => $project_id , 'categorys' => $arr );
    }

    /**
     * @Route("/ajaxAddTask", name="_ajaxAddTask")
     * @Template("TrackersBundle:Task:index.html.twig")
     */
    public function ajaxAddTaskAction()
    {
        $arr = array();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $title = $requestData->get('title');
            $id_category = $requestData->get('id_category');
            $project_id = $requestData->get('project_id');
            $Project_task = new Project_task();

            $Project_task->setTitle($title);
            $Project_task->setDescription('');
            $Project_task->setCategoryTaskId($id_category);
            $Project_task->setProjectId($project_id);
            $Project_task->setCreatedBy($this->getUser()->getId());
            $Project_task->setCreated(new \DateTime('now'));
            $Project_task->setModified(new \DateTime('now'));
            $Project_task->setDuetime(new \DateTime('now'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($Project_task);
            $em->flush();

            $arr = array(
                'id' => $Project_task->getId(),
                'title' => $Project_task->getTitle(),
                'duetime' => $Project_task->getDuetime()
            );

        }
        echo json_encode($arr);
        die();
    }

}
