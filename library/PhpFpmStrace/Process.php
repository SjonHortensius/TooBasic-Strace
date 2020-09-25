<?php namespace PhpFpmStrace;

class Process
{
	protected int $_id;
	/** @var Syscall[] $_calls */
	protected array $_calls;
	/** @var Syscall\Opener[] $_open */
	protected array $_open;
	/** @var Operation\Observer[] $_observers */
	protected array $_observers = [];

	public function __construct(int $id)
	{
		$this->_id = $id;

		foreach (Analyzer::getObservers() as $class)
			$this->_observers []= new $class;
	}

	public function executes(Syscall $c): void
	{
		foreach ($this->_observers as $observer)
			foreach ($observer->observe(clone $c) as $msg)
				print $msg ."\n";

		$this->_calls []= $c;
	}
}