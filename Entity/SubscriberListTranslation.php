<?php

namespace Unifik\MailChimpBundle\Entity;

use Unifik\DoctrineBehaviorsBundle\Model as UnifikORMBehaviors;

/**
 * Class SubscriberListTranslation
 * @package Unifik\MailChimpBundle\Entity
 */
class SubscriberListTranslation
{
    use UnifikORMBehaviors\Translatable\Translation;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $listId
     */
    protected $listId;

    /**
     * @var string
     */
    private $name;

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
     * Set listId
     *
     * @param string $listId
     */
    public function setListId($listId)
    {
        $this->listId = $listId;
    }

    /**
     * Get listId
     *
     * @return string 
     */
    public function getListId()
    {
        return $this->listId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return SubscriberListTranslation
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
}