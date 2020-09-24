<?php namespace PhpFpmStrace;
use PhpFpmStrace\Syscall\Opener;

class Process
{
	protected int $_id;
	protected array $_calls;
	protected array $_open;

	public function __construct(int $id)
	{
		$this->_id = $id;
	}

	public function executes(Syscall $c): void
	{
		if ($c instanceof Syscall\Opener)
		{
			foreach ($c->spawns() as $id)
				$this->_open[$id] = $c;
		}
		elseif ($c instanceof Syscall\Closer)
		{
			if (!array_key_exists($c->closes(), $this->_open))
				throw new Exception('Closer %s was not opened by any of [%s]', [$c, implode(', ', $this->_open)]);

			unset($this->_open[ $c->closes() ]);
			/** @var Opener $o */
			foreach ($this->_open as $idx => $o)
				if ($o->closedBy($c))
				{
					unset($this->_open[$idx]);
				}


		}

		$this->_calls []= $c;
	}

	public function getChildren(): array
	{
		return $this->_childs;
	}
}

