<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
$module_name="safedns-api-v1";
try {

    $script = "plesk bin extension --exec $module_name safedns.php";
    $result = pm_ApiCli::call('server_dns', array('--enable-custom-backend', $script));
} catch (pm_Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
exit(0);
