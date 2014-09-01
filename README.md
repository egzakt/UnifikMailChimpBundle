UnifikMailChimpBundle
=====================

`UnifikMailChimpBundle` let you easily integrate a MailChimp Subscription Form in your application simply be configuring an API Key and a Subscription List Unique ID. It supports both List Fields and Groupings. The form is posted via AJAX to a controller inside this bundle so there is no need to create a controller to handle the posted form. All data is validated depending on the field types.

This bundle is still experimental and has been developed for Symfony 2.3,  it has not been tested on the latest versions of Symfony.

The form is posted via AJAX so jQuery >= 1.0 is required. If jQuery UI is loaded, datepicker will automatically be added on date fields.

## Content
* Installation
* How to use
* Screenshots

## Installation
1. Add the following to your `deps` file:
   ```yml
    [UnifikMailChimpBundle]
        git=http://github.com/unifik/UnifikMailChimpBundle.git
        target=bundles/Unifik/MailChimpBundle
   ```

2. Register the bundle in your `app/AppKernel.php`:
   ```php
    ...
    public function registerBundles()
    {
        $bundles = array(
        ...
            new Unifik\MailChimpBundle\UnifikMailChimpBundle(),
        ...
        );
    }
   ```

3. Import the `UnifikMailChimpBundle` routing to your `app/config/routing.yml`:
   ```yml
    UnifikMailChimpBundle:
        resource: "@UnifikMailChimpBundle/Resources/config/routing_frontend.yml"
   ```

4. Configure your MailChimp API Key in your `app/config/config.yml`:
   ```yml
    # MailChimp
    unifik_mail_chimp:
        api_key: 1234567890qwerty-us5
   ```

5. Update your database :
   ```
    app/console doctrine:schema:update --dump-sql
   ```

6. Create a new `MailChimpSubscriptionList` in your database, you will need to create a `MailChimpSubscriptionListTranslation` for each Locale of your application. The `listId` field is the List Unique Id that you can find in the "Settings" tab in the list configuration page.

## How to use
To display the Subscription Form in your application, simply include jQuery in your `<HEAD>` and add this line to a Twig template, where `id` is the ID of the `MailChimpSubscriptionList` in your database:

```html
<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
```

```twig
{{ render(controller('UnifikMailChimpBundle:Frontend/MailChimp:displayForm', { id: 1 })) }}
```

## Screenshots
List of available form field types:

![Available form field types](/doc/form.png)

Validations before sending the data to MailChimp API:

![Validations](/doc/validations.png)

Confirmation message when successfully subscribed:

![Confirmation](/doc/confirmation.png)
