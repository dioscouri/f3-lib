<?php

namespace Dsc\Event;

/**
 * Interface to be implemented by classes depending on a dispatcher.
 *
 * @since  1.0
 */
interface DispatcherAwareInterface
{
	/**
	 * Set the dispatcher to use.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher to use.
	 *
	 * @return  DispatcherAwareInterface  This method is chainable.
	 *
	 * @since   1.0
	 */
	public function setDispatcher(DispatcherInterface $dispatcher);
}
