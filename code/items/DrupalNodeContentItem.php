<?php
/**
 * A content item that represents a Drupal node. Roughly the equivalent of a
 * Page in SilverStripe.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalNodeContentItem extends ExternalContentItem
{

    /**
     * @param  array $data
     * @return DrupalNodeContentItem
     */
    public static function factory($source, $data)
    {
        $item = new self($source, 'node:' . $data['nid']);

        $item->DrupalID = $data['nid'];
        $item->VersionID = $data['vid'];
        $item->CreatedAt = strftime('%F %T', $data['created']);
        $item->ChangedAt = strftime('%F %T', $data['changed']);
        $item->UserID = $data['uid'];
        $item->Status = $data['status'];
        $item->Language = $data['language'];
        $item->Title = html_entity_decode($data['title']);
        $item->Files = array();
        if (array_key_exists('files', $data)) {
            $item->Files = $data['files'];
        }
        $item->Tags = array();
        if (array_key_exists('taxonomy', $data)) {
            $item->Tags = $data['taxonomy'];
        }

        $item->Body = $data['body'];
        if ($source->DrupalVersion == '7.x') {
            $item->Body = $item->Body[$item->Language][0]['value'];
        }

        // if no <p>, <br/>, <table> or <div> tags, assume that this is plain text and needs to be converted to HTML.
        if (stripos($item->Body, '<p') === false &&
            stripos($item->Body, '<br') === false &&
            stripos($item->Body, '<div') === false &&
            stripos($item->Body, '<table') === false) {
            $item->Body = str_replace("\n\n", '</p><p>', $item->Body);
            $item->Body = str_replace("\n\n", "\n", $item->Body);
            $item->Body = str_replace("\n", '<br/>', $item->Body);
            $item->Body = '<p>' . $item->Body . '</p>';
        }

        // Strip away any &nbsp;. Drupal seems to like inserting them  where it really should just have spaces.
        $item->Body = str_replace('&nbsp;', ' ', $item->Body);

        // Set the name for the tree.
        $item->Name = $item->Title;

        return $item;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('DrupalID');
        $fields->removeByName('CreatedAt');
        $fields->removeByName('ChangedAt');
        $fields->removeByName('UserID');
        $fields->removeByName('Status');
        $fields->removeByName('Language');
        $fields->removeByName('Title');
        $fields->removeByName('Body');

        $fields->addFieldsToTab('Root.Node', array(
            new ReadonlyField('DrupalID', 'Drupal Node ID', $this->DrupalID),
            new ReadonlyField('Title', 'Title', $this->Title),
            new ReadonlyField('Body', 'Body', $this->Body),
            new ReadonlyField('CreatedAt', 'Created Date', $this->CreatedAt),
            new ReadonlyField('ChangedAt', 'Modified Date', $this->ChangedAt),
            new ReadonlyField('UserID', 'UserID', $this->UserID),
            new ReadonlyField('Status', 'Status', $this->Status),
            new ReadonlyField('Language', 'Language', $this->Language)
        ));

        return $fields;
    }

    // Nodes don't have a native hierarchy, only as part of a menu or taxonomy structure.
    public function stageChildren($showAll = false)
    {
        return null;
    }

    public function getType()
    {
        return 'DrupalNode';
    }
}
