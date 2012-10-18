<?php
/**
 * Transforms a remote Drupal node into a local {@link DrupalNode} instance.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalNodeTransformer implements ExternalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$page = new DrupalNode();
		$params = $this->importer->getParams();

		$exists = DataObject::get_one('DrupalNode', sprintf(
			'"DrupalID" = %d AND "ParentID" = %d', $item->DrupalNodeID, $parent->ID
		));

		if ($exists) switch ($strategy) {
			case ExternalContentTransformer::DS_OVERWRITE:
				$page = $exists;
				break;
			case ExternalContentTransformer::DS_DUPLICATE:
				break;
			case ExternalContentTransformer::DS_SKIP:
				return;
		}

		$page->Title = $item->Title;
		$page->MenuTitle = isset($item->MenuTitle) ? $item->MenuTitle : $item->Title;
		$page->Content = $item->Body;
		$page->ParentID = $parent->ID;

		$page->DrupalID = $item->DrupalNodeID;
		$page->OriginalData = serialize($item->getRemoteProperties());
		$page->write();
/*
		if (isset($params['ImportMedia'])) {
			$this->importMedia($item, $page);
		}
*/
		return new TransformResult($page, $item->stageChildren());
	}

	public function setImporter($importer) {
		$this->importer = $importer;
	}
/*
	protected function importMedia($item, $page) {
		$source  = $item->getSource();
		$params  = $this->importer->getParams();
		$folder  = $params['AssetsPath'];
		$content = $item->Content;

		if ($folder) $folderId = Folder::findOrMake($folder)->ID;

		$url = trim(preg_replace('~^[a-z]+://~', null, $source->BaseUrl), '/');
		$pattern = sprintf(
			'~[a-z]+://%s/wp-content/uploads/[^"]+~', $url
		);

		if (!preg_match_all($pattern, $page->Content, $matches)) return;

		foreach ($matches[0] as $match) {
			if (!$contents = @file_get_contents($match)) continue;

			$name = basename($match);
			$path = Controller::join_links(ASSETS_PATH, $folder, $name);
			$link = Controller::join_links(ASSETS_DIR, $folder, $name);

			file_put_contents($path, $contents);
			$page->Content = str_replace($match, $link, $page->Content);
		}

		Filesystem::sync($folderId);
		$page->write();
	}
*/
}