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
    use TrackersBundle\Entity\Users_activity;
    use TrackersBundle\Entity\Projects_issues_attachments;
    use Symfony\Component\HttpFoundation\Response;
    use TrackersBundle\Entity\Project_activity;
    use TrackersBundle\Entity\Projects_issues_comments;

    class IssuesController extends Controller {
        /**
         * @Route("/issue", name="_listIssue")
         * @Template("TrackersBundle:Issues:index.html.twig")
         */
        public function indexAction () {
            return array ();
        }

        /**
         * @Route("/ajax_list_activity_issues", name="_ajaxListActivityIssues")
         */
        public function ajax_activity_issuesAction () {
            $requestData = $this->getRequest()->request;
            $page = $requestData->get('page');
            $project_id = $requestData->get('project_id');
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Users_activity');


            $limit = $this->container->getParameter('limit_open_issues');
            $offset = $page*$limit;

            $total = (int)( count($repository->findBy(array ('parentId' => $project_id))) / $limit);
            $count = count(($repository->findBy(array ('parentId' => $project_id))));
            if($count > $limit &&  $count  % $limit != 0){
                $total = $total + 1;
            }


            $pagination = new Pagination();
            $paginations = $pagination->render($page, $total, 'load_activity');
            $activitys = $repository->findBy(array ('parentId' => $project_id), array ('createdAt' => 'DESC'), $limit, $offset);
            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            $repository_activity = $this->getDoctrine()->getRepository('TrackersBundle:Project_activity');
            $repository_issues = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            $repository_issues_comments = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_comments');
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
               if($activity->getActionId() != ''){
                   $comment = $repository_issues_comments->find($activity->getActionId())->getComment();
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

            $template = $this->render('TrackersBundle:Issues:ajax_activity.html.twig', array ('activitys' => $arr, 'paginations' => $paginations));

            return new Response($template->getContent());
            die();
        }

        /**
         * @Route("/ajax_list_open_issues", name="_ajaxListOpenIssues")
         */
        public function ajax_open_issuesAction () {
            $requestData = $this->getRequest()->request;
            $page = $requestData->get('page');
            $project_id = $requestData->get('project_id');
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            $limit = $this->container->getParameter('limit_open_issues');
            $offset = $page*$limit;

            $total = (int)( count($repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN'))) / $limit);
            $count = count($repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN')));
            if($count > $limit &&  $count  % $limit != 0){
                $total = $total + 1;
            }

            $pagination = new Pagination();
            $paginations = $pagination->render($page, $total, 'load_open');
            $issues = $repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN'), array ('created' => 'ASC'), $limit, $offset);
            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            $arr = array ();
            foreach ($issues as $issue) {
                $user = $repository_user->findBy(array ('user_id' => $issue->getCreatedBy()));
                $arr[] = array ('id' => $issue->getId(), 'title' => $issue->getTitle(), 'created' => $issue->getCreated(), 'users' => $user);
            }
            $template = $this->render('TrackersBundle:Issues:ajax_open.html.twig', array ('issues' => $arr, 'paginations' => $paginations, 'project_id' => $project_id));

            return new Response($template->getContent());
            die();
        }

        /**
         * @Route("/ajax_list_close_issues", name="_ajaxListCloseIssues")
         */
        public function ajax_close_issuesAction () {
            $requestData = $this->getRequest()->request;
            $page = $requestData->get('page');
            $project_id = $requestData->get('project_id');
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            $limit = $this->container->getParameter('limit_open_issues');
            $offset = $page * $limit;

            $total = (int)(count($repository->findBy(array ('projectId' => $project_id, 'status' => 'CLOSED'))) / $limit);
            $count = count($repository->findBy(array ('projectId' => $project_id, 'status' => 'CLOSED'))) / $limit;
            if($count > $limit &&  $count  % $limit != 0){
                $total = $total + 1;
            }


            $pagination = new Pagination();
            $paginations = $pagination->render($page, $total, 'load_close');
            $issues = $repository->findBy(array ('projectId' => $project_id, 'status' => 'CLOSED'), array ('created' => 'ASC'), $limit, $offset);
            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            $arr = array ();
            foreach ($issues as $issue) {
                $user = $repository_user->findBy(array ('user_id' => $issue->getCreatedBy()));
                $arr[] = array ('id' => $issue->getId(), 'title' => $issue->getTitle(), 'created' => $issue->getCreated(), 'users' => $user);
            }
            $template = $this->render('TrackersBundle:Issues:ajax_close.html.twig', array ('issues' => $arr, 'paginations' => $paginations, 'project_id' => $project_id));

            return new Response($template->getContent());
            die();
        }

        /**
         * @Route("/ajax_list_assigned_issues", name="_ajaxListAssignedIssues")
         */
        public function ajax_assigned_issuesAction () {
            $requestData = $this->getRequest()->request;
            $page = $requestData->get('page');
            $project_id = $requestData->get('project_id');
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            $limit = $this->container->getParameter('limit_open_issues');
            $offset = $page * $limit;

            $total = (int)(count($repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN', 'assignedTo' => $this->getUser()->getId()))) / $limit);
            $count = count($repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN', 'assignedTo' => $this->getUser()->getId()))) / $limit;
            if($count > $limit &&  $count  % $limit != 0){
                $total = $total + 1;
            }

            $pagination = new Pagination();
            $paginations = $pagination->render($page, $total, 'load_assigned');
            $issues = $repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN','assignedTo' => $this->getUser()->getId() ), array ('created' => 'ASC'), $limit, $offset);
            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            $arr = array ();
            foreach ($issues as $issue) {
                $user = $repository_user->findBy(array ('user_id' => $issue->getCreatedBy()));
                $arr[] = array ('id' => $issue->getId(), 'title' => $issue->getTitle(), 'created' => $issue->getCreated(), 'users' => $user);
            }
            $template = $this->render('TrackersBundle:Issues:ajax_assigned.html.twig', array ('issues' => $arr, 'paginations' => $paginations, 'project_id' => $project_id));

            return new Response($template->getContent());
            die();
        }


        /**
         * @Route("/ajaxcloseissues", name="_ajaxcloseissues")
         */
        public function ajaxcloseissuesAction () {
            if ($this->getRequest()->getMethod() == 'POST') {
                $requestData = $this->getRequest()->request;
                $issueId = $requestData->get('issueId');
                $project_id = $requestData->get('project_id');
                $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
                $issue = $repository->find($issueId);
                $issue->setStatus('CLOSED');
                $em = $this->getDoctrine()->getManager();
                $em->persist($issue);
                $em->flush();

                $users_activity = new Users_activity();
                $users_activity->setUserId($this->getUser()->getId());
                $users_activity->setParentId($project_id);
                $users_activity->setItemId($issue->getId());
                $users_activity->setTypeId(3);
                $users_activity->setCreatedAt(new \DateTime("now"));
                $em->persist($users_activity);
                $em->flush();

            }
            echo 1;
            die();
        }
        /**
         * @Route("/issues/{id}/{project_id}", name="_project_issues")
         * @Template("TrackersBundle:Issues:project_issue.html.twig")
         */
        public function project_issuesAction ($id, $project_id) {
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            $issue = $repository->find($id);
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
            $project = $repository->find($project_id);
            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            $users = $repository_user->findBy(array ('user_id' => $issue->getCreatedBy()));
            $user = $repository_user->find($users[0]->getId());
            $repository_file = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
            $attachments = $repository_file->findBy(array ('issueId' => $id));


            return array ('issue' => $issue, 'project' => $project, 'user' => $user, 'attachments' => $attachments);

        }

        /**
         * @Route("/issue/add/{id}", name="_addIssues")
         * @Template("TrackersBundle:Issues:add.html.twig")
         */
        public function addAction ($id) {
            $issue = new Project_issues();
            if ($this->getRequest()->getMethod() == 'POST') {
                $requestData = $this->getRequest()->request;
                $attachments = $requestData->get('attachments');
                $form = $requestData->get('form');
                $issue->setTitle($form['title']);
                $issue->setDescription($form['description']);
                $issue->setAssignedTo($form['assigned_to']);
                $issue->setStatus($form['status']);
                $issue->setCreated(new \DateTime("now"));
                $issue->setModified(new \DateTime("now"));
                $issue->setProjectId($id);
                $issue->setCreatedBy($this->getUser()->getId());
                $em = $this->getDoctrine()->getManager();
                $em->persist($issue);
                $em->flush();
                /*
                $project_issue_assignments = new Project_issue_assignments();
                $project_issue_assignments->setUserId($this->getUser()->getId());
                $project_issue_assignments->setIssueId($issue->getId());
                $em->persist($project_issue_assignments);
                $em->flush();
                */
                if (!empty($attachments)) {
                    foreach ($attachments as $file) {
                        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
                        $files = $repository->find($file);
                        $files->setIssueId($issue->getId());
                        $em->persist($files);
                        $em->flush();
                    }
                }
                $users_activity = new Users_activity();
                $users_activity->setUserId($this->getUser()->getId());
                $users_activity->setParentId($id);
                $users_activity->setItemId($issue->getId());
                $users_activity->setTypeId(1);
                $users_activity->setCreatedAt(new \DateTime("now"));
                $em->persist($users_activity);
                $em->flush();
                $this->get('session')->getFlashBag()->add('notice', 'More successful Issues!');

                return $this->redirectToRoute('_addIssues', array ('id' => $id));
            }
            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id  FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND u.projectId = :project_id")->setParameter('project_id', $id);
            $entities = $query->getResult();

            $arr = array ();
            foreach ($entities as $post) {
                $arr[$post['user_id']] = $post['firstname'] . " " . $post['lastname'];
            }
            $form = $this->createFormBuilder($issue)->add('title', 'text', array ('label' => 'Tile', 'required' => true))->add('description', 'textarea', array ('required' => false, 'label' => 'Description', 'attr' => array ('class' => 'editor', 'id' => 'editor')))->add('assigned_to', 'choice', array ('choices' => $arr, 'required' => false, 'empty_value' => '--select--', 'label' => 'Assigned To', 'empty_data' => null))->add('status', 'choice', array ('choices' => array ('OPEN' => 'OPEN', 'HOLD' => 'HOLD', 'INPROGRESS' => 'INPROGRESS', 'CLOSED' => 'CLOSED', 'FINISHED' => 'FINISHED'), // 'preferred_choices' => array('OPEN'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                       'label'   => 'Status'))->getForm();

            return array ('form' => $form->createView(), 'project_id' => $id);
        }

        /**
         * @Route("/issue/edit/{id}/{project_id}", name="_editIssues")
         * @Template("TrackersBundle:Issues:edit.html.twig")
         */
        public function editAction ($id, $project_id) {
            $issue = new Project_issues();
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            if ($this->getRequest()->getMethod() == 'POST') {
                $requestData = $this->getRequest()->request;
                $form = $requestData->get('form');
                $issue = $repository->find($id);
                $issue->setTitle($form['title']);
                $issue->setDescription($form['description']);
                $issue->setAssignedTo($form['assigned_to']);
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
            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("SELECT n.firstname , n.lastname ,n.user_id FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND u.projectId = :project_id")->setParameter('project_id', $project_id);
            $entities = $query->getResult();
            $arr = array ();
            foreach ($entities as $post) {
                $arr[$post['user_id']] = $post['firstname'] . " " . $post['lastname'];
            }
            $issues_id = $repository->find($id);
            $issue->setTitle($issues_id->getTitle());
            $issue->setDescription($issues_id->getDescription());
            $issue->setStatus($issues_id->getStatus());
            $issue->setAssignedTo($issues_id->getassignedTo());
            $form = $this->createFormBuilder($issue)->add('title', 'text', array ('label' => 'Tile', 'required' => true))->add('description', 'textarea', array ('required' => false, 'label' => 'Description', 'attr' => array ('class' => 'editor', 'id' => 'editor')))->add('assigned_to', 'choice', array ('choices' => $arr, 'required' => false, 'empty_value' => '--select--', 'label' => 'Assigned To', 'preferred_choices' => array ($issues_id->getassignedTo()), 'empty_data' => null))->add('status', 'choice', array ('choices' => array ('OPEN' => 'OPEN', 'HOLD' => 'HOLD', 'INPROGRESS' => 'INPROGRESS', 'CLOSED' => 'CLOSED', 'FINISHED' => 'FINISHED'), 'preferred_choices' => array ($issues_id->getStatus()), 'label' => 'Status'))->getForm();

            return array ('form' => $form->createView(), array ('project_id' => $id));
        }

    }
