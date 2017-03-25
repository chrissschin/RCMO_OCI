<?php
/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	May 26 2015
 * @link	mailto:kingzmeadow@hotmail.com
 *
 * Entry point for the Online Check-In web application
 *
 * Require include's all necessary application files and
 * calls/invokes the entry point function of the online check-in process
 */

## REQUIRE _APP ABSTRACT CODE FILES  ####################################
require_once(__DIR__.'/../_app/_app.abstract');
require_once(__DIR__.'/../_app/_base.abstract');
require_once(__DIR__.'/../_app/_ctrlr.abstract');

## REQUIRE and INVOKE ONLINE CHECK-IN MAIN CODE  ########################
require_once(__DIR__.'/../ctrlr/ctrlr.online_check_in.main.php');
ctrlr_online_check_in_pub_main($_app_env);
?>
