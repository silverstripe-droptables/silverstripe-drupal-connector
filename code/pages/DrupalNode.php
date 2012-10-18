<?php
/**
 * A page that was imported from a remote Drupal installation.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalNode extends Page {

	public static $db = array(
		'DrupalID' => 'Int',
		'OriginalData' => 'Text'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Drupal', array(
			new ReadonlyField('DrupalID', 'Drupal Node ID'),
			new ReadonlyField('OriginalData', 'Original Drupal Data')
		));

		return $fields;
	}
}

class DrupalNode_Controller extends Page_Controller {
}
