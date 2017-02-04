<?php
/**
 * A menu link that was imported from a remote Drupal installation.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalMenuLink extends Page
{

    public static $db = array(
        'DrupalID' => 'Int',
        'OriginalData' => 'Text'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Drupal', array(
            new ReadonlyField('DrupalID', 'Drupal Menu Link ID'),
            new ReadonlyField('OriginalData', 'Original Drupal Data')
        ));

        return $fields;
    }
}

class DrupalMenuLink_Controller extends Page_Controller
{
}
