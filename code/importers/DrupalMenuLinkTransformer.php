<?php
/**
 * Transforms a remote Drupal menu link into a local {@link DrupalMenuLink} instance.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalMenuLinkTransformer extends DrupalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$page = new DrupalMenuLink();
		$params = $this->importer->getParams();

		$existingPage = DataObject::get_one('DrupalMenuLink', sprintf(
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
		$page->MenuTitle = $item->MenuTitle;
		$page->ParentID = $parent->ID;

		$page->DrupalID = $item->DrupalID;
		$page->OriginalData = serialize($item->getRemoteProperties());

		// Write the attached node, if any exists.
		if ($item->Node) {
			$page->Content = $item->Node->Body;
		}

		$page->write();

		$this->importMedia($item, $page);
		$this->importAttachments($item->Node, $page);

		return new TransformResult($page, $item->stageChildren());
	}

	public function setImporter($importer) {
		$this->importer = $importer;
	}
}