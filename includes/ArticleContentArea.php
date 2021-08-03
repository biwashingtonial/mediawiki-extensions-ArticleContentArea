<?php

namespace MediaWiki\Extension\ArticleContentArea;

use Category;
use Title;

class ArticleContentArea {

	static $validContentAreas;

	/**
	 * Get SELECT fields and joins for retrieving the content area
	 *
	 * @return array[] With three keys:
	 *   - tables: (string[]) to include in the `$table` to `IDatabase->select()`
	 *   - fields: (string[]) to include in the `$vars` to `IDatabase->select()`
	 *   - join_conds: (array) to include in the `$join_conds` to `IDatabase->select()`
	 *  All tables, fields, and joins are aliased, so `+` is safe to use.
	 */
	public static function getJoin( $contentArea ) {
		$dbr = wfGetDB( DB_REPLICA );

		$joinType  = $contentArea ? 'INNER JOIN' : 'LEFT OUTER JOIN';
		$joinConds = [ 'page_id = content_area_page_props.pp_page', "content_area_page_props.pp_propname = 'ArticleContentArea'" ];
		if ( $contentArea ) {
			$joinConds[] = 'content_area_page_props.pp_value = ' . $dbr->addQuotes( $contentArea );
		}

		$tables['content_area_page_props'] = 'page_props';
		$joins['content_area_page_props'] = [ $joinType, $joinConds ];

		// Changing the field's alias MUST be marked as a breaking change
		$fields['content_area'] = 'content_area_page_props.pp_value';

		return [
			'tables' => $tables,
			'fields' => $fields,
			'join_conds' => $joins
		];
	}

	/**
	 * @return array
	 */
	public static function getValidContentAreas(): array {
		global $wgArticleContentAreaCategoryName;

		if ( isset( self::$validContentAreas ) ) {
			return self::$validContentAreas;
		}

		if ( $wgArticleContentAreaCategoryName === null ) {
			self::$validContentAreas = [];
		} else {
			$category = Category::newFromName( $wgArticleContentAreaCategoryName );
			$members  = iterator_to_array( $category->getMembers() );
			array_walk($members, function ( Title &$t ) {
				$t = $t->getText();
			} );

			self::$validContentAreas = $members;
		}

		return self::$validContentAreas;
	}

	/**
	 * @param string $contentArea
	 *
	 * @return bool
	 */
	public static function isValidContentArea( $contentArea ) {
		global $wgArticleContentAreaCategoryName;

		if ( $wgArticleContentAreaCategoryName === null ) {
			return true;
		}

		return in_array( $contentArea, self::getValidContentAreas() );
	}
}