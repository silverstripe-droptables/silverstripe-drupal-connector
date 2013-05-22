<?php
/**
 * Transforms a remote Drupal node into a local {@link DrupalNode} instance.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalNodeTransformer extends DrupalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$page = new DrupalNode();
		$params = $this->importer->getParams();

		$existingPage = DataObject::get_one('DrupalNode', sprintf(
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
		$page->Content = $item->Body;
		$page->ParentID = $parent->ID;

		$page->DrupalID = $item->DrupalID;
		$page->OriginalData = serialize($item->getRemoteProperties());
		$page->write();

		$this->importMedia($item, $page);
		$this->importAttachments($item, $page);
		$this->ImportTags($item, $page);

		return new TransformResult($page, $item->stageChildren(), $item);
	}

	public function setImporter($importer) {
		$this->importer = $importer;
	}
}