<?php namespace PhpFpmStrace;

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
			$this->_open []= $c;
		elseif ($c instanceof Syscall\Closer)
		{
			foreach ($this->_open as $idx => $o)
				if ($o->closedBy($c))
				{
					unset($this->_open[$idx]);
					$this->_calls []= $o;
					return;
				} else
					echo $o ." did not open {$c->closes()}\n";

			throw new Exception('Closer %s was not opened by any of [%s]', [$c, implode(', ', $this->_open)]);
		} else
			$this->_calls []= $c;
	}
}

