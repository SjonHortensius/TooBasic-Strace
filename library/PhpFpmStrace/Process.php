<?php namespace PhpFpmStrace;
use PhpFpmStrace\Syscall\Opener;

class Process
{
	protected int $_id;
	protected array $_calls;
	protected array $_open;
	protected $_listeners = [];

	public function __construct(int $id)
	{
		$this->_id = $id;

		$this->_listeners = [
			new Operation\MemcacheQuery,
			new Operation\MysqlQuery,
		];
	}

	public function executes(Syscall $c): void
	{
		if ($c instanceof Syscall\Opener)
			foreach ($c->spawns() as $id)
				$this->_open[$id] = $c;
		elseif ($c instanceof Syscall\Closer)
		{
			if (!array_key_exists($c->closes(), $this->_open))
				throw new Exception('Closer %s was not opened by any of [%s]', [$c, implode(', ', $this->_open)]);

			$this->_open[ $c->closes() ]->closedBy($c);
			unset($this->_open[ $c->closes() ]);
		}

		$this->_calls []= $c;

		foreach ($this->_listeners as $l)
			foreach ($l->executes($c) as $msg)
				print $msg."\n";
	}
}