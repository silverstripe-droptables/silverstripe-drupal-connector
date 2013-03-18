<?php
/**
 * A content source that displays the menu of a Drupal site.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalTaxonomyContentSource extends DrupalContentSource {

	protected $taxonomy_cache = null;

	public static $db = array(
		'TaxonomyID' => 'Varchar(255)'
	);

	public function getRoot() {
		return $this;
	}

	protected function createContentItem($type, $id) {
		if ($type == 'taxonomyterm') {
			return $this->getTaxonomyTerm($id);
		}

		return parent::createContentItem($type, $id);
	}

	protected function getTaxonomyTerm($id) {
		$taxonomy = $this->getTaxonomy();
		foreach($taxonomy as $term) {
			if ($term['tid'] == $id) {
				return DrupalTaxonomyTermContentItem::factory($this, $term);
			}
		}
	}

	public function stageChildren($showAll = false) {
		$children = new ArrayList();

		$taxonomyChildren = $this->getTaxonomy();
		if ($taxonomyChildren) foreach ($taxonomyChildren as $child) {
			$root = false;
			foreach ($child['parents'] as $parent) {
				if ($parent == 0) {
					$root = true;
					break;
				}
			}

			if ($root) {
				$children->push(DrupalTaxonomyTermContentItem::factory($this, $child));
			}
		}

		return $children;
	}

	public function allowedImportTargets() {
		return array('sitetree' => true);
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new TextField('TaxonomyID', 'Taxonomy ID'), 'ShowContentInMenu');

		$fields->addFieldsToTab('Root.Import', array(
			new CheckboxField('ImportMedia',
				'Import and rewrite references to Drupal media?', true),
			new TextField('AssetsPath',
				'Upload Drupal files to', 'Uploads/Drupal')
		));

		return $fields;
	}

	/**
	 * Gets all the node content items that sit under a parent ID.
	 *
	 * @param  int $parent
	 * @return DataObjectSet
	 */
	public function getItemsByParentId($parentID = null) {
		$result = new ArrayList();
		
		if (!$this->isValid()) {
			return $result;
		}

		if ($parentID == null) {
			return $this->stageChildren();
		} else {
			$parentID = $this->parseID($parentID);

			// Fetch the child terms.
			$taxonomy = $this->getTaxonomy();
			foreach($taxonomy as $term) {
				$child = false;
				foreach ($term['parents'] as $parent) {
					if ($parent == $parentID) {
						$child = true;
						break;
					}
				}

				if ($child) {
					$result->push(DrupalTaxonomyTermContentItem::factory($this, $term));
				}
			}

			// Fetch the child nodes.
			$method = '';
			$parameters = array();

			if ($this->DrupalVersion == '5.x') {
				$method = 'taxonomy.selectNodes';
				$parameters = array(array($parentID), array('nid'), 'or', 0, FALSE, 'n.sticky DESC, n.created DESC');
			}
			else {
				$method = 'taxonomy_term.selectNodes';
				$parameters = array($parentID, FALSE, FALSE, 'n.sticky DESC, n.created DESC');
			}

			$nodes = NULL;
			try {
				$nodes = $this->RPC($method, $parameters);
			} catch (Zend_XmlRpc_Client_FaultException $exception) {
				// This gets thrown if there are not nodes matching the tid.
			}

			if ($nodes) {
				foreach ($nodes as $node) {
					$result->push($this->getNode($node['nid']));
				}
			}
		}

		return $result;
	}

	public function canCreate($member = null) {
		return true;
	}

	/**
	 * Returns an array containing the top-level elements in the menu's tree
	 * @return array
	 */

	protected function getTaxonomy() {
		if ($this->taxonomy_cache) {
			return $this->taxonomy_cache;
		}

		try {
			$method = 'taxonomy_vocabulary.getTree';
			if ($this->DrupalVersion == '5.x') {
				$method = 'taxonomy.getTree';
			}
			$taxonomy = $this->RPC($method, array($this->TaxonomyID));
		} catch (Zend_Exception $exception) {
			SS_Log::log($exception, SS_Log::ERR);
			return NULL;
		}

		$this->taxonomy_cache = $taxonomy;
		return $taxonomy;
	}
}