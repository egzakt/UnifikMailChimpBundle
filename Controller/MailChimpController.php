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
     * @var array $fields The field list
     */
    protected $fields;

    /**
     * @var array $groupings The Interest Groupings (Select-Multiple field which is not available from field list)
     */
    protected $groupings;

    /**
     * Init
     *
     * Init the API, get the List Fields and Groupings
     *
     * @param $id The Subscription List id
     *
     * @throws \Exception
     */
    protected function init($id)
    {
        $this->api = $this->get('egzakt_mail_chimp.api')->getApi();

        $this->subscriberList = $this->get('doctrine')->getEntityManager()->getRepository('EgzaktMailChimpBundle:SubscriberList')->find($id);

        if (!$this->subscriberList) {
            throw new \Exception('Unable to find this Subscriber List');
        }

        $this->fields = $this->api->listMergeVars($this->subscriberList->getListId());
        $this->groupings = $this->api->listInterestGroupings($this->subscriberList->getListId());
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
        // Init the list
        $this->init($id);

        return new Response('a');
    }

}
