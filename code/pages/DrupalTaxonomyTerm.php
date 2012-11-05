<?php
/**
 * A taxonomy term that was imported from a remote Drupal installation.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalTaxonomyTerm extends Page {

	public static $db = array(
		'DrupalID' => 'Int',
		'OriginalData' => 'Text'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Drupal', array(
			new ReadonlyField('DrupalID', 'Drupal Taxonomy Term ID'),
			new ReadonlyField('OriginalData', 'Original Drupal Data')
		));

		return $fields;
	}
}

class DrupalTaxonomyTerm_Controller extends Page_Controller {
}
