<?php

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WP_Mock;
use function Patchwork\restoreAll;

class Unit_Testcase extends Unit {

	protected LoggerInterface $logger;

	protected function setup(): void {
		WP_Mock::setUp();

		$this->logger = new ColorLogger();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		WP_Mock::tearDown();
		restoreAll();
	}
}
