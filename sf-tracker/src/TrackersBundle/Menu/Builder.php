<?php

// src/AppBundle/Menu/Builder.php
namespace TrackersBundle\Menu;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends Controller
{

    public function mainMenu(FactoryInterface $factory, array $options)
    {
            $menu = $factory->createItem('root');

            // you can also add sub level's to your menu's as follows
          //  $menu->addChild('Home', array('route' => '_home'))->setAttribute('class','home');
           //  $menu->addChild('Project')->setAttribute('class','project');
                $em = $this->container->get('doctrine')->getManager();



            $query = $em->createQuery("SELECT p FROM TrackersBundle:Projects p
                                        WHERE  p.owner_id = :user_id OR p.id IN
                                         ( SELECT up.projectId
                                         FROM TrackersBundle:User_projects up
                                         WHERE up.userId = :assigned_to)
                                           ORDER BY p.created DESC ")
                ->setParameter('user_id', $this->getUser()->getId())
                ->setParameter('assigned_to', $this->getUser()->getId());

            $projects = $query->getResult();



            foreach($projects as $project){
                $menu->addChild($project->getName(), array('route' => '_detailProjects',  'routeParameters' => array('id' => $project->getId(),'tab' => 1)))->setAttribute('class','detail-projects ajax-request-btn');
            }
            // ... add more children

            return $menu;
    }
    public function notifications(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $em = $this->container->get('doctrine')->getManager();
      //  $menu->addChild('Home')->setAttributes(array('class'=>'sss', 'onclick'=>'onclick()'));

        $query = $em->createQuery("SELECT p FROM TrackersBundle:Notifications p WHERE  p.userId = :user_id AND p.isRead = 0   ORDER BY p.created DESC ")
            ->setParameter('user_id', $this->getUser()->getId());

        $notifications = $query->getResult();
        foreach($notifications as $notification){
            $menu->addChild($notification->getText(), array('route' => '_project_issues',  'routeParameters' => array('id' => $notification->getIssueId(), 'project_id' => $notification->getProjectId())))->setAttribute('class','ajax-request-btn');
        }


        // ... add more children
        return $menu->setAttributes(array('class' => 'userDropdown'));
    }


    public function menuUser(FactoryInterface $factory, array $options, \Symfony\Component\HttpFoundation\Request $request)
    {
        $menu = $factory->createItem('root');
        $em = $this->container->get('doctrine')->getManager();
        //  $menu->addChild('Home')->setAttributes(array('class'=>'sss', 'onclick'=>'onclick()'));

        $query = $em->createQuery("SELECT p FROM TrackersBundle:Notifications p WHERE  p.userId = :user_id AND p.isRead = 0   ORDER BY p.created DESC ")
            ->setParameter('user_id', $this->getUser()->getId());

        $notifications = $query->getResult();
        foreach($notifications as $notification){
            $menu->addChild($notification->getText(), array('route' => '_project_issues',  'routeParameters' => array('id' => $notification->getIssueId(), 'project_id' => $notification->getProjectId())))->setAttribute('class','ajax-request-btn');
        }




        // ... add more children
        return $menu->setAttributes(array('class' => 'userDropdown'));
    }

}