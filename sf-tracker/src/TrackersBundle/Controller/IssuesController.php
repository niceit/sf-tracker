<?php

    namespace TrackersBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
    use Symfony\Component\Config\Definition\Exception\Exception;
    use Symfony\Component\Validator\Constraints\DateTime;
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
    use TrackersBundle\Entity\Notifications;
    use TrackersBundle\Entity\Project_Closed_Issues;

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

            $template = $this->render('TrackersBundle:Issues:ajax_activity.html.twig', array ('activitys' => $arr, 'paginations' => $paginations , 'count' => $count));

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
            $issues = $repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN'), array ('created' => 'DESC'), $limit, $offset);
            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            $arr = array ();

            $repository_pro = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
            $project = $repository_pro->find($project_id);
            if( $project->getOwnerId() == $this->getUser()->getId() )
                $is_close = true;
            else
                $is_close = false;


            $now = date('Y-m-d');
            $endtime = '';
            $tomorrow =  '';
            foreach ($issues as $issue) {
                $user = $repository_user->findBy(array ('user_id' => $issue->getCreatedBy()));

                $endtime = $issue->getEndTime()->format('Y-m-d');
                $tomorrow = date('Y-m-d',strtotime('+1 day'));
                $arr[] = array (
                    'id' => $issue->getId(),
                    'title' => $issue->getTitle(),
                    'created' => $issue->getCreated(),
                    'CreatedBy' => $issue->getCreatedBy(),
                    'EndTime' => $issue->getEndTime(),
                    'unis_endtime' => strtotime($endtime),
                    'users' => $user
                );
            }
            $template = $this->render('TrackersBundle:Issues:ajax_open.html.twig', array ('issues' => $arr, 'now' => strtotime($now) ,'tomorrow' => strtotime($tomorrow), 'paginations' => $paginations, 'project_id' => $project_id,'is_close' => $is_close, 'user_id' => $this->getUser()->getId()));

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
            $issues = $repository->findBy(array ('projectId' => $project_id, 'status' => 'CLOSED'), array ('created' => 'DESC'), $limit, $offset);
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

            // get uer_id project assign
            $em = $this->container->get('doctrine')->getManager();
            $query = $em->createQuery("SELECT p FROM TrackersBundle:Project_issues p , TrackersBundle:Project_issue_assignments pm  WHERE  p.projectId =:projectId AND  p.id = pm.issueId AND pm.userId =:userId   ORDER BY p.created DESC ")
                ->setParameter('projectId', $project_id)
                ->setParameter('userId', $this->getUser()->getId());
            $project_issues = $query->getResult();

            $limit = $this->container->getParameter('limit_open_issues');
            $offset = $page * $limit;



            $total = (int)(count($project_issues) / $limit);
            $count = count($project_issues) / $limit;
            if($count > $limit &&  $count  % $limit != 0){
                $total = $total + 1;
            }

            $pagination = new Pagination();
            $paginations = $pagination->render($page, $total, 'load_assigned');

            $query = $em->createQuery("SELECT p FROM TrackersBundle:Project_issues p , TrackersBundle:Project_issue_assignments pm  WHERE p.projectId =:projectId AND p.id = pm.issueId AND pm.userId =:userId   ORDER BY p.created DESC ")
                ->setMaxResults($limit)
                ->setFirstResult($offset)
                ->setParameter('projectId', $project_id)
                ->setParameter('userId', $this->getUser()->getId());
            $issues = $query->getResult();

          

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

                $start_date = $requestData->get('start_date');
                $start_time = $requestData->get('start_time');
                $end_date = $requestData->get('end_date');
                $end_time = $requestData->get('end_time');


                $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
                $issue = $repository->find($issueId);
                $issue->setStatus('CLOSED');
                $em = $this->getDoctrine()->getManager();
                $em->persist($issue);
                $em->flush();

                $Closed_Issues = new Project_Closed_Issues();

                // date start time
                $arr_start_date = explode('-',$start_date);
                $arr_start_time = explode(':',$start_time);

                $date_start = new \DateTime();
                $date_start->setDate($arr_start_date[0],$arr_start_date[1],$arr_start_date[2]);
                if($arr_start_time)
                $date_start->setTime($arr_start_time[0],$arr_start_time[1],$arr_start_time[2]);

                // date end time
                $arr_end_date = explode('-',$end_date);
                $arr_end_time = explode(':',$end_time);

                $date_end = new \DateTime();
                $date_end->setDate($arr_end_date[0],$arr_end_date[1],$arr_end_date[2]);
                if($arr_end_time)
                $date_end->setTime($arr_end_time[0],$arr_end_time[1],$arr_end_time[2]);

                $Closed_Issues->setIssueId($issueId);
                $Closed_Issues->setUserId($this->getUser()->getId());
                $Closed_Issues->setStartDate($date_start);
                $Closed_Issues->setEndDate($date_end);
                $Closed_Issues->setCreated(new \DateTime("now"));
                $em->persist($Closed_Issues);
                $em->flush();


                // add activity close
                $users_activity = new Users_activity();
                $users_activity->setUserId($this->getUser()->getId());
                $users_activity->setParentId($project_id);
                $users_activity->setItemId($issue->getId());
                $users_activity->setTypeId(3);
                $users_activity->setCreatedAt(new \DateTime("now"));
                $em->persist($users_activity);
                $em->flush();


                // add activity completed
                $users_activity_6 = new Users_activity();
                $users_activity_6->setUserId($this->getUser()->getId());
                $users_activity_6->setParentId($project_id);
                $users_activity_6->setItemId($issue->getId());
                $users_activity_6->setTypeId(6);
                $users_activity_6->setCreatedAt(new \DateTime("now"));
                $em->persist($users_activity_6);
                $em->flush();


                $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');
                $project = $repository->find($project_id);
                if(!empty($project)){
                    // Get user detail
                    $repository = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
                    $users = $repository->findBy(array('user_id' => $this->getUser()));
                    $user = $users[0];

                    $repository_attachments = $this->getDoctrine()->getRepository('TrackersBundle:Project_issue_assignments');
                    $attachments = $repository_attachments->findBy(array ('issueId' => $issue->getId()));
                    // notifiaction user attachment

                    $text = $user->getFirstname().' '.$user->getLastname()." closed issue ".$project->getName();

                    // send user create project----
                    if($this->getUser()->getId() != $project->getOwnerId()){
                        $notifications = new Notifications();
                        $notifications->setUserId($project->getOwnerId());
                        $notifications->setIssueId($issue->getId());
                        $notifications->setProjectId($project_id);
                        $notifications->setCreated(new \DateTime("now"));
                        $notifications->setIsRead(false);
                        $notifications->setText($text);
                        $em->persist($notifications);
                        $em->flush();
                    }
                    // send user do issues----
                    if(!empty($attachments)){
                        foreach($attachments as $attachment){
                            if($this->getUser()->getId() != $attachment->getUserId()){
                                $notifications = new Notifications();
                                $notifications->setUserId($attachment->getUserId());
                                $notifications->setIssueId($issue->getId());
                                $notifications->setProjectId($project_id);
                                $notifications->setCreated(new \DateTime("now"));
                                $notifications->setIsRead(false);
                                $notifications->setText($text);
                                $em->persist($notifications);
                                $em->flush();
                            }else{
                                $attach = $repository_attachments->find($attachment->getId());
                                $attach->setStatus('CLOSED');
                                $em->persist($attach);
                                $em->flush();
                            }

                        }
                    }

                }

            }
            echo 1;
            die();
        }
        /**
         * @Route("/ajaxopenissues", name="_ajaxopenissues")
         */
        public function ajaxopenissuesAction () {
            if ($this->getRequest()->getMethod() == 'POST') {
                $requestData = $this->getRequest()->request;
                $issueId = $requestData->get('issueId');
                $project_id = $requestData->get('project_id');

                $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
                $issue = $repository->find($issueId);
                $issue->setStatus('OPEN');
                $em = $this->getDoctrine()->getManager();
                $em->persist($issue);
                $em->flush();

                $users_activity = new Users_activity();
                $users_activity->setUserId($this->getUser()->getId());
                $users_activity->setParentId($project_id);
                $users_activity->setItemId($issue->getId());
                $users_activity->setTypeId(4);
                $users_activity->setCreatedAt(new \DateTime("now"));
                $em->persist($users_activity);
                $em->flush();

                $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects');

                $project = $repository->find($project_id);



                if(!empty($project)){
                    // Get user detail
                    $repository = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
                    $users = $repository->findBy(array('user_id' => $this->getUser()));
                    $user = $users[0];

                    $repository_attachments = $this->getDoctrine()->getRepository('TrackersBundle:Project_issue_assignments');
                    $attachments = $repository_attachments->findBy(array ('issueId' => $issue->getId()));
                    // notifiaction user attachment
                    $text = $user->getFirstname().' '.$user->getLastname()."  Reopened issue ".$project->getName();

                    // send user create project----
                    if($this->getUser()->getId() != $project->getOwnerId()){
                        $notifications = new Notifications();
                        $notifications->setUserId($project->getOwnerId());
                        $notifications->setIssueId($issue->getId());
                        $notifications->setProjectId($project_id);
                        $notifications->setCreated(new \DateTime("now"));
                        $notifications->setIsRead(false);
                        $notifications->setText($text);
                        $em->persist($notifications);
                        $em->flush();
                    }
                    // send user do issues----
                    if(!empty($attachments)){
                        foreach($attachments as $attachment){
                            if($this->getUser()->getId() != $attachment->getUserId()){
                                $notifications = new Notifications();
                                $notifications->setUserId($attachment->getUserId());
                                $notifications->setIssueId($issue->getId());
                                $notifications->setProjectId($project_id);
                                $notifications->setCreated(new \DateTime("now"));
                                $notifications->setIsRead(false);
                                $notifications->setText($text);
                                $em->persist($notifications);
                                $em->flush();
                            }else{
                                $attach = $repository_attachments->find($attachment->getId());
                                $attach->setStatus('CLOSED');
                                $em->persist($attach);
                                $em->flush();
                            }

                        }
                    }

                }

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



            // users create priect
            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            $users = $repository_user->findBy(array ('user_id' => $issue->getCreatedBy()));
            $user = $repository_user->find($users[0]->getId());

            // is avatar
            if($user->getAvatar()!='' && !empty($user)){
                if(file_exists($this->get('kernel')->getRootDir() . '/../web'.$user->getAvatar()) ) {
                    $is_avatar = true;
                }else $is_avatar = false;
            }else $is_avatar = false;


            $repository_file = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
            $attachments = $repository_file->findBy(array ('issueId' => $id));

            $repository_assignment = $this->getDoctrine()->getRepository('TrackersBundle:Project_issue_assignments');
            $assignments = $repository_assignment->findBy(array ('issueId' => $id));

            $is_ssues = false;
            if(!empty($assignments)){
                foreach($assignments as $assign){
                    if($this->getUser()->getId() == $assign->getUserId() ){
                        $is_ssues = true;
                        break;
                    }

                }
            }

            if($this->getUser()->getId() == $issue->getCreatedBy() )
                $is_ssues = true;


            $repository_notifications = $this->getDoctrine()->getRepository('TrackersBundle:Notifications');
            $notification = $repository_notifications->findBy(array('userId' => $this->getUser()->getId(), 'issueId' => $id, 'projectId' => $project_id ));
            if(!empty($notification)){

                foreach($notification as $notific ){
                    $notifications = $notific; //$repository_notifications->find($notification[0]->getId());
                    $notifications->setModified(new \DateTime("now"));
                    $notifications->setIsRead(true);

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($notifications);
                    $em->flush();
                }

            }

            $repository_Closed_Issues = $this->getDoctrine()->getRepository('TrackersBundle:Project_Closed_Issues');
            $Closed_Issues = $repository_Closed_Issues->findBy(array('userId' => $this->getUser()->getId(), 'issueId' => $id ));
           $array_date = array();

            $repository_user = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
            foreach($Closed_Issues as $closed){
               $users = $repository_user->findBy(array ('user_id' => $issue->getCreatedBy()));

                $date1 = $closed->getStartDate();
                $date2 = $closed->getEndDate();
                $diff = $date2->diff($date1);
                if($diff->format('%i') > 0 )
                    $h = $diff->format('%d')*24 + $diff->format('%h')." hours ".$diff->format('%i')." minute";
                else
                    $h = $diff->format('%d')*24 + $diff->format('%h')." hours ";
                $array_date[] = array(
                    'full_name' => $users[0]->getFirstname()." ".$users[0]->getLastname(),
                    'start_date' => $closed->getStartDate(),
                    'end_date' => $closed->getEndDate(),
                    'total_time' => $h

                );
            }


            return array ('issue' => $issue , 'is_avatar' => $is_avatar , 'project' => $project, 'user' => $user, 'attachments' => $attachments, 'is_ssues' => $is_ssues , 'completes' => $array_date );

        }

        /**
         * @Route("/issue/add/{id}", name="_addIssues")
         * @Template("TrackersBundle:Issues:add.html.twig")
         */
        public function addAction ($id) {
            $issue = new Project_issues();

            $times = new \DateTime();
            $date = $times->format('Y-m-d');
            $time = $times->format('h:i:s');



            if ($this->getRequest()->getMethod() == 'POST') {
                $requestData = $this->getRequest()->request;
                $attachments = $requestData->get('attachments');
                $form = $requestData->get('form');
                $date = $requestData->get('date');
                $time = '0:0:0';//$requestData->get('time');

                $arr_date = explode('-',$date);
                $arr_time = explode(':',$time);

                $date_time = new \DateTime("now");
                $date_time->setDate($arr_date[0],$arr_date[1],$arr_date[2]);
                $date_time->setTime($arr_time[0],$arr_time[1],$arr_time[2]);

                $issue->setTitle($form['title']);
                $issue->setDescription($form['description']);
                $issue->setAssignedTo(0);
                $issue->setStatus($form['status']);
                $issue->setEndTime($date_time);
                $issue->setCreated(new \DateTime("now"));
                $issue->setModified(new \DateTime("now"));
                $issue->setProjectId($id);
                $issue->setCreatedBy($this->getUser()->getId());
                $em = $this->getDoctrine()->getManager();
                $em->persist($issue);
                $em->flush();



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



                if(!empty($form['assigned_to'])) {
                    foreach ($form['assigned_to'] as $assigned) {
                        $project_issue_assignments = new Project_issue_assignments();
                        $project_issue_assignments->setUserId($assigned);
                        $project_issue_assignments->setIssueId($issue->getId());
                        $em->persist($project_issue_assignments);
                        $em->flush();
                        $repository = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
                        $users = $repository->findBy(array ('user_id' => $this->getUser()->getId()));
                        $user = $users[0];
                        $notifications = new Notifications();
                        $notifications->setUserId($assigned);
                        $notifications->setIssueId($issue->getId());
                        $notifications->setProjectId($id);
                        $notifications->setCreated(new \DateTime("now"));
                        $notifications->setIsRead(false);
                        $notifications->setText('You have assign issue with title ' . $form['title'] . ' from ' . $user->getFirstname() . ' ' . $user->getLastname());
                        $em->persist($notifications);
                        $em->flush();

                    }
                }
                $this->get('session')->getFlashBag()->add('notice', 'Issue is successfully added!');

                return $this->redirectToRoute('_addIssues', array ('id' => $id));
            }
            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("SELECT n.firstname , n.lastname , n.user_id  FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND u.projectId = :project_id")->setParameter('project_id', $id);
            $entities = $query->getResult();

            $arr = array ();
            foreach ($entities as $post) {
                $arr[$post['user_id']] = $post['firstname'] . " " . $post['lastname'];
            }
            $form = $this->createFormBuilder($issue)
                ->add('title', 'text', array ('label' => 'Tile', 'required' => true))
               // ->add('endTime', 'text', array ('label' => 'End time', 'required' => true,'attr' => array('class' =>'timepicker')))
                ->add('description', 'textarea', array ('required' => false, 'label' => 'Description', 'attr' => array ('class' => 'editor', 'id' => 'editor')))
                ->add('assigned_to', 'choice', array ('choices' => $arr,'multiple' => true, 'required' => false, 'label' => 'Assigned To', 'attr'=> array('multiple' => 'multiple', 'class' => 'chzn-select' ), 'empty_data' => null))
                ->add('status', 'choice', array ('choices' => array ('OPEN' => 'OPEN', 'HOLD' => 'HOLD', 'INPROGRESS' => 'INPROGRESS', 'CLOSED' => 'CLOSED', 'FINISHED' => 'FINISHED'), // 'preferred_choices' => array('OPEN'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                       'label'   => 'Status'))->getForm();


            return array ('form' => $form->createView(), 'project_id' => $id, 'date' => $date, 'time' => $time);
        }

        /**
         * @Route("/issue/edit/{id}/{project_id}", name="_editIssues")
         * @Template("TrackersBundle:Issues:edit.html.twig")
         */
        public function editAction ($id, $project_id) {


            $issue = new Project_issues();
            $repositorys = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            if ($this->getRequest()->getMethod() == 'POST') {
                $requestData = $this->getRequest()->request;
                $form = $requestData->get('form');
                $assignedTo= $requestData->get('assigned_to');


                $date = $requestData->get('date');
                $time = $time = '0:0:0';// $requestData->get('time');

                $arr_date = explode('-',$date);
                $arr_time = explode(':',$time);

                $date_time = new \DateTime("now");
                $date_time->setDate($arr_date[0],$arr_date[1],$arr_date[2]);
                $date_time->setTime($arr_time[0],$arr_time[1],$arr_time[2]);

                $issue = $repositorys->find($id);
                $issue->setTitle($form['title']);
                $issue->setDescription($form['description']);
                $issue->setEndTime($date_time);
                $issue->setStatus($form['status']);
                $issue->setModified(new \DateTime("now"));
                $issue->setProjectId($project_id);
                $issue->setCreatedBy($this->getUser()->getId());
                $em = $this->getDoctrine()->getManager();
                $em->persist($issue);
                $em->flush();

                $repository_attachments = $this->getDoctrine()->getRepository('TrackersBundle:Project_issue_assignments');
                $attachments = $repository_attachments->findBy(array ('issueId' => $issue->getId()));
                // delete user attachments
                if(!empty($attachments)){
                    foreach($attachments as $attachment){
                        $repository_attachments = $this->getDoctrine()->getRepository('TrackersBundle:Project_issue_assignments');
                        $em = $this->getDoctrine()->getManager();
                        $user_attachments = $repository_attachments->find($attachment->getId());
                        $em->remove($user_attachments);
                        $em->flush();
                    }
                }

                $users_activity = new Users_activity();
                $users_activity->setUserId($this->getUser()->getId());
                $users_activity->setParentId($project_id);
                $users_activity->setItemId($issue->getId());
                $users_activity->setTypeId(5);
                $users_activity->setCreatedAt(new \DateTime("now"));
                $em->persist($users_activity);
                $em->flush();

                if(!empty($assignedTo)){
                    foreach($assignedTo as $assigned){

                        $project_issue_assignments = new Project_issue_assignments();
                        $project_issue_assignments->setUserId($assigned);
                        $project_issue_assignments->setIssueId($issue->getId());
                        $em->persist($project_issue_assignments);
                        $em->flush();

                        $repository = $this->getDoctrine()->getRepository('TrackersBundle:UserDetail');
                        $users = $repository->findBy(array('user_id' => $this->getUser()->getId()));
                        $user = $users[0];
                        $notifications = new Notifications();
                        $notifications->setUserId($assigned);
                        $notifications->setIssueId($issue->getId());
                        $notifications->setProjectId($project_id);
                        $notifications->setCreated(new \DateTime("now"));
                        $notifications->setIsRead(false);
                        $notifications->setText('You have reassign issue with title '.$form['title'].' from '.$user->getFirstname().' '.$user->getLastname());
                        $em->persist($notifications);
                        $em->flush();
                    }
                }
                $this->get('session')->getFlashBag()->add('notice', 'Issue is successfully edited!');
            }



            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery("SELECT n.firstname , n.lastname ,n.user_id FROM TrackersBundle:UserDetail n, TrackersBundle:User_projects u WHERE  u.userId = n.user_id AND u.projectId = :project_id")->setParameter('project_id', $project_id);
            $entities = $query->getResult();
            $arr = array ();

            $issues_id = $repositorys->find($id);


            // ---- load array mutil select
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issue_assignments');
            $assignments = $repository->findBy(array('issueId' =>$id));


            foreach ($entities as $post) {
                $is_select = false;
                foreach ($assignments as $assign) {
                    if($post['user_id'] == $assign->getUserId()){
                        $is_select = true;
                        break;
                    }

                }
                $arr[] = array(
                    'id' => $post['user_id'],
                    'fullName' =>$post['firstname'] . " " . $post['lastname'],
                    'selected' => $is_select
                );

            }


            $assignedToOld = $issues_id->getassignedTo();
            // set form
            $issue->setTitle($issues_id->getTitle());
            $issue->setDescription($issues_id->getDescription());
            $issue->setStatus($issues_id->getStatus());
            $form = $this->createFormBuilder($issue)
                ->add('title', 'text', array ('label' => 'Tile', 'required' => true))
                ->add('description', 'textarea', array ('required' => false, 'label' => 'Description', 'attr' => array ('class' => 'editor', 'id' => 'editor')))
                //->add('assigned_to', 'choice', array ('choices' => $arr, 'multiple' => true, 'required' => false, 'label' => 'Assigned To', 'preferred_choices' => array (), 'empty_data' => null))
                ->add('status', 'choice', array ('choices' => array ('OPEN' => 'OPEN', 'HOLD' => 'HOLD', 'INPROGRESS' => 'INPROGRESS', 'CLOSED' => 'CLOSED', 'FINISHED' => 'FINISHED'), 'preferred_choices' => array ($issues_id->getStatus()), 'label' => 'Status'))
                ->getForm();
            return array ('form' => $form->createView(),'project_id' => $project_id,'assignedTo' => $assignedToOld, 'assignedtos' => $arr,'endtime'=>$issues_id->getEndTime(), 'date' => $issues_id->getEndTime()->format('Y-m-d'), 'time' => $issues_id->getEndTime()->format('H:i:s') );
        }

        /**
         * @Route("/ajaxUpdateStatusIssue/{project_id}", name="_ajaxUpdateStatusIssue")
         */
        public function ajaxUpdateStatusIssueAction ($project_id) {
            if ($this->getRequest()->getMethod() == 'POST') {
                $requestData = $this->getRequest()->request;
                $id = $requestData->get('id');
                $status = $requestData->get('status');
                $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
                $issue = $repository->find($id);
                $issue->setStatus($status);

                $em = $this->getDoctrine()->getManager();
                $em->persist($issue);
                $em->flush();
            }
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            $number_open = count($repository->findBy(array ('projectId' => $project_id, 'status' => 'OPEN')));
            $number_close = count($repository->findBy(array ('projectId' => $project_id, 'status' => 'CLOSED')));
            $data['number_open'] = $number_open;
            $data['number_close'] = $number_close;
            echo json_encode($data);
            die();
        }

        /**
         * @Route("/removeIssue", name="_removeIssue")
         */
        public function removeUerProjectAction()
        {
            if ($this->getRequest()->getMethod() == 'POST') {
                $requestData = $this->getRequest()->request;
                $id = $requestData->get('id');


                $repository_pro = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');

                $em = $this->getDoctrine()->getManager();
                $user_projects = $repository_pro->find($id);
                $em->remove($user_projects);
                $em->flush();
                echo $id;
                exit;
            }
            echo 0;
            exit;
        }


        /**
         * @Route("/tasks", name="_listtasks")
         * @Template("TrackersBundle:Issues:listtasks.html.twig")
         */
        public function _listtasksAction () {

            $em = $this->container->get('doctrine')->getManager();

            $query = $em->createQuery("SELECT p FROM TrackersBundle:Projects p WHERE  p.owner_id = :user_id OR p.id IN ( SELECT up.projectId FROM TrackersBundle:User_projects up WHERE up.userId = :assigned_to)  ORDER BY p.created DESC ")
                ->setParameter('user_id', $this->getUser()->getId())
                ->setParameter('assigned_to', $this->getUser()->getId());

            $projects = $query->getResult();
            $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_issues');
            $array = array();

            foreach($projects as $project){
                $issues = $repository->findBy(array('projectId' => $project->getId()));
                $array[] = array(
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'issues' => $issues
                );

            }
            return array( 'project_issues' => $array );
        }


    }
