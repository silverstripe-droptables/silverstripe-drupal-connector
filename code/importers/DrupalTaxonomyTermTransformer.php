<?php
/**
 * Transforms a remote Drupal taxonomy term into a local {@link DrupalTaxonomyTerm} instance.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalTaxonomyTermTransformer extends DrupalContentTransformer
{

    protected $importer;

    public function transform($item, $parent, $strategy)
    {
        echo get_class($parent);
        if (is_a($parent, 'TaxonomyTerm')) {
            // If a child term of $parent doesn't already exists with this name, create it.
            $taxonomyTerms = TaxonomyTerm::get()->filter('Name', $item->Name)->filter('ParentID', $parent->ID);
            if ($taxonomyTerms->exists()) {
                $taxonomyTerm = $taxonomyTerms->first();
            } else {
                $taxonomyTerm = new TaxonomyTerm();
                $taxonomyTerm->Name = $item->Name;
                $taxonomyTerm->ParentID = $parent->ID;
                $taxonomyTerm->write();

                $parent->Children()->Add($taxonomyTerm);
            }

            return new TransformResult($taxonomyTerm, $item->stageChildren()->filter('ClassName', 'DrupalTaxonomyTermContentItem'), $item);
        } else {
            $page = new DrupalTaxonomyTerm();
            $params = $this->importer->getParams();

            $existingPage = DataObject::get_one('DrupalTaxonomyTerm', sprintf(
                '"DrupalID" = %d AND "ParentID" = %d', $item->DrupalID, $parent->ID
            ));

            if ($existingPage) {
                switch ($strategy) {
                case ExternalContentTransformer::DS_OVERWRITE:
                    $page = $existingPage;
                    break;
                case ExternalContentTransformer::DS_DUPLICATE:
                    break;
                case ExternalContentTransformer::DS_SKIP:
                    return;
            }
            }

            $page->Title = $item->Title;
            $page->MenuTitle = $item->Title;
            $page->ParentID = $parent->ID;

            $page->DrupalID = $item->DrupalID;
            $page->OriginalData = serialize($item->getRemoteProperties());
            $page->write();

            $this->importMedia($item, $page);

            return new TransformResult($page, $item->stageChildren(), $item);
        }
    }

    public function setImporter($importer)
    {
        $this->importer = $importer;
    }
}
