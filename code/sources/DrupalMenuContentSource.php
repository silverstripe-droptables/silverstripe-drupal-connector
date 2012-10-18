<?php
/**
 * A content source that displays the menu of a Drupal site.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalMenuContentSource extends DrupalContentSource {

	public static $db = array(
		'MenuName' => 'Varchar(255)'
	);

	public static $defaults = array(
		'MenuName' => 'main-menu'
	);

	public function getRoot() {
		return $this;
	}

	public function getObject($id) {
		$client = $this->getClient($id);
		$id = $this->decodeId($id);

		$result = $this->getMenuLink($id);
		if ($result) {
			return $this->createContentItem($result);
		}
	}

	public function stageChildren($showAll = false) {
		$children = new ArrayList();

		$menuChildren = $this->getMenu();
		if ($menuChildren) foreach ($menuChildren as $child) {
			$children->push($this->createContentItem($child));
		}

		return $children;
	}

	public function allowedImportTargets() {
		return array('sitetree' => true);
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new TextField('MenuName', 'Menu Name'), 'ShowContentInMenu');

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
			$items = $this->getMenu();
		} else {
			$menuLink = $this->getMenuLink($parentID);
			$items = $menuLink['children'];
		}

		if ($items) foreach ($items as $item) {
			$result->push($this->createContentItem($item));
		}

		return $result;
	}

	public function canCreate($member = null) {
		return true;
	}

	/**
	 * Looks through an array of menu links for a particular id.
	 * @param array menuLink An array describing a Drupal menu link, as returned by the Services module.
	 * @param int ID The mlid to search for.
	 */
	protected function getMenuLink($ID) {
		$menuLinks = array_values($this->getMenu());

		while (count($menuLinks) > 0) {
			$menuLink = $menuLinks[0];
			unset($menuLinks[0]);
			if ($menuLink['link']['mlid'] == $ID) {
				return $menuLink;
			}

			$menuLinks = array_merge($menuLinks, array_values($menuLink['children']));
		}
	}

	/**
	 * Returns an array containing the top-level elements in the menu's tree
	 * @return array
	 */

	protected function getMenu() {
		try {
			$client = $this->getClient();
			$menu = $client->call("menu.retrieve", array($this->MenuName));
		} catch (Zend_Exception $exception) {
			SS_Log::log($exception, SS_Log::ERR);
			return NULL;
		}

		if ($menu['name'] == $this->MenuName) {
			return $menu['tree'];
		}
	}

	protected function createContentItem($item) {
		if (!isset($item['link'])) {
			return NULL;
		}

		$linkData = $item['link'];
		$menuLinkID = $linkData['mlid'];
		if (isset($linkData['path']) && strlen($linkData['path']) > 5 && substr($linkData['path'], 0, 5) == 'node/') {
			$nodeId = substr($linkData['path'], 5);
			$nodeData = $this->getNode($this->encodeId($nodeId));
			return DrupalNodeContentItem::factory($this, array('link' => $linkData, 'node' => $nodeData));
		}
		else {
			return DrupalMenuLinkContentItem::factory($this, array('link' => $linkData));
		}
	}
}