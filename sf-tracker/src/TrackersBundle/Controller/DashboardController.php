<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use TrackersBundle\Entity\Pagination;

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
        $query = $em->createQuery("SELECT p FROM TrackersBundle:Projects p WHERE  p.owner_id = :user_id OR p.id IN ( SELECT up.projectId FROM TrackersBundle:User_projects up WHERE up.userId = :assigned_to)  ORDER BY p.created DESC ")
            ->setParameter('user_id', $this->getUser()->getId())
            ->setParameter('assigned_to', $this->getUser()->getId());

        $projects = $query->getResult();
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Users_activity');
        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
        $repository_activity = $this->getDoctrine()->getRepository('TrackersBundle:Project_activity');
        $repository_issues = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $repository_issues_comments = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_comments');

        foreach ($projects as $project){
            $activitys = $repository->findBy(array ('parentId' => $project->getId() ), array ('createdAt' => 'DESC'),5, 0);
            $arr = array ();
            foreach ($activitys as $activity) {
                $user = $repository_user->findBy(array ('user_id' => $activity->getUserId()));
                $activity_type = $repository_activity->find($activity->getTypeId());

                $issues = $repository_issues->find($activity->getItemId());
                if (!empty($issues)){
                    $tile_issue = $issues->getTitle();
                } else {
                    $tile_issue =  '';
                }
                if ($activity->getActionId() != ''){
                    $comments = $repository_issues_comments->find($activity->getActionId());
                    if(!empty($comments)){
                        $comment = $comments->getComment();
                    }else $comment = '';
                } else $comment = '';

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
    public function numberNotificationsAction()
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
    public function notificationsAction()
    {
        $em = $this->container->get('doctrine')->getManager();
        $query = $em->createQuery("SELECT p FROM TrackersBundle:Notifications p WHERE  p.userId = :user_id AND p.isRead = 0   ORDER BY p.created DESC ")
            ->setParameter('user_id', $this->getUser()->getId());

        $template = $this->render('TrackersBundle:Dashboard:ajaxnotifications.html.twig', array ('notifications' => $query->getResult(), 'total' => count($query->getResult())));
        return new Response($template->getContent());
        die();
    }

    /**
     * @Route("/notifications-view-all", name="_notificationsView_All")
     * @Template("TrackersBundle:Dashboard:notificationsviewall.html.twig")
     */
    public function notificationsViewAllAction()
    {
        $em = $this->container->get('doctrine')->getManager();
        $query = $em->createQuery("SELECT p FROM TrackersBundle:Notifications p WHERE  p.userId = :user_id AND p.isRead = 0   ORDER BY p.created DESC ")
            ->setParameter('user_id', $this->getUser()->getId());
        return array();

    }
    /**
     * @Route("/ajax_message", name="_ajax_message")
     */
    public function ajaxMessageAction()
    {
        $requestData = $this->getRequest()->request;
        $page = $requestData->get('page');
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Notifications');

        $limit = 50;
        $offset = $page*$limit;

        $total = (int)( count($repository->findBy(array ('userId' => $this->getUser()->getId()), array('created' => 'DESC'))) / $limit);
        $count = count($repository->findBy(array ('userId' => $this->getUser()->getId()), array('created' => 'DESC')));
        if ($count > $limit &&  $count  % $limit != 0){
            $total = $total + 1;
        }

        $pagination = new Pagination();
        $paginations = $pagination->render($page, $total, 'loadMessage');
        $notifications = $repository->findBy(array ('userId' => $this->getUser()->getId()), array('created' => 'DESC', 'isRead' => 'ASC'), $limit, $offset);

        $template = $this->render('TrackersBundle:Dashboard:ajax_notifications_page.html.twig', array ('notifications' => $notifications, 'paginations' => $paginations));
        return new Response($template->getContent());
        die();
    }

    /**
     * @Route("/recentAvatar", name="_recentAvatar")
     */
    public function recentAvatarAction()
    {
        $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');

        $users = $repository_user->findBy(array ('user_id' => $this->getUser()->getId()));
       if (!empty($users) && $users[0]->getAvatar() != '' ){
           if (file_exists($this->get('kernel')->getRootDir() . '/../web'.$users[0]->getAvatar()) ){
               $is_avatar = true;
           } else $is_avatar = false;
       } else $is_avatar = false;

        return $this->render(
            'TrackersBundle:Dashboard:recentAvatar.html.twig',
            array('avatar' => $users[0]->getAvatar() , 'is_avatar' => $is_avatar)
        );
    }

    /**
     * @Route("/recentCountIssue/{id}", name="_recentCountIssue")
     */
    public function recentCountIssueAction($id)
    {
        $repository_Projects = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
        $project = $repository_Projects->find($id);
        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
        $number_open = count($repository->findBy(array ('projectId' => $id, 'status' => 'OPEN')));
        $number_close = count($repository->findBy(array ('projectId' => $id, 'status' => 'CLOSED')));

        return $this->render(
            'TrackersBundle:Dashboard:recentCountIssue.html.twig',
            array('project' => $project, 'number_open' => $number_open , 'number_close' => $number_close , 'project_id' => $id )
        );
    }



}
