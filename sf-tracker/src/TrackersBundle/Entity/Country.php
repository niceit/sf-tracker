<?php

namespace TrackersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country
 *
 * @ORM\Table(name="project_country")
 * @ORM\Entity
 */
class Country
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="iso_code_2", type="string", length=10)
     */
    private $isoCode2;

    /**
     * @var string
     *
     * @ORM\Column(name="iso_code_3", type="string", length=10)
     */
    private $isoCode3;

    /**
     * @var string
     *
     * @ORM\Column(name="address_format", type="text")
     */
    private $addressFormat;

    /**
     * @var integer
     *
     * @ORM\Column(name="postcode_required", type="integer")
     */
    private $postcodeRequired;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;


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
     * Set name
     *
     * @param string $name
     * @return Country
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set isoCode2
     *
     * @param string $isoCode2
     * @return Country
     */
    public function setIsoCode2($isoCode2)
    {
        $this->isoCode2 = $isoCode2;

        return $this;
    }

    /**
     * Get isoCode2
     *
     * @return string 
     */
    public function getIsoCode2()
    {
        return $this->isoCode2;
    }

    /**
     * Set isoCode3
     *
     * @param string $isoCode3
     * @return Country
     */
    public function setIsoCode3($isoCode3)
    {
        $this->isoCode3 = $isoCode3;

        return $this;
    }

    /**
     * Get isoCode3
     *
     * @return string 
     */
    public function getIsoCode3()
    {
        return $this->isoCode3;
    }

    /**
     * Set addressFormat
     *
     * @param string $addressFormat
     * @return Country
     */
    public function setAddressFormat($addressFormat)
    {
        $this->addressFormat = $addressFormat;

        return $this;
    }

    /**
     * Get addressFormat
     *
     * @return string 
     */
    public function getAddressFormat()
    {
        return $this->addressFormat;
    }

    /**
     * Set postcodeRequired
     *
     * @param integer $postcodeRequired
     * @return Country
     */
    public function setPostcodeRequired($postcodeRequired)
    {
        $this->postcodeRequired = $postcodeRequired;

        return $this;
    }

    /**
     * Get postcodeRequired
     *
     * @return integer 
     */
    public function getPostcodeRequired()
    {
        return $this->postcodeRequired;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Country
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }
}
