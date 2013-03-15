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
     * @param int $id The Subscription List id
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

        // Remove hidden fields
        $this->fields = $this->normalizeData($this->fields);

        $a = $this->fields;
    }

    /**
     * Normalize Data
     *
     * Return a normalized field type for form creation and removes fields we don't want to show
     *
     * @param array $fields
     *
     * @return array
     */
    protected function normalizeData($fields)
    {
        // Convert "special" types to standard form field types
        $normalizedTypes = array(
            'email' => 'text',
            'imageurl' => 'text',
            'url' => 'text',
            'zip' => 'text',
            'number' => 'text',
            'birthday' => 'date'
        );

        foreach($fields as $key => $field) {
            if (!$field['show']) {
                unset($fields[$key]);
                continue;
            }

            if (array_key_exists($field['field_type'], $normalizedTypes)) {
                $fields[$key]['field_type'] = $normalizedTypes[$field['field_type']];
            }
        }

        return $fields;
    }

    /**
     * Display Form
     *
     * Display the form to register to a Subscription List
     *
     * @param int $id The SubscriberList id
     *
     * @return Response
     */
    public function displayFormAction($id)
    {
        // Init the list
        $this->init($id);

        return $this->render('EgzaktMailChimpBundle:MailChimp:displayForm.html.twig', array(
            'subscriberList' => $this->subscriberList,
            'fields' => $this->fields,
            'grouping' => $this->groupings
        ));
    }

}
