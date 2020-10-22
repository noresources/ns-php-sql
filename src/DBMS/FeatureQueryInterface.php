<?php
namespace NoreSources\SQL\DBMS;

/**
 * Interface to query DBMS supported features
 */
interface FeatureQueryInterface
{

	/**
	 * Query feature support
	 *
	 * @param mixed $query
	 * @param mixed $dflt
	 *        	Default value if $query is not found. If NULL, let the Platform choose the most
	 *        	meaningful default value
	 */
	function queryFeature($query, $dflt = null);
}
