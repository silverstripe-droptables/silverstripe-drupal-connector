<?php
/**
 * @package silverstripe-drupal-connector
 */

DrupalImporter::add_extension('PostImportStepExtension');

set_include_path(dirname(__FILE__).'/thirdparty'.PATH_SEPARATOR.get_include_path());