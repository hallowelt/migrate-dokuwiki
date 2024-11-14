<?php

namespace HalloWelt\MigrateDokuwiki\Composer;

use HalloWelt\MediaWiki\Lib\MediaWikiXML\Builder;
use HalloWelt\MediaWiki\Lib\Migration\ComposerBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Output\Output;

class DokuwikiComposer extends ComposerBase implements IOutputAwareInterface {

	/**
	 * @var DataBuckets
	 */
	private $dataBuckets;

	/**
	 * @var Output
	 */
	private $output = null;

	/**
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 */
	public function __construct( $config, Workspace $workspace, DataBuckets $buckets ) {
		parent::__construct( $config, $workspace, $buckets );

		$this->dataBuckets = new DataBuckets( [
			'page-titles',
			'page-id-to-title-map',
			'page-id-to-page-contents',
			'page-id-to-attic-page-id',
			'page-id-to-attic-page-contents',
			'page-meta-map',
			'page-changes-map'
		] );

		$this->dataBuckets->loadFromWorkspace( $this->workspace );
	}

	/**
	 * @param Output $output
	 */
	public function setOutput( Output $output ) {
		$this->output = $output;
	}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	public function buildXML( Builder $builder ) {
		$this->output->writeln( 'Appending default pages' );
		$this->appendDefaultPages( $builder );
		$this->output->writeln( 'Adding pages' );
		$this->addPages( $builder );
		$this->output->writeln( 'Adding default files' );
		$this->addDefaultFiles();
	}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	private function addPages( Builder $builder ) {
		$pageTitles = $this->dataBuckets->getBucketData( 'page-titles' );
		$pageIdToTitleMap = $this->dataBuckets->getBucketData( 'page-id-to-title-map' );
		$pageIdToContentsMap = $this->dataBuckets->getBucketData( 'page-id-to-page-contents' );
		$pageIdToHistoryPageIdMap = $this->dataBuckets->getBucketData( 'page-id-to-attic-page-id' );

		$titleToPageIdMap = [];
		foreach ( $pageIdToTitleMap as $id => $titles ) {
			$title = $titles[0];
			$titleToPageIdMap[$title] = $id;
		}

		foreach ( $pageTitles['pages_titles'] as $pageTitle ) {
			if ( !isset( $titleToPageIdMap[$pageTitle] ) ) {
				continue;
			}
			$pageId = $titleToPageIdMap[$pageTitle];

			if ( !isset( $pageIdToContentsMap[$pageId] ) ) {
				continue;
			}

			$wikiText = $this->workspace->getConvertedContent( $pageId );

			$this->output->writeln( "Add latest revison of $pageTitle" );
			$builder->addRevision( $pageTitle, $wikiText );

			if ( !isset( $pageIdToHistoryPageIdMap[$pageId] ) ) {
				continue;
			}

			$this->output->writeln( "Add latest history revison of $pageTitle" );

			// not for each attic page does a change file exist for some reason
			#$pageChanges = $this->workspace->loadData( $pageId, 'content/history/changes' );

			$historyVersions = $pageIdToHistoryPageIdMap[$pageId];
			foreach ( $historyVersions as $historyVersion ) {
				$wikiText = $this->workspace->getConvertedContent( $historyVersion );
				// unitx timestamp
				$timestamp = str_replace( "$pageId.", '', $historyVersion );
				$timestamp = str_replace( ".wiki", '', $timestamp );

				$dateTime = date( 'Y-m-d H:i:s', $timestamp );
				$this->output->writeln( "\t- $dateTime" );
				$mwTimestamp = date( 'YmdHis', $timestamp );

				$user = '';
				$comment = '';

				/*
				if ( !isset( $pageChanges[$timestamp] ) ) {
					continue;
				}
				$object = $pageChanges[$timestamp];

				#$ip = $object['ip'];
				#$flag = $object['flag'];
				#$pageTitle = $object['title'];
				$user = $object['user'];
				$comment = $object['comment'];
				*/

				// do not handle attic versions with .change information

				$builder->addRevision( $pageTitle, $wikiText, $mwTimestamp, $user, '', '', $comment );
			}

		}
	}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	private function appendDefaultPages( Builder $builder ) {
		$basepath = __DIR__ . '/_defaultpages/';
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $basepath ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $fileObj ) {
			if ( $fileObj->isDir() ) {
				continue;
			}
			$file = $fileObj->getPathname();
			$namespacePrefix = basename( dirname( $file ) );
			$pageName = basename( $file );
			$wikiPageName = "$namespacePrefix:$pageName";
			$wikiText = file_get_contents( $file );

			$builder->addRevision( $wikiPageName, $wikiText );
		}
	}

	/**
	 * @return void
	 */
	private function addDefaultFiles() {
		$basepath = __DIR__ . '/_defaultfiles/';
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $basepath ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $fileObj ) {
			if ( $fileObj->isDir() ) {
				continue;
			}
			$file = $fileObj->getPathname();
			$fileName = basename( $file );
			$data = file_get_contents( $file );

			$this->workspace->saveUploadFile( $fileName, $data );
		}
	}

}
