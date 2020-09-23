<?php
namespace PhpFpmStrace;

spl_autoload_register(function($class){
	if (0 !== strpos($class, 'PhpFpmStrace\\'))
		return false;

	$path = __DIR__ .'/library/'. str_replace('\\', '/', $class) .'.php';

	if (!file_exists($path))
		return false;

	return require_once($path);
});

print'<pre>';
Analyzer::run();