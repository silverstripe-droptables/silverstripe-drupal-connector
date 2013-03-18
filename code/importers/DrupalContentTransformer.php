<?php
/**
 * Base class for all ExternalContentTransformers that contains a few common functions.
 *
 * @package silverstripe-drupal-connector
 */
abstract class DrupalContentTransformer implements ExternalContentTransformer {

	protected function importMedia($item, $page) {
		$source = $item->getSource();
		$params = $this->importer->getParams();
		$folder = $params['AssetsPath'];
		$baseURL = $params['BaseUrl'];

		if ($folder) $folderId = Folder::find_or_make($folder)->ID;

		// Find the src attribute of every img element.
		$pattern = '/<img [^>]*src="([^"]*)/';

		if (!preg_match_all($pattern, $page->Content, $matches)) return;

		// Loop through all the matches to ([^"]*), which captures the filename
		foreach ($matches[1] as $match) {
			$imageURL = $match;

			// Don't pull in absolute links unless they're on the same location. Note that this will skip over any
			// files served from a subdomain like static.mysite.com.
			if (substr($match, 0, strlen($baseURL)) != $baseURL) {
				if (strpos($match, '://') !== false) continue;

				$imageURL = Controller::join_links($baseURL, $match);
			}

			$name = basename($match);
			$path = Controller::join_links(ASSETS_PATH, $folder, $name);
			$link = Controller::join_links(ASSETS_DIR, $folder, $name);

			if (!file_exists($path)) {
				if (!$contents = @file_get_contents($imageURL)) continue;
				file_put_contents($path, $contents);
			}
			
			$page->Content = str_replace("\"$match\"", "\"$link\"", $page->Content);
		}

		Filesystem::sync($folderId);
		$page->write();
	}
}