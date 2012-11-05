<?php
/**
 * A content item that represents a Drupal taxonomy term.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalTaxonomyTermContentItem extends ExternalContentItem {

	/**
	 * @param  array $data
	 * @return DrupalTermContentItem
	 */
	public static function factory($source, $data) {
		$item = new self($source, 'taxonomyterm:' . $data['tid']);

		$item->DrupalID = $data['tid'];
		$item->VocabularyID = $data['vid'];
		$item->TermName = html_entity_decode($data['name']);
		$item->Title = $item->TermName;
		$item->Description = html_entity_decode($data['description']);
		$item->Weight = $data['weight'];
		if (isset($data['language'])) $item->Language = $data['language'];
		if (isset($data['trid'])) $item->TRID = $data['trid'];
		$item->Depth = $data['depth'];

		$parents = new ArrayData(array());
		foreach ($data['parents'] as $key => $value) {
			$parents->setField($key, $value);
		}
		$item->Parents = $parents;

		// Set the name for the tree.
		$item->Name = $item->TermName;

		return $item;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		// Basic information
		$fields->fieldByName('Root.Main')->getChildren()->changeFieldOrder(array(
			'TermName', 'Description'
		));
		$fields->addFieldToTab('Root.Main', new ReadonlyField('DrupalTermID', 'Term ID'), 'Name');
		$fields->removeByName('Parents');

		// Details
		$parentsText = '(none)';
		$parents = $this->Parents->toMap();
		if ($parents) {
			$parentsText = '';
			foreach ($parents as $key => $value) {
				$parentsText .= "$value, ";
			}
			$parentsText = substr($parentsText, 0, -2);
		}
		$fields->addFieldToTab('Root.Details', new ReadonlyField('ParentList', 'Parents', $parentsText));

		$fields->addFieldsToTab('Root.Behaviour', array(
			new ReadonlyField('VocabularyID', 'VocabularyID', $this->VocabularyID),
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
		return 'DrupalTaxonomyTerm';
	}
}