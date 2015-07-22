<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use TrackersBundle\Entity\Project_category_task;
use Symfony\Component\HttpFoundation\Response;

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

            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id   FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND  u.projectId = :projectid ")
                ->setParameter('projectid', $project_id);

        }

        $template = $this->render('TrackersBundle:Task:ajaxAddcategoryGroup.html.twig', array ('project_id'=> $project_id, 'id' => $Project_category_task->getId(), 'name' => $Project_category_task->getName(), 'users' => $query->getResult()));
        return new Response($template->getContent());
        die();
    }
    /**
     * @Route("/ajaxGetFromCategory", name="_ajaxGetFromCategory")
     */
    public function ajaxGetFromCategoryAction()
    {
        $arr = array();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $category_id = $requestData->get('category_id');
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_category_task');
            $arr = $repository->find($category_id);
        }
        $template = $this->render('TrackersBundle:Task:ajaxgetfromeditcategory.html.twig', array ('category_task' => $arr));

        return new Response($template->getContent());
        die();
    }
    /**
     * @Route("/ajaxSaveEditCategory", name="_ajaxSaveEditCategory")
     */
    public function editAction()
    {
        $arr = array();
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $name = $requestData->get('name');
            $category_id = $requestData->get('category_id');
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_category_task');
            $Project_category_task = $repository->find($category_id);

            $Project_category_task->setName($name);
            $Project_category_task->setModified(new \DateTime('now'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($Project_category_task);
            $em->flush();

            $arr = array(
                'id' => $Project_category_task->getId(),
                'name' => $Project_category_task->getName()
            );
        } else {
            die('Error...');
        }
        echo json_encode($arr);
        die();
    }


    /**
     * @Route("/ajaxRemoveCategory", name="_ajaxRemoveCategory")
     */
    public function ajaxRemoveCategoryAction()
    {
        if ($this->getRequest()->getMethod() == 'POST') {
            $requestData = $this->getRequest()->request;
            $id = $requestData->get('category_id');

            $repository_pro = $this->getDoctrine()->getRepository('TrackersBundle:Project_task');
            $tasks = $repository_pro->findBy(array('categoryTaskId' => $id));

            foreach($tasks as $row){
                $em = $this->getDoctrine()->getManager();
                $taskrow = $repository_pro->find($row->getId());
                $em->remove($taskrow);
                $em->flush();
            }

            $repository_pro = $this->getDoctrine()->getRepository('TrackersBundle:Project_category_task');
            $em = $this->getDoctrine()->getManager();
            $task = $repository_pro->find($id);
            $em->remove($task);
            $em->flush();
            echo $id;
            exit;
        } else {
            die('Error...');
        }
        echo 0;
        die();
    }


}
