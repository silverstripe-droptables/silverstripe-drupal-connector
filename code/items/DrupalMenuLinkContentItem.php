<?php
/**
 * A content item that represents a Drupal menu entry.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalMenuLinkContentItem extends ExternalContentItem {

	/**
	 * @param  array $data
	 * @return DrupalTermContentItem
	 */
	public static function factory($source, $data) {
		$item = new self($source, $data['link']['mlid']);

		$item->loadData($data);

		return $item;
	}

	protected function loadData($data) {
		if (isset($data['link'])) {
			$linkData = $data['link'];

			$this->DrupalMenuLinkID = $linkData['mlid'];
			$this->MenuTitle = html_entity_decode($linkData['title']);
			$this->Description = $linkData['description'];
			$this->Path = $linkData['path'];
			$this->PathAlias = $linkData['path_alias'];
			$this->Href = $linkData['href'];
			$this->Hidden = $linkData['hidden'];
			$this->External = $linkData['external'];
			$this->HasChildren = $linkData['has_children'];
			$this->Weight = $linkData['weight'];
			$this->Depth = $linkData['depth'];

			$options = new ArrayData(array());
			foreach ($linkData['options'] as $key => $value) {
				$options->setField($key, $value);
			}
			$this->Options = $options;
		}

		// Set the name for the tree.
		$this->Name = $this->MenuTitle;

		// Set up this default. It will get overridden by the DrupalNodeContentItem base class.
		$this->Title = $this->MenuTitle;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		// Basic information
		$fields->fieldByName('Root.Main')->getChildren()->changeFieldOrder(array(
			'MenuTitle', 'Description', 'Path', 'Href'
		));
		$fields->addFieldToTab('Root.Main', new ReadonlyField('DrupalMenuLinkID', 'Menu Link ID'), 'Title');
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

		return $fields;
	}

	public function stageChildren($showAll = false) {
		return $this->source->getItemsByParentId($this->externalId);
	}

	public function getType() {
		return 'DrupalMenuLink';
	}
}