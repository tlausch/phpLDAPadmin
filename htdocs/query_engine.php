<?php
/**
 * Query render engine.
 *
 * @package phpLDAPadmin
 * @subpackage Page
 * @author The phpLDAPadmin development team
 */

/**
 */

require_once './common.php';
require_once LIBDIR.'query_functions.php';

$request = array();
$request['page'] = new QueryRender($app['server']->getIndex(),get_request('query','REQUEST',false,null));
$request['page']->accept();
?>
