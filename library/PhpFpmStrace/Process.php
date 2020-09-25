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
	}

	public function executes(Syscall $c): void
	{
		if ($c instanceof Syscall\Opener)
		{
			foreach (Analyzer::getObservers() as $class)
			{
				foreach ($c->spawns() as $id)
				{
					$this->_open[$id] = $c;

					/** @var Operation\Observer $class */
					$observer = $class::register(clone $c);

					if (isset($observer))
						$this->_observers[$id] = $observer;
				}
			}
		}
		elseif ($c instanceof Syscall\Closer)
		{
			$id = $c->closes();

			if (!array_key_exists($id, $this->_open))
				throw new Exception('Closer %s was not opened by any of [%s]', [$c, implode(', ', $this->_open)]);

			$this->_open[$id]->closedBy(clone $c);
			if (array_key_exists($id, $this->_observers))
				$this->_observers[$id]->unregister(clone $c);

			unset($this->_open[$id], $this->_observers[$id]);
		}
		elseif ($c instanceof Syscall\Operator)
		{
			foreach ($c->getDescriptors() as $id)
				if (array_key_exists($id, $this->_observers))
					foreach ($this->_observers[$id]->observe(clone $c) as $msg)
						print $msg ."\n";
		}

		$this->_calls []= $c;
	}
}