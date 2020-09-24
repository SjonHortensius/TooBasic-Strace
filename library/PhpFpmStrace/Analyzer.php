<?php namespace PhpFpmStrace;

class Analyzer
{
	public static function parse($h): \Generator
	{
		while (false !== ($line = fgets($h)))
		{
print $line;
			if (!preg_match('~^(?:\[pid (?P<pid>\d+)\] )?(?P<time>[0-9:.]+) (?P<call>[a-z0-9_]+)\((?P<args>.*)\)(?: += (?P<retn>[0x0-9a-f]+))?$~', rtrim($line, "\n"), $m))
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

		print_r($processes);
	}
}
