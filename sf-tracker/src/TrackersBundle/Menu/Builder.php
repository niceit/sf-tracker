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

            $query = $em->createQuery("SELECT p FROM TrackersBundle:Projects p WHERE  p.owner_id = :user_id OR p.id IN ( SELECT up.projectId FROM TrackersBundle:Project_issues up WHERE up.assignedTo = :assigned_to)  ORDER BY p.created DESC ")
                ->setParameter('user_id', $this->getUser()->getId())
                ->setParameter('assigned_to', $this->getUser()->getId());

            $projects = $query->getResult();
            foreach($projects as $project){
                $menu->addChild($project->getName(), array('route' => '_detailProjects',  'routeParameters' => array('id' => $project->getId())))->setAttribute('class','detail-projects ajax-request-btn');
            }
            // ... add more children

            return $menu;
    }
}