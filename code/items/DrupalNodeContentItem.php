<?php
/**
 * A content item that represents a Drupal node. Roughly the equivalent of a
 * Page in SilverStripe.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalNodeContentItem extends DrupalMenuLinkContentItem {

	/**
	 * @param  array $data
	 * @return DrupalNodeContentItem
	 */
	public static function factory($source, $data) {
		$menuLinkID = 0;
		if (isset($data['link'])) {
			$menuLinkID = $data['link']['mlid'];
		}

		$item = new self($source, $menuLinkID);

		$item->loadData($data);

		return $item;
	}

	protected function loadData($data) {
		parent::loadData($data);

		if (isset($data['node'])) {
			$nodeData = $data['node'];

			$this->DrupalNodeID = $nodeData['vid'];
			$this->CreatedAt = strtotime($nodeData['created']);
			$this->UserID = $nodeData['uid'];
			$this->Status = $nodeData['status'];
			$this->Language = $nodeData['language'];
			$this->Title = html_entity_decode($nodeData['title']);

			$this->Body = $nodeData['body'][$this->Language][0]['value'];
			// if no <p> or <br/> tags, assume that this is plain text and needs to be converted to HTML.
			if (strpos($this->Body, '<p>') === false && strpos($this->Body, '<br/>') === false) {
				$this->Body = str_replace("\n\n", '</p><p>', $this->Body);
				$this->Body = str_replace("\n\n", "\n", $this->Body);
				$this->Body = str_replace("\n", '<br/>', $this->Body);
				$this->Body = '<p>' . $this->Body . '</p>';
			}
		}
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('DrupalNodeID');
		$fields->removeByName('CreatedAt');
		$fields->removeByName('UserID');
		$fields->removeByName('Status');
		$fields->removeByName('Language');
		$fields->removeByName('Title');
		$fields->removeByName('Body');

		$fields->addFieldsToTab('Root.Node', array(
			new ReadonlyField('DrupalNodeID', 'Drupal Node ID', $this->DrupalNodeID),
			new ReadonlyField('Title', 'Title', $this->Title),
			new ReadonlyField('Body', 'Body', $this->Body),
			new ReadonlyField('CreatedAt', 'Created Date', $this->CreatedAt),
			new ReadonlyField('UserID', 'UserID', $this->UserID),
			new ReadonlyField('Status', 'Status', $this->Status),
			new ReadonlyField('Language', 'Language', $this->Language)
		));

		return $fields;
	}

	public function getType() {
		return 'DrupalNode';
	}
}