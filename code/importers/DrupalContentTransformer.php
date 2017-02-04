<?php
/**
 * Base class for all ExternalContentTransformers that contains a few common functions.
 *
 * @package silverstripe-drupal-connector
 */
abstract class DrupalContentTransformer implements ExternalContentTransformer
{

    protected function importMedia($item, $page)
    {
        $source = $item->getSource();
        $params = $this->importer->getParams();
        $folder = $params['AssetsPath'];
        $baseURL = $params['BaseUrl'];

        if ($folder) {
            $folderId = Folder::find_or_make($folder)->ID;
        }

        // Find the src attribute of every img element.
        $pattern = '/<img [^>]*src="([^"]*)/';

        if (!preg_match_all($pattern, $page->Content, $matches)) {
            return;
        }

        // Loop through all the matches to ([^"]*), which captures the filename
        foreach ($matches[1] as $match) {
            $imageURL = $match;

            // Don't pull in absolute links unless they're on the same location. Note that this will skip over any
            // files served from a subdomain like static.mysite.com.
            if (substr($match, 0, strlen($baseURL)) != $baseURL) {
                if (strpos($match, '://') !== false) {
                    continue;
                }

                $imageURL = Controller::join_links($baseURL, $match);
            }

            $name = basename($match);
            $path = Controller::join_links(ASSETS_PATH, $folder, $name);
            $link = Controller::join_links(ASSETS_DIR, $folder, $name);

            if (!file_exists($path)) {
                if (!$contents = @file_get_contents($imageURL)) {
                    continue;
                }
                file_put_contents($path, $contents);
            }
            
            $page->Content = str_replace("\"$match\"", "\"$link\"", $page->Content);
        }

        Filesystem::sync($folderId);
        $page->write();
    }

    protected function importAttachments($item, $page)
    {
        $source = $item->getSource();
        $params = $this->importer->getParams();
        $relation = $params['FileRelation'];
        $folderPath = $params['AssetsPath'];
        $baseURL = $params['BaseUrl'];

        // Check that we should import the files and import them into the specified relation.
        if (!$relation || $page->getRelationClass($relation) != 'File') {
            return;
        }

        if ($folderPath) {
            $folder = Folder::find_or_make($folderPath);
        } else {
            $folder = Folder::get_one('Folder', "'ParentID' = 0");
        }

        $relationList = $page->$relation();
        foreach ($item->Files as $file) {
            $fileURL = $file['filepath'];

            // Append the site's URL if it's a relative URL.
            if (strpos($fileURL, '://') === false) {
                $fileURL = Controller::join_links($baseURL, $fileURL);
            }

            $fileName = basename($fileURL);
            $path = Controller::join_links(ASSETS_PATH, $folderPath, $fileName);

            if (file_exists($path)) {
                $SQLFilename = Controller::join_links(ASSETS_DIR, $folderPath, $fileName);
                $file = File::get_one('File', "Filename = '$SQLFilename'");
            } else {
                if (!$contents = file_get_contents(str_replace(' ', '%20', $fileURL))) {
                    continue;
                }
                file_put_contents($path, $contents);
                $fileID = $folder->constructChild($fileName);
                $file = File::get_by_id('File', $fileID);
            }

            $relationList->add($file);
        }
    }

    protected function importTags($item, $page)
    {
        $params = $this->importer->getParams();
        $relation = $params['TaxonomyRelation'];

        // Check that we should import the tags and import them into the specified relation.
        if (!$relation || $page->getRelationClass($relation) != 'TaxonomyTerm') {
            return;
        }

        if (class_exists('TaxonomyTerm')) {
            $relationList = $page->$relation();
            foreach ($item->Tags as $tag) {
                $terms = TaxonomyTerm::get('TaxonomyTerm')->Filter('Name', $tag['name']);
                if ($terms->exists()) {
                    $relationList->add($terms->first());
                }
            }
        }
    }
}
