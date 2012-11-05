<?php
/**
 * A content item that represents a Drupal menu link. It can be the equivalent of a holder or
 * landing page in SilverStripe, though if it is attached to a node then it's more like a Page.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalMenuLinkContentItem extends ExternalContentItem {

	/**
	 * @param  array $data
	 * @return DrupalTermContentItem
	 */
	public static function factory($source, $data) {
		$linkData = $data['link'];

		$item = new self($source, 'menulink:' . $linkData['mlid']);

		$item->DrupalID = $linkData['mlid'];
		$item->Title = html_entity_decode($linkData['title']);
		$item->MenuTitle = $item->Title;
		$item->Description = $linkData['description'];
		$item->Path = $linkData['path'];
		$item->PathAlias = $linkData['path_alias'];
		$item->Href = $linkData['href'];
		$item->Hidden = $linkData['hidden'];
		$item->External = $linkData['external'];
		$item->HasChildren = $linkData['has_children'];
		$item->Weight = $linkData['weight'];
		$item->Depth = $linkData['depth'];

		$options = new ArrayData(array());
		foreach ($linkData['options'] as $key => $value) {
			$options->setField($key, $value);
		}
		$item->Options = $options;

		// Set the name for the tree.
		$item->Name = $item->MenuTitle;

		// Check if we have to load any node content
		$item->Node = NULL;
		if (strlen($item->Path) > 5 && substr($item->Path, 0, 5) == 'node/') {
			$nodeId = 'node:' . substr($item->Path, 5);
			$item->Node = $source->getObject($source->encodeId($nodeId));
			$item->Title = $item->Node->Title;
		}

		return $item;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		// Basic information
		$fields->fieldByName('Root.Main')->getChildren()->changeFieldOrder(array(
			'MenuTitle', 'Description', 'Path', 'Href'
		));
		$fields->addFieldToTab('Root.Main', new ReadonlyField('DrupalID', 'Menu Link ID'), 'Title');
		$fields->removeByName('Options');

		// Details
		$optionsText = '(none)';
		$options = $this->Options->toMap();
		if ($options) {
			$optionsText = '';
			foreach ($options as $key => $value) {
				$optionsText .= "$key = $value, ";
			}
			$optionsText = substr($optionsText, 0, -2);
		}
		$fields->addFieldToTab('Root.Details', new ReadonlyField('OptionList', 'Options', $optionsText));

		$fields->addFieldsToTab('Root.Behaviour', array(
			new ReadonlyField('Hidden', 'Hidden', $this->Hidden),
			new ReadonlyField('External', 'External', $this->External),
			new ReadonlyField('HasChildren', 'HasChildren', $this->HasChildren),
			new ReadonlyField('Weight', 'Weight', $this->Weight),
			new ReadonlyField('Depth', 'Depth', $this->Depth)
		));

		if ($this->Node) {
			$fields->addFieldsToTab('Root.Node', array(
				new ReadonlyField('NodeDrupalID', 'Drupal ID', $this->Node->DrupalID),
				new ReadonlyField('NodeVersionID', 'Version ID', $this->Node->VersionID),
				new ReadonlyField('NodeCreatedAt', 'Created At', $this->Node->CreatedAt),
				new ReadonlyField('NodeUserID', 'User ID', $this->Node->UserID),
				new ReadonlyField('NodeStatus', 'Status', $this->Node->Status),
				new ReadonlyField('NodeLanguage', 'Language', $this->Node->Language),
				new ReadonlyField('NodeTitle', 'Title', $this->Node->Title),
				new ReadonlyField('NodeBody', 'Body', $this->Node->Body)
			));
		}

		return $fields;
	}

	public function stageChildren($showAll = false) {
		return $this->source->getItemsByParentId($this->externalId);
	}

	public function getType() {
		return 'DrupalMenuLink';
	}
}