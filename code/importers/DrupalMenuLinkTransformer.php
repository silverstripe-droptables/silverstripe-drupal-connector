<?php
/**
 * Transforms a remote Drupal menu link into a local {@link DrupalMenuLink} instance.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalMenuLinkTransformer extends DrupalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$params = $this->importer->getParams();

		$pageType = 'DrupalMenuLink';
		$drupalPageType = true;

		// Set the page type if requested.
		if (isset($params['PageType']) && class_exists($params['PageType'])) {
			$pageType = $params['PageType'];
			$drupalPageType = false;
		}
		$page = new $pageType();

		// Get the URL segment to avoid duplicates
		$URLSegment = '';
		if ($page->PathAlias) {
			$pathSegments = explode('/', $page->PathAlias);
			if ($pathSegments) {
				$URLSegment = $pathSegments[count($pathSegments) - 1];
			}
		}

		if ($drupalPageType) {
			$existingPage = DataObject::get_one('DrupalMenuLink', sprintf(
				'"DrupalID" = %d AND "ParentID" = %d', $item->DrupalID, $parent->ID
			));
		} else {
			$existingPage = DataObject::get_one($pageType, sprintf(
				'"URLSegment" = \'%s\' AND "ParentID" = %d', mysql_real_escape_string($URLSegment), $parent->ID
			));
		}

		if ($existingPage) switch ($strategy) {
			case ExternalContentTransformer::DS_OVERWRITE:
				$page = $existingPage;
				break;
			case ExternalContentTransformer::DS_DUPLICATE:
				break;
			case ExternalContentTransformer::DS_SKIP:
				return;
		}

		$page->Title = $item->Title;
		$page->MenuTitle = $item->MenuTitle;
		if ($URLSegment) {
			$page->URLSegment = $URLSegment;
		}
		$page->ParentID = $parent->ID;

		if ($drupalPageType) {
			$page->DrupalID = $item->DrupalID;
			$page->OriginalData = serialize($item->getRemoteProperties());
		}

		// Write the attached node, if any exists.
		if ($item->Node) {
			$page->Content = $item->Node->Body;
			$this->ImportTags($item->Node, $page);
		}

		$page->write();

		$this->importMedia($item, $page);
		if ($item->Node) {
			$this->importAttachments($item->Node, $page);
		}

		if ($params['PublishOnImport']) {
			$page->doPublish();
		}

		if ($params['ImportPublishDates'] && $item->Node) {
			// Raw SQL writes to update the tables directly to avoid setting the last updated field.
			DB::query("UPDATE SiteTree SET Created='" . $item->Node->CreatedAt . "', LastEdited='" . $item->Node->ChangedAt . "' WHERE ID=$page->ID")->value();
			DB::query("UPDATE SiteTree_Live SET Created='" . $item->Node->CreatedAt . "', LastEdited='" . $item->Node->ChangedAt . "' WHERE ID=$page->ID")->value();
			DB::query("UPDATE SiteTree_Versions SET Created='" . $item->Node->CreatedAt . "', LastEdited='" . $item->Node->ChangedAt . "' WHERE RecordID=$page->ID")->value();
		}

		return new TransformResult($page, $item->stageChildren(), $item);
	}

	public function setImporter($importer) {
		$this->importer = $importer;
	}
}