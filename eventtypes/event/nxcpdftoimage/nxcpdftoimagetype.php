<?php
/**
 * @package nxcPDFGallery
 * @class   nxcPDFToImageType
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    21 Jun 2010
 **/

class nxcPDFToImageType extends eZWorkflowEventType
{
	const TYPE_ID = 'nxcpdftoimage';

	public function __construct() {
		$this->eZWorkflowEventType( self::TYPE_ID, 'Parse PDF to images' );
	}

	public function execute( $process, $event ) {
		eZDebug::createAccumulatorGroup( 'nxc_pdftoimage', 'NXC PDF to image' );

		$processParams = $process->attribute( 'parameter_list' );

		$object  = eZContentObject::fetch( $processParams['object_id'] );
		$dataMap = $object->attribute( 'data_map' );

		if( isset( $dataMap['file'] ) === false ) {
			return eZWorkflowType::STATUS_ACCEPTED;
		}

		$file = $dataMap['file']->content();
		if( $file instanceof eZBinaryFile === false
			|| $file->attribute( 'mime_type' ) != 'application/pdf'
	 	) {
	 		return eZWorkflowType::STATUS_ACCEPTED;
	 	}

		eZDebug::accumulatorStart( 'nxc_pdftoimage_parse', 'nxc_pdftoimage', 'Parsing PDF file' );

		$output = shell_exec( 'pdf2ps ' . $file->attribute( 'filepath' ) . ' /dev/null 2>&1' );
		if( strpos( $output, 'ERROR' ) !== false ) {
			return eZWorkflowType::STATUS_ACCEPTED;
		}

		exec( 'gs -q -dNODISPLAY -c "(' . $file->attribute( 'filepath' ) . ') (r) file runpdfbegin pdfpagecount = quit"', $output );
		$pagesCount = (int) $output[0];
		if( $pagesCount === 0 ) {
			return eZWorkflowType::STATUS_ACCEPTED;
		}

		$pc = new nxcPowerContent();
		$imageClass = eZContentClass::fetchByIdentifier( 'image' );

		$currentImages = eZContentObjectTreeNode::subTreeByNodeID(
			array(
				'ClassFilterType'  => 'include',
				'ClassFilterArray' => array( $imageClass->attribute( 'identifier' ) )
			),
			$object->attribute( 'main_node_id' )
		);
		foreach( $currentImages as $node ) {
			$pc->removeObject( $node->attribute( 'object' ) );
		}

		for( $i = 0; $i < $pagesCount; ++$i ) {
			$pageFilepath = 'var/cache/page_' . $i . '.jpg';

			$im = new Imagick( $file->attribute( 'filepath' ) . '[' .  $i . ']' );
			$im->setCompression( Imagick::COMPRESSION_JPEG );
			$im->setCompressionQuality( 100 );
			$im->setImageFormat( 'jpeg' );
			$im->writeImage( $pageFilepath );

			$pc->createObject(
				array(
					'parentNode' => $object->attribute( 'main_node' ),
					'class'      => $imageClass,
					'attributes' => array(
						'name'  => 'Page ' . ( $i + 1 ),
						'image' => $pageFilepath
					)
				)
			);
			@unlink( $pageFilepath );
		}

		eZDebug::accumulatorStop( 'nxc_pdftoimage_parse' );

		return eZWorkflowType::STATUS_ACCEPTED;
	}
}

eZWorkflowEventType::registerEventType( nxcPDFToImageType::TYPE_ID, 'nxcPDFToImageType' );
?>
