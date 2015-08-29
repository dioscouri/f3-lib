<?php

namespace Dsc\Event;

/**
 * Interface for events.
 * An event has a name and its propagation can be stopped (if the implementation supports it).
 *
 * @since  1.0
 */
interface EventInterface
{
	/**
	 * Get the event name.
	 *
	 * @return  string  The event name.
	 *
	 * @since   1.0
	 */
	public function getName();

	/**
	 * Tell if the event propagation is stopped.
	 *
	 * @return  boolean  True if stopped, false otherwise.
	 *
	 * @since   1.0
	 */
	public function isStopped();
}
