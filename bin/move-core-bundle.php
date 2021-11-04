#!/usr/bin/env php
<?php

###############################################################################################
# Moves the DbpRelayCoreBundle to bottom of the array in `config/bundles.php`.
###############################################################################################

// read the entire string
$str = file_get_contents('config/bundles.php');
$coreBundleString = "    Dbp\Relay\CoreBundle\DbpRelayCoreBundle::class => ['all' => true],";

$str = str_replace($coreBundleString . "\n", '', $str);
$str = str_replace('];', $coreBundleString . "\n];", $str);

// write the entire string
file_put_contents('config/bundles.php', $str);
