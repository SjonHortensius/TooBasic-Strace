<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Interval;
use PhpFpmStrace\Syscall;

class MemcacheQuery implements Observer
{
	protected array $_fds = [];
	protected int $_hits = 0;
	protected Interval $_spent;
	private Syscall\Sendto $_lastRequest;

	public function __construct()
	{
		$this->_spent = new Interval;
	}

	public function executes(Syscall $c): \Generator
	{
		$msg = sprintf('[%s] %s: %%s', $c->getTimestamp()->format("H:m:s.u"), __CLASS__);

		if ($c instanceof Syscall\Connect && 11211 === $c->port)
		{
			$host = $c->getArgument(1)["sin_addr"] ?? explode('"', $c->getArgument(1)[0])[1];
			yield sprintf($msg, 'connecting to '.$host);
			$this->_fds[ intval($c->getArgument(0)) ] = true;
		}
		elseif (!is_numeric($c->getArgument(0)) || !isset($this->_fds[ intval($c->getArgument(0)) ]))
			return;

		if ($c instanceof Syscall\Sendto)
		{
			$this->_lastRequest = $c;
			$this->_hits++;

			//FIXME properly implement protocol
			$p = explode('\\0', $c->getArgument(1));
			$key = array_pop($p);
			yield sprintf($msg, 'requesting '.$key);
		}
		elseif ($c instanceof Syscall\Recvfrom)
		{
			$this->_spent->add(Interval::fromDiff($c->getTimestamp(), $this->_lastRequest->getTimestamp()));

			//FIXME properly implement protocol
			$p = explode('\\0', $c->getArgument(1));

			if (count($p) > 2)
				yield sprintf($msg, 'received '. strlen(array_pop($p)) .' bytes');
		}
		elseif ($c instanceof Syscall\Close)
			unset($this->_fds[ $c->getArgument(0) ]);
	}

	public function reset(): void
	{
		$this->_fds = [];
	}
}