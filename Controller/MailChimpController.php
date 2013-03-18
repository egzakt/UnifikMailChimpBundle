<?php

namespace Egzakt\MailChimpBundle\Controller;

use Egzakt\MailChimpBundle\Entity\SubscriberList;
use Egzakt\MailChimpBundle\Lib\MailChimpApi;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
        $notBlankValidator = new NotBlankValidator();

        if (is_array($value)) {
            if (!count($value)) {
                $this->error = true;
            }
        } else {
            if (!$notBlankValidator->isValid(trim($value), $notBlankConstraint)) {
                $this->error = true;
            }
        }

        if ($this->error) {
            $this->errors[] = $this->get('translator')->trans('%field% is required.', array('%field%' => $fieldName));
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
        $emailValidator = new EmailValidator();

        if (!$emailValidator->isValid($value, $emailConstraint)) {
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
        $dateValidator = new DateValidator();

        if (!$dateValidator->isValid($value, $dateConstraint)) {
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
        $urlValidator = new UrlValidator();

        if (!$urlValidator->isValid($value, $urlConstraint)) {
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
     *
     * @param array $postedFields The posted fields
     */
    protected function processFields($postedFields)
    {
        // Loop through the fields to validate
        foreach($this->fields as $field) {

            // If field was not posted, we set a null value
            if (!isset($postedFields[$field['tag']])) {
                $postedFields[$field['tag']] = null;
            }

            // Required field
            if ($field['req']) {
                // Address field is splitted in 6 different fields
                if ($field['field_type'] == 'address') {
                    // addr2 is not required
                    foreach(array('addr1' => 'Address 1', 'city' => 'City', 'state' => 'Province/State', 'zip' => 'Postal Code/ZIP', 'country' => 'Country') as $addrField => $addrFieldName) {
                        // If field was not posted, we set a null value
                        if (!isset($postedFields[$field['tag']])) {
                            $postedFields[$field['tag']][$addrField] = null;
                        }

                        $this->isNotBlank($this->get('translator')->trans($addrFieldName), $postedFields[$field['tag']][$addrField]);
                    }
                // Regular field
                } else {
                    $this->isNotBlank($field['name'], $postedFields[$field['tag']]);
                }
            }

            // Different validations based on field type
            switch($field['field_type']) {
                case 'email':
                    $this->isEmailValid($field['name'], $postedFields[$field['tag']]);
                    break;
                case 'date':
                case 'birthday':
                    $this->isDateValid($field['name'], $postedFields[$field['tag']]);
                    break;
                case 'url':
                case 'imageurl':
                    $this->isUrlValid($field['name'], $postedFields[$field['tag']]);
                    break;
                case 'number':
                    $this->isNumberValid($field['name'], $postedFields[$field['tag']]);
                    break;
            }

            $this->mergeVars[$field['tag']] = $postedFields[$field['tag']];
        }

        $this->mergeVars['GROUPINGS'] = array();
    }

    /**
     * Process Groupings
     *
     * Validate posted values and prepare the groupings data to send to MailChimp
     *
     * @param array $postedFields The posted fields
     */
    protected function processGroupings($postedFields)
    {
        // Loop through the grouping to validate
        foreach($this->groupings as $grouping) {
            // If field was posted
            if (isset($postedFields[$grouping['id']])) {
                // Commas in Interest Group names should be escaped with a backslash. ie, "," => "\," and either an "id" or "name" parameter to specify the Grouping
                if (is_array($postedFields[$grouping['id']])) {
                    array_walk($postedFields[$grouping['id']], function(&$value, $key) {
                        $value = str_replace(',', '\,', $value);
                    });
                    $values = implode(',', $postedFields[$grouping['id']]);
                } else {
                    $values = $postedFields[$grouping['id']];
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

        return $this->render('EgzaktMailChimpBundle:MailChimp:displayForm.html.twig', array(
            'subscriberList' => $this->subscriberList,
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
            $postedFields = $request->request->get('mailchimp_fields');

            // Process the list fields
            $this->processFields($postedFields);

            // Process the groupings
            $this->processGroupings($postedFields);

            // No errors, send data to MailChimp
            if (!$this->error) {

            }
        }

        return $this->render('EgzaktMailChimpBundle:MailChimp:subscribe.html.twig', array(
            'response' => json_encode(array(
                    'error' => $this->error,
                    'errorMessage' => $this->errors
            ))
        ));
    }

}
