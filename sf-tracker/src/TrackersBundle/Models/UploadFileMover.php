<?php

namespace TrackersBundle\Models;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of UploadFileMover
 *
 * @author Manoj
 */
class UploadFileMover {

    public function moveUploadedFile(UploadedFile $file, $uploadBasePath,$relativePath, $file_name) {
        $originalName = $file_name.".".$file->getClientOriginalExtension();
        $targetFileName = $relativePath . DIRECTORY_SEPARATOR . $originalName;
        $targetFilePath = $uploadBasePath . DIRECTORY_SEPARATOR . $targetFileName;



        $targetDir = $uploadBasePath . DIRECTORY_SEPARATOR . $relativePath;
        if (!is_dir($targetDir)) {
            $ret = mkdir($targetDir, umask(), true);
            if (!$ret) {
                throw new \RuntimeException("Could not create target directory to move temporary file into.");
            }
        }
        $file->move($targetDir, basename($targetFilePath));

        return str_replace($uploadBasePath . DIRECTORY_SEPARATOR, "", $targetFilePath);
    }

}

?>
