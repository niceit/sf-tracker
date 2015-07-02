<?php
namespace TrackersBundle\Models;

/**
 * Description of Document
 *
 * @author Manoj
 */
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class Document
{

    private $file;

    private $subDir;
    private $name_file;
    private $type_file;
    private $filePersistencePath;

    /** @var string */
    protected static $uploadDirectory = '%kernel.root_dir%/../upload';

    static public function setUploadDirectory($dir)
    {
        self::$uploadDirectory = $dir;
    }

    static public function getUploadDirectory()
    {
        if (self::$uploadDirectory === null) {
            throw new \RuntimeException("Trying to access upload directory for profile files");
        }
        return self::$uploadDirectory;
    }
    public function setSubDirectory($dir)
    {
        $this->subDir = $dir;
    }
    public function setNameFile($name_file)
    {
        $this->name_file = $name_file;
    }
    public function setTypeFile($type_file)
    {
        $this->type_file = $type_file;
    }
    public function getSubDirectory()
    {
        if ($this->subDir === null) {
            throw new \RuntimeException("Trying to access sub directory for profile files");
        }
        return $this->subDir;
    }


    public function setFile(File $file)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return new File(self::getUploadDirectory() . "/" . $this->filePersistencePath);
    }

    public function getOriginalFileName()
    {
        return $this->file->getClientOriginalName();
    }

    public function getFilePersistencePath()
    {
        return $this->filePersistencePath;
    }

    public function processFile()
    {
        if (! ($this->file instanceof UploadedFile) ) {
            return false;
        }
        $uploadFileMover = new UploadFileMover();
        $this->filePersistencePath = $uploadFileMover->moveUploadedFile($this->file, self::getUploadDirectory(),$this->subDir,$this->name_file, $this->type_file);
    }
}
?>
