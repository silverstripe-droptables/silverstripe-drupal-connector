<?php
/**
 * Transforms a remote Drupal taxonomy term into a local {@link DrupalTaxonomyTerm} instance.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalTaxonomyTermTransformer implements ExternalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$page = new DrupalTaxonomyTerm();
		$params = $this->importer->getParams();

		$existingPage = DataObject::get_one('DrupalTaxonomyTerm', sprintf(
			'"DrupalID" = %d AND "ParentID" = %d', $item->DrupalID, $parent->ID
		));

		if ($existingPage) switch ($strategy) {
			case ExternalContentTransformer::DS_OVERWRITE:
				$page = $existingPage;
				break;
			case ExternalContentTransformer::DS_DUPLICATE:
				break;
			case ExternalContentTransformer::DS_SKIP:
				return;
		}

		$page->Title = $item->Title;
		$page->MenuTitle = $item->Title;
		$page->ParentID = $parent->ID;

		$page->DrupalID = $item->DrupalID;
		$page->OriginalData = serialize($item->getRemoteProperties());
		$page->write();

		return new TransformResult($page, $item->stageChildren());
	}

	public function setImporter($importer) {
		$this->importer = $importer;
	}
}