<?php

namespace TrackersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Projects_issues_attachments
 *
 * @ORM\Table(name="projects_issues_attachments")
 * @ORM\Entity
 */
class Projects_issues_attachments
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="issue_id", type="integer")
     */
    private $issueId;

    /**
     * @var integer
     *
     * @ORM\Column(name="comment_id", type="integer")
     */
    private $commentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="uploaded_by", type="integer")
     */
    private $uploadedBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="filesize", type="integer")
     */
    private $filesize;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="fileurl", type="string", length=255)
     */
    private $fileurl;

    /**
     * @var string
     *
     * @ORM\Column(name="fileextension", type="string", length=20)
     */
    private $fileextension;

    /**
     * @var string
     *
     * @ORM\Column(name="upload_token", type="string", length=255)
     */
    private $uploadToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set issueId
     *
     * @param integer $issueId
     * @return Projects_issues_attachments
     */
    public function setIssueId($issueId)
    {
        $this->issueId = $issueId;

        return $this;
    }

    /**
     * Get issueId
     *
     * @return integer 
     */
    public function getIssueId()
    {
        return $this->issueId;
    }

    /**
     * Set commentId
     *
     * @param integer $commentId
     * @return Projects_issues_attachments
     */
    public function setCommentId($commentId)
    {
        $this->commentId = $commentId;

        return $this;
    }

    /**
     * Get commentId
     *
     * @return integer 
     */
    public function getCommentId()
    {
        return $this->commentId;
    }

    /**
     * Set uploadedBy
     *
     * @param integer $uploadedBy
     * @return Projects_issues_attachments
     */
    public function setUploadedBy($uploadedBy)
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    /**
     * Get uploadedBy
     *
     * @return integer 
     */
    public function getUploadedBy()
    {
        return $this->uploadedBy;
    }

    /**
     * Set filesize
     *
     * @param integer $filesize
     * @return Projects_issues_attachments
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Get filesize
     *
     * @return integer 
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return Projects_issues_attachments
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }


    /**
     * Set fileurl
     *
     * @param string $fileurl
     * @return Projects_issues_attachments
     */
    public function setFileurl($fileurl)
    {
        $this->fileurl = $fileurl;

        return $this;
    }

    /**
     * Get fileurl
     *
     * @return string
     */
    public function getFileurl()
    {
        return $this->fileurl;
    }

    /**
     * Set fileextension
     *
     * @param string $fileextension
     * @return Projects_issues_attachments
     */
    public function setFileextension($fileextension)
    {
        $this->fileextension = $fileextension;

        return $this;
    }

    /**
     * Get fileextension
     *
     * @return string 
     */
    public function getFileextension()
    {
        return $this->fileextension;
    }

    /**
     * Set uploadToken
     *
     * @param string $uploadToken
     * @return Projects_issues_attachments
     */
    public function setUploadToken($uploadToken)
    {
        $this->uploadToken = $uploadToken;

        return $this;
    }

    /**
     * Get uploadToken
     *
     * @return string 
     */
    public function getUploadToken()
    {
        return $this->uploadToken;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Projects_issues_attachments
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Projects_issues_attachments
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
