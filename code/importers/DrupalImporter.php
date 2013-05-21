<?php
/**
 * @package silverstripe-drupal-importer
 */
class DrupalImporter extends ExternalContentImporter {

	public function __construct() {
		$node = new DrupalNodeTransformer();
		$node->setImporter($this);

		$menuLink = new DrupalMenuLinkTransformer();
		$menuLink->setImporter($this);

		$taxonomyTerm = new DrupalTaxonomyTermTransformer();
		$taxonomyTerm->setImporter($this);

		$this->contentTransforms['node'] = $node;
		$this->contentTransforms['menuLink'] = $menuLink;
		$this->contentTransforms['taxonomyTerm'] = $taxonomyTerm;

		parent::__construct();
	}

	public function getExternalType($item) {
		switch ($item->class) {
			case 'DrupalNodeContentItem': return 'node';
			case 'DrupalMenuLinkContentItem': return 'menuLink';
			case 'DrupalTaxonomyTermContentItem': return 'taxonomyTerm';
		}
	}

	public function import($contentItem, $target, $includeParent = false, $includeChildren = true, $duplicateStrategy='Overwrite', $params = array()) {
		parent::import($contentItem, $target, $includeParent, $includeChildren, $duplicateStrategy, $params);

		if ($this->has_extension('PostImportStepExtension') && !is_a($target, 'TaxonomyTerm')) {
			$extension = $this->getExtensionInstance('PostImportStepExtension');
			if ($extension) {
				// Build up a list of link aliases to page IDs.
				$linkRewrites = array();
				foreach ($extension->importedPages as $page) {
					$originalData = unserialize($page->OriginalData);
					foreach (array('Path', 'PathAlias') as $key) {
						if (array_key_exists($key, $originalData)) {
							$linkRewrites['/' . $originalData[$key]] = $page->ID;
						}
					}
				}

				// Go through all imported pages and rewrite any necessary links.
				foreach ($extension->importedPages as $page) {
					// Find the href attribute of every a element.
					$pattern = '/<a [^>]*href="([^"]*)/';

					if (!preg_match_all($pattern, $page->Content, $matches)) continue;

					// Loop through all the matches to ([^"]*), which captures the link href.
					$numReplacements = 0;
					foreach ($matches[1] as $match) {
						if (array_key_exists($match, $linkRewrites)) {
							$replacement = '[sitetree_link,id=' . $linkRewrites[$match] . ']';
							$page->Content = str_replace("\"$match\"", "\"$replacement\"", $page->Content);
							$numReplacements++;
						}
					}

					if ($numReplacements > 0) {
						$page->write();
					}
				}
			}
		}
	}
}