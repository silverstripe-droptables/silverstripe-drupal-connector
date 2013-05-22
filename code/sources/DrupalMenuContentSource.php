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

	protected function createContentItem($type, $id) {
		if ($type == 'menulink') {
			return DrupalMenuLinkContentItem::factory($this, $this->getMenuLink($id));
		}

		return parent::createContentItem($type, $id);
	}

	public function stageChildren($showAll = false) {
		$children = new ArrayList();

		$menuChildren = $this->getMenu();
		if ($menuChildren) foreach ($menuChildren as $child) {
			$children->push(DrupalMenuLinkContentItem::factory($this, $child));
		}

		return $children;
	}

	public function allowedImportTargets() {
		return array('sitetree' => true);
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new TextField('MenuName', 'Menu Name'), 'ShowContentInMenu');

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
			$menuLink = $this->getMenuLink($this->parseID($parentID));
			$items = $menuLink['children'];
		}

		if ($items) foreach ($items as $item) {
			$result->push(DrupalMenuLinkContentItem::factory($this, $item));
		}

		// TODO: load children if path is a taxonomy node

		return $result;
	}

	public function canCreate($member = null) {
		return true;
	}

	/**
	 * Looks through the links in the menu for a particular id.
	 * @param int ID The mlid to search for.
	 */
	protected function getMenuLink($ID) {
		$menu = $this->getMenu();
		if ($menu) {
			$menuLinks = array_values($menu);

			while (count($menuLinks) > 0) {
				$menuLink = $menuLinks[0];
				unset($menuLinks[0]);
				if ($menuLink['link']['mlid'] == $ID) {
					return $menuLink;
				}

				$menuLinks = array_merge($menuLinks, array_values($menuLink['children']));
			}
		}
	}

	/**
	 * Returns an array containing the top-level elements in the menu's tree
	 * @return array
	 */

	protected function getMenu() {
		try {
			$menu = $this->RPC('menu.retrieve', array($this->MenuName));
		} catch (Zend_Exception $exception) {
			SS_Log::log($exception, SS_Log::ERR);
			return NULL;
		}

		if ($menu['name'] == $this->MenuName) {
			return $menu['tree'];
		}
	}
}