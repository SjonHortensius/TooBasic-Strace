<?php namespace PhpFpmStrace;

class Process
{
	protected int $_id;
	/** @var Syscall[] $_calls */
	protected array $_calls;
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

	public function summary()
	{
		foreach ($this->_observers as $observer)
		{
			echo '** '. get_class($observer) .' **'."\n";
			print_r($observer->summary());
		}
	}
}