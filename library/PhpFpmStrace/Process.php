<?php namespace PhpFpmStrace;

use PhpFpmStrace\Operation\Observer;

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
			$this->_observers[$class]= new $class;
	}

	public function executes(Syscall $c): void
	{
		foreach ($this->_observers as $class => $observer)
			foreach ($observer->observe(clone $c) as $level => $msg)
				switch ($level)
				{
					case Observer::LEVEL_INFO: printf("<i style='color:gray'>[%s] %s: %s</i>\n", $c->getTimestamp()->format("H:m:s.u"), $class, $msg); break;
					case Observer::LEVEL_CALL: printf("%s (took %s) %s: %s\n", $c->getTimestamp()->format("H:m:s.u"), $msg->took(), $class, (string)$msg); break;
				}

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