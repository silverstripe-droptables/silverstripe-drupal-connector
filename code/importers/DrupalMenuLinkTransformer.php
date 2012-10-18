<?php
/**
 * Transforms a remote Drupal menu link into a local {@link DrupalMenuLink} instance.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalMenuLinkTransformer implements ExternalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$page = new DrupalMenuLink();
		$params = $this->importer->getParams();

		$exists = DataObject::get_one('DrupalMenuLink', sprintf(
			'"DrupalID" = %d AND "ParentID" = %d', $item->DrupalMenuLinkID, $parent->ID
		));

		if ($exists) switch ($strategy) {
			case ExternalContentTransformer::DS_OVERWRITE:
				$page = $exists;
				break;
			case ExternalContentTransformer::DS_DUPLICATE:
				break;
			case ExternalContentTransformer::DS_SKIP:
				return;
		}

		$page->Title = $item->MenuTitle;
		$page->MenuTitle = $item->MenuTitle;
		$page->ParentID = $parent->ID;

		$page->DrupalID = $item->DrupalMenuLinkID;
		$page->OriginalData = serialize($item->getRemoteProperties());
		$page->write();

		return new TransformResult($page, $item->stageChildren());
	}

	public function setImporter($importer) {
		$this->importer = $importer;
	}
}