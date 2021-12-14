#!/usr/bin/env php
<?php

//########################################################################################################
// Disables the DATABASE_URL env variable in `.env` when its default value is used to prevent confusion.
//########################################################################################################

// read the entire string
$str = file_get_contents('.env');

$pattern = '/^(DATABASE_URL=\"postgresql:\/\/symfony:ChangeMe@127.0.0.1:5432\/app)/im';
$replacement = '# $1';

$str = preg_replace($pattern, $replacement, $str);

// write the entire string
file_put_contents('.env', $str);
