<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    /**
     * @Route("/", name="_home")
     * @Template("TrackersBundle:Dashboard:index.html.twig")
     */
    public function indexAction()
    {
        $project_issues = array();
        $em = $this->container->get('doctrine')->getManager();
        $query = $em->createQuery("SELECT p FROM TrackersBundle:Projects p WHERE  p.owner_id = :user_id OR p.id IN ( SELECT up.projectId FROM TrackersBundle:Project_issues up WHERE up.assignedTo = :assigned_to)  ORDER BY p.created DESC ")
            ->setParameter('user_id', $this->getUser()->getId())
            ->setParameter('assigned_to', $this->getUser()->getId());

        $projects = $query->getResult();
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Users_activity');
        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $repository_activity = $this->getDoctrine()->getRepository('TrackersBundle:Project_activity');
        $repository_issues = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $repository_issues_comments = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_comments');



        foreach($projects as $project){

            $activitys = $repository->findBy(array ('parentId' => $project->getId() ), array ('createdAt' => 'DESC'),5, 0);
            $arr = array ();
            foreach ($activitys as $activity) {
                $user = $repository_user->findBy(array ('user_id' => $activity->getUserId()));
                $activity_type = $repository_activity->find($activity->getTypeId());

                $issues = $repository_issues->find($activity->getItemId());
                if(!empty($issues)){
                    $tile_issue = $issues->getTitle();
                }else
                {
                    $tile_issue =  '';
                }
                if($activity->getActionId()!=''){
                    $comments = $repository_issues_comments->find($activity->getActionId());
                    if(!empty($comments)){
                        $comment = $comments->getComment();
                    }else $comment = '';
                }else $comment = '';


                $arr[] = array (
                    'id' => $activity->getId(),
                    'created' => $activity->getCreatedAt(),
                    'type' => $activity_type->getDescription(),
                    'type_id' => $activity->getTypeId(),
                    'issue' => $tile_issue,
                    'comment' => $comment,
                    'project_id' => $activity->getParentId(),
                    'issue_id' => $activity->getItemId(),
                    'users' => $user[0]->getFirstname()." ".$user[0]->getLastname()
                );
            }

            $project_issues[] = array(
                'id' => $project->getId(),
                'name' => $project->getName(),
                'activitys' => $arr
            );
        }

        return array( 'project_issues' => $project_issues );
    }
    /**
     * @Route("/number-notifications", name="_numberNotifications")
     */
    public function NumberNotificationsAction()
    {
        $em = $this->container->get('doctrine')->getManager();
        $query = $em->createQuery("SELECT p FROM TrackersBundle:Notifications p WHERE  p.userId = :user_id AND p.isRead = 0   ORDER BY p.created DESC ")
            ->setParameter('user_id', $this->getUser()->getId());

        echo count($query->getResult());
        die();
    }

    /**
     * @Route("/notifications", name="_Notifications")
     */
    public function NotificationsAction()
    {
        $em = $this->container->get('doctrine')->getManager();
        $query = $em->createQuery("SELECT p FROM TrackersBundle:Notifications p WHERE  p.userId = :user_id AND p.isRead = 0   ORDER BY p.created DESC ")
            ->setParameter('user_id', $this->getUser()->getId());

        $template = $this->render('TrackersBundle:Dashboard:ajaxnotifications.html.twig', array ('notifications' => $query->getResult(), 'total' => count($query->getResult())));
        return new Response($template->getContent());
        die();
    }

}
