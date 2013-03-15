<?php

namespace Egzakt\MailChimpBundle\Controller;

use Egzakt\MailChimpBundle\Entity\SubscriberList;
use Egzakt\MailChimpBundle\Lib\MailChimpApi;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotBlankValidator;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateValidator;

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


    protected $error = false;

    protected $errors = array();

    /**
     * Init
     *
     * Init the API, get the List Fields and Groupings
     *
     * @param integer $id The Subscription List id
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
        $this->fields = $this->normalizeFields($this->fields);
        $this->groupings = $this->normalizeGroupings($this->groupings);
    }

    /**
     * Normalize Fields
     *
     * Return a normalized field type for form creation and removes fields we don't want to show
     *
     * @param array $fields The list of fields
     *
     * @return array
     */
    protected function normalizeFields($fields)
    {
        // Convert "special" types to standard form field types
        $normalizedTypes = array(
            'email' => 'text',
            'imageurl' => 'text',
            'url' => 'text',
            'zip' => 'text',
            'number' => 'text',
            'birthday' => 'date',
            'address' => 'text'
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
     * Normalize Groupings
     *
     * Return an array of grouping having the name as value instead of an array of parameters
     *
     * @param array $groupings The Groupings list
     *
     * @return array
     */
    protected function normalizeGroupings($groupings)
    {
        foreach($groupings as $key => $grouping) {

            foreach($grouping['groups'] as $k => $group) {
                $groupings[$key]['groups'][$k] = $group['name'];
            }
        }

        return $groupings;
    }

    /**
     * Display Form
     *
     * Display the form to register to a Subscription List
     *
     * @param integer $id The SubscriberList id
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
            'groupings' => $this->groupings
        ));
    }

    /**
     * Subscribe Action
     *
     * Process the submitted form to register a new subscription. This action is called via AJAX.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     */
    public function subscribeAction(Request $request, $id)
    {
        // Init the list
        $this->init($id);

        if ($request->getMethod() == 'POST') {

            // Parameters to send to MailChimp
            $mergeVars = array();

            // POST form fields
            $postedFields = $request->request->get('mailchimp_fields');

            // Loop through the fields to validate
            foreach($this->fields as $field) {
                if ($field['req']) {

                }
            }
        }

        return $this->render('EgzaktMailChimpBundle:MailChimp:subscribe.html.twig', array(
            'response' => json_encode($response)
        ));
    }

}
