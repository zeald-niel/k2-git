<?php
/**
 * Class Tribe__Tickets_Plus__Service_Provider
 *
 * Provides the Events Tickets Plus service.
 *
 * This class should handle implementation binding, builder functions and hooking for any first-level hook and be
 * devoid of business logic.
 *
 * @since 4.6
 */
class Tribe__Tickets__Service_Provider extends tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.6
	 */
	public function register() {
		$this->container->singleton( 'tickets.assets', new Tribe__Tickets__Assets() );
		$this->container->singleton( 'tickets.handler', 'Tribe__Tickets__Tickets_Handler' );
		$this->container->singleton( 'tickets.attendees', 'Tribe__Tickets__Attendees', array( 'hook' ) );
		$this->container->singleton( 'tickets.version', 'Tribe__Tickets__Version', array( 'hook' ) );
		$this->container->singleton( 'tickets.metabox', 'Tribe__Tickets__Metabox', array( 'hook' ) );

		// Caching
		$this->container->singleton( 'tickets.cache-central', 'Tribe__Tickets__Cache__Central', array( 'hook' ) );
		$this->container->singleton( 'tickets.cache', tribe( 'tickets.cache-central' )->get_cache() );

		// Query Vars
		$this->container->singleton( 'tickets.query', 'Tribe__Tickets__Query', array( 'hook' ) );

		// Tribe Data API Init
		$this->container->singleton( 'tickets.data_api', 'Tribe__Tickets__Data_API' );

		// View links, columns and screen options
		$this->container->singleton( 'tickets.admin.views', 'Tribe__Tickets__Admin__Views', array( 'hook' ) );
		$this->container->singleton( 'tickets.admin.columns', 'Tribe__Tickets__Admin__Columns', array( 'hook' ) );
		$this->container->singleton( 'tickets.admin.screen-options', 'Tribe__Tickets__Admin__Screen_Options', array( 'hook' ) );

		$this->load();
	}

	/**
	 * Any hooking for any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since 4.6
	 */
	protected function load() {
		tribe( 'tickets.query' );
		tribe( 'tickets.handler' );
		tribe( 'tickets.attendees' );
		tribe( 'tickets.version' );
		tribe( 'tickets.metabox' );

		if ( is_admin() ) {
			tribe( 'tickets.admin.views' );
			tribe( 'tickets.admin.columns' );
			tribe( 'tickets.admin.screen-options' );
		}
	}

	/**
	 * Binds and sets up implementations at boot time.
	 *
	 * @since 4.6
	 */
	public function boot() {
		// no ops
	}
}
