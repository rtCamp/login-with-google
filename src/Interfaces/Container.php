<?php
/**
 * Container Interface.
 *
 * @package WpGuruDev\OrderExport
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Interfaces;

/**
 * Interface Container
 *
 * @package WpGuruDev\OrderExport\Interfaces
 */
interface Container {
	/**
	 * Define services in container.
	 *
	 * @return void
	 */
	public function define_services(): void;
}
