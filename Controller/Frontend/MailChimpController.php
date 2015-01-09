<?php

namespace Unifik\MailChimpBundle\Controller\Frontend;

use Unifik\MailChimpBundle\Entity\SubscriberList;
use Unifik\MailChimpBundle\Lib\MailChimpApi;

use Unifik\SystemBundle\Lib\Frontend\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Locale\Locale;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotBlankValidator;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateValidator;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\UrlValidator;

class MailChimpController extends BaseController
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
     * @var array $postedFields The posted fields
     */
    protected $postedFields;

    /**
     * @var array $mergeVars The array of parameters to send to MailChimp
     */
    protected $mergeVars = array();

    /**
     * @var bool $error
     */
    protected $error = false;

    /**
     * @var array $errors
     */
    protected $errors = array();

    /**
     * Init
     *
     * Init the API, get the List Fields and Groupings
     *
     * @throws \Exception
     */
    public function init()
    {
        $this->api = $this->get('unifik_mail_chimp.api')->getApi();

        $this->subscriberList = $this->get('doctrine')->getManager()->getRepository('UnifikMailChimpBundle:SubscriberList')->find(1);

        if (!$this->subscriberList) {
            throw new \Exception('Unable to find this Subscriber List');
        }

        $this->fields = $this->api->listMergeVars($this->subscriberList->getListId());
        $this->groupings = $this->api->listInterestGroupings($this->subscriberList->getListId());

        // There may be no groupings
        if (!$this->groupings) {
            $this->groupings = array();
        }
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
            'phone' => 'text'
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
     * Is Not Blank
     *
     * Validate a field
     *
     * @param string $fieldName
     * @param mixed  $value
     */
    protected function isNotBlank($fieldName, $value)
    {
        // NotBlank Validator
        $notBlankConstraint = new NotBlank();

        if (is_array($value)) {
            if (!count($value)) {
                $this->error = true;
                $this->errors[] = $this->get('translator')->trans('%field% is required.', array('%field%' => $fieldName));
            }
        } else {
            if (count($this->get('validator')->validateValue(trim($value), $notBlankConstraint)) > 0) {
                $this->error = true;
                $this->errors[] = $this->get('translator')->trans('%field% is required.', array('%field%' => $fieldName));
            }
        }
    }

    /**
     * Is Email Valid
     *
     * @param string $fieldName
     * @param mixed  $value
     */
    protected function isEmailValid($fieldName, $value)
    {
        $emailConstraint = new Email();

        if (count($this->get('validator')->validateValue($value, $emailConstraint)) > 0) {
            $this->error = true;
            $this->errors[] = $this->get('translator')->trans('%field% must be a valid email address.', array('%field%' => $fieldName));
        }
    }

    /**
     * Is Date Valid
     *
     * @param string $fieldName
     * @param mixed  $value
     */
    protected function isDateValid($fieldName, $value)
    {
        $dateConstraint = new Date();

        if (count($this->get('validator')->validateValue($value, $dateConstraint)) > 0) {
            $this->error = true;
            $this->errors[] = $this->get('translator')->trans('%field% must be a valid date.', array('%field%' => $fieldName));
        }
    }

    /**
     * Is Url Valid
     *
     * @param string $fieldName
     * @param mixed  $value
     */
    protected function isUrlValid($fieldName, $value)
    {
        $urlConstraint = new Url();

        if (!$this->get('validator')->validateValue($value, $urlConstraint)) {
            $this->error = true;
            $this->errors[] = $this->get('translator')->trans('%field% must be a valid URL.', array('%field%' => $fieldName));
        }
    }

    /**
     * Is Number Valid
     *
     * @param string $fieldName
     * @param mixed  $value
     */
    protected function isNumberValid($fieldName, $value)
    {
        if (!is_numeric($value)) {
            $this->error = true;
            $this->errors[] = $this->get('translator')->trans('%field% must be a valid number.', array('%field%' => $fieldName));
        }
    }

    /**
     * Process Fields
     *
     * Validate posted values and prepare the list fields data to send to MailChimp
     */
    protected function processFields()
    {
        // Loop through the fields to validate
        foreach($this->fields as $field) {

            // If field was not posted, we set a null value
            if (!isset($this->postedFields[$field['tag']])) {
                $this->postedFields[$field['tag']] = null;
            }

            // Required field
            if ($field['req']) {
                // Address field is splitted in 6 different fields
                if ($field['field_type'] == 'address') {
                    // addr2 is not required
                    foreach(array('addr1' => 'Address 1', 'city' => 'City', 'state' => 'Province/State', 'zip' => 'Postal Code/ZIP', 'country' => 'Country') as $addrField => $addrFieldName) {
                        // If field was not posted, we set a null value
                        if (!isset($this->postedFields[$field['tag']][$addrField])) {
                            $this->postedFields[$field['tag']][$addrField] = null;
                        }

                        $this->isNotBlank($this->get('translator')->trans($addrFieldName), $this->postedFields[$field['tag']][$addrField]);
                    }
                // Regular field
                } else {
                    $this->isNotBlank($field['name'], $this->postedFields[$field['tag']]);
                }
            }

            // Different validations based on field type
            switch($field['field_type']) {
                case 'email':
                    $this->isEmailValid($field['name'], $this->postedFields[$field['tag']]);
                    break;
                case 'date':
                    $this->isDateValid($field['name'], $this->postedFields[$field['tag']]);
                    $this->postedFields[$field['tag']] = date('Y/m/d', strtotime($this->postedFields[$field['tag']]));
                    break;
                case 'birthday':
                    $this->isDateValid($field['name'], $this->postedFields[$field['tag']]);
                    $this->postedFields[$field['tag']] = date('m/d', strtotime($this->postedFields[$field['tag']]));
                    break;
                case 'url':
                case 'imageurl':
                    $this->isUrlValid($field['name'], $this->postedFields[$field['tag']]);
                    break;
                case 'number':
                    $this->isNumberValid($field['name'], $this->postedFields[$field['tag']]);
                    break;
            }

            $this->mergeVars[$field['tag']] = $this->postedFields[$field['tag']];
        }
    }

    /**
     * Process Groupings
     *
     * Validate posted values and prepare the groupings data to send to MailChimp
     */
    protected function processGroupings()
    {
        $this->mergeVars['GROUPINGS'] = array();

        // Loop through the grouping to validate
        foreach($this->groupings as $grouping) {
            // If field was posted
            if (isset($this->postedFields[$grouping['id']])) {
                // Commas in Interest Group names should be escaped with a backslash. ie, "," => "\," and either an "id" or "name" parameter to specify the Grouping
                if (is_array($this->postedFields[$grouping['id']])) {
                    array_walk($this->postedFields[$grouping['id']], function(&$value, $key) {
                        $value = str_replace(',', '\,', $value);
                    });
                    
                    $values = implode(',', $this->postedFields[$grouping['id']]);
                } else {
                    $values = $this->postedFields[$grouping['id']];
                }

                $this->mergeVars['GROUPINGS'][] = array(
                    'id' => $grouping['id'],
                    'groups' => $values
                );
            }
        }
    }

    /**
     * Display Form
     *
     * Display the form to register to a Subscription List
     *
     * @param Request $request
     * @param integer $id The SubscriberList id
     *
     * @return Response
     */
    public function displayFormAction(Request $request, $id)
    {
        // Init the list
        $this->init($id);

        // Remove hidden fields
        $this->fields = $this->normalizeFields($this->fields);
        $this->groupings = $this->normalizeGroupings($this->groupings);

        return $this->render('UnifikMailChimpBundle:MailChimp:display_form.html.twig', array(
            'subscriber_list' => $this->subscriberList,
            'fields' => $this->fields,
            'groupings' => $this->groupings,
            'countries' => Locale::getDisplayCountries($request->getLocale())
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

            // POST form fields
            $this->postedFields = $request->request->get('mailchimp_fields');

            // Process the list fields
            $this->processFields();

            // Process the groupings
            $this->processGroupings();

            // No errors, send data to MailChimp
            if (!$this->error) {
                $this->api->listSubscribe($this->subscriberList->getListId(), $this->mergeVars['EMAIL'], $this->mergeVars, 'html', true, true);

                if ($this->api->errorCode) {
                    $this->error = true;
                    $this->errors[] = $this->api->errorMessage;
                }
            }
        }

        $response = new Response(json_encode(array(
            'error' => $this->error,
            'errorMessage' => $this->errors
        )));

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
