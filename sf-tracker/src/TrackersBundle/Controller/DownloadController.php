<?php

namespace TrackersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DownloadController extends Controller
{
    /**
     * Serve a file by forcing the download
     *
     * @Route("/download/{id}", name="download_file", requirements={"filename": ".+"})
     */
    public function downloadFileAction($id)
    {
        /**
         * $basePath can be either exposed (typically inside web/)
         * or "internal"
         */

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Projects_issues_attachments');
        $files = $repository->find($id);

        if (empty($files)){
            throw $this->createNotFoundException();
        }
        $filename = $files->getFileurl();
        $basePath = $this->get('kernel')->getRootDir() . '/../web/upload';

        $filePath = $basePath.'/'.$filename;
        $name_array = explode('/', $filename);
        $filename = $name_array[sizeof($name_array) - 1];

        // check if file exists
        $fs = new FileSystem();
        if (!$fs->exists($filePath)) {
            throw $this->createNotFoundException();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filePath));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    /**
     * Serve a file by forcing the download
     *
     * @Route("/download-task/{id}", name="download_file_task", requirements={"filename": ".+"})
     */
    public function downloadFileTaskAction($id)
    {
        /**
         * $basePath can be either exposed (typically inside web/)
         * or "internal"
         */

        $repository = $this->getDoctrine()->getRepository('TrackersBundle:Project_Task_Attachments');
        $files = $repository->find($id);

        if (empty($files)){
            throw $this->createNotFoundException();
        }
        $filename = $files->getFileurl();
        $basePath = $this->get('kernel')->getRootDir() . '/../web/upload';

        $filePath = $basePath.'/'.$filename;
        $name_array = explode('/', $filename);
        $filename = $name_array[sizeof($name_array) - 1];

        // check if file exists
        $fs = new FileSystem();
        if (!$fs->exists($filePath)) {
            throw $this->createNotFoundException();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filePath));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
