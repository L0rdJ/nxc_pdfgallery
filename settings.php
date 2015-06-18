<?php
/**
 * @package nxcPDFGallery
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    21 Jun 2010
 **/

class nxc_pdfgallerySettings extends nxcExtensionSettings
{
	public $defaultOrder = 15;
	public $dependencies = array( 'nxc_powercontent' );

	public function activate() {}

	public function deactivate() {}
}
?>
