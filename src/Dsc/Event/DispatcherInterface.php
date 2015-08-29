<?php

namespace Dsc\Event;

/**
 * Interface for event dispatchers.
 *
 * @since  1.0
 */
interface DispatcherInterface
{
	/**
	 * Trigger an event.
	 *
	 * @param   EventInterface|string  $event  The event object or name.
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 *
	 * @since   1.0
	 */
	public function triggerEvent($event);
}
