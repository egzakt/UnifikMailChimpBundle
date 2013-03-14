<?php

namespace Egzakt\MailChimpBundle\Controller;

use Egzakt\MailChimpBundle\Entity\SubscriberList;
use Egzakt\MailChimpBundle\Lib\MailChimpApi;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MailChimpController extends Controller
{

    /**
     * @var MailChimpApi The MailChimp API
     */
    protected $api;

    /**
     * @var SubscriberList The Subscriber List
     */
    protected $subscriberList;


    /**
     * Init
     */
    public function init()
    {
        $this->api = $this->get('egzakt_mail_chimp.api')->getApi();
    }

    protected function initList($id)
    {
        $this->subscriberList = $this->get('doctrine')->getEntityManager()->getRepository('EgzaktMailChimpBundle:SubscriberList')->find($id);

        if (!$this->subscriberList) {
            throw new \Exception('Unable to find this Subscriber List');
        }

        $fields = $this->api->listMergeVars($this->subscriberList->getListId());

        $groupings = $this->api->listInterestGroupings($this->subscriberList->getListId());
    }

    /**
     * Display Form
     *
     * Display the form to register to a Subscription List
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayFormAction($id)
    {
        $this->init();
        $this->initList($id);

        return new Response('a');
    }

}
