<?php namespace PhpFpmStrace;

use PhpFpmStrace\Operation\Observer;

class Analyzer
{
	public static array $observers = [];

	public static function parse($h): \Generator
	{
		while (false !== ($line = fgets($h)))
		{
			if (!preg_match('~^(?:\[pid (?P<pid>\d+)\] )?(?P<time>[0-9:.]+) (?P<call>[a-z0-9_]+)\((?P<args>.*?)\)(?: += (?P<retn>-?[x0-9a-f]+(?: [^"]*?)?))?$~', rtrim($line, "\n"), $m))
				throw new Exception('Could not parse line: '. $line);

			yield intval($m['pid']) => Syscall::fromCall($m['time'], $m['call'], $m['args'], $m['retn']??"");
		}
	}

	public static function run()
	{
		$processes = [];

		foreach (self::parse(fopen('../fpm-strace-stats.in', 'r')) as $pid => $c)
		{
			if (!isset($processes[$pid]))
				$processes[$pid] = new Process($pid);

			$processes[$pid]->executes($c);
		}

		foreach ($processes as $process)
			$process->summary();
	}

	public static function getObservers(): array
	{
		if (!empty(self::$observers))
			return self::$observers;

		foreach (glob(__DIR__ .'/Operation/*') as $path)
			require_once($path);

		foreach (get_declared_classes() as $class)
			if ((new \ReflectionClass($class))->implementsInterface(Observer::class))
				self::$observers []= $class;

		return self::$observers;
	}
}