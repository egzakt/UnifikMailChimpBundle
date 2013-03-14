<?php

namespace Egzakt\MailChimpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Egzakt\Backend\CoreBundle\Lib\BaseTranslationEntity;

/**
 * Egzakt\MailChimpBundle\Entity\SubscriberListTranslation
 */
class SubscriberListTranslation extends BaseTranslationEntity
{

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $locale
     */
    protected $locale;

    /**
     * @var string $listId
     */
    protected $listId;

    /**
     * @var Egzakt\Backend\SubscriberBundle\Entity\SubscriberList
     */
    protected $translatable;

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
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
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
     * Set translatable
     *
     * @param Egzakt\Backend\SubscriberBundle\Entity\SubscriberList $translatable
     */
    public function setTranslatable(\Egzakt\Backend\SubscriberBundle\Entity\SubscriberList $translatable)
    {
        $this->translatable = $translatable;
    }

    /**
     * Get translatable
     *
     * @return Egzakt\Backend\SubscriberBundle\Entity\SubscriberList 
     */
    public function getTranslatable()
    {
        return $this->translatable;
    }

}