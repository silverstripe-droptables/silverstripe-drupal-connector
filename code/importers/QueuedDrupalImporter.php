<?php
/**
 * @package silverstripe-drupal-importer
 */
class QueuedDrupalImporter extends QueuedExternalContentImporter {

	public function init() {
		$node = new DrupalNodeTransformer();
		$node->setImporter($this);

		$menuLink = new DrupalMenuLinkTransformer();
		$menuLink->setImporter($this);

		$this->contentTransforms['node'] = $node;
		$this->contentTransforms['menuLink'] = $menuLink;
	}

	public function getExternalType($item) {
		switch ($item->class) {
			case 'DrupalNodeContentItem': return 'node';
			case 'DrupalMenuLinkContentItem': return 'menuLink';
		}
	}
}