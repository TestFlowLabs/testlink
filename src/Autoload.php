<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink;

use TestFlowLabs\TestLink\Runtime\TestLinkTrait;

// Register the trait for Pest tests (if Pest is available)
if (class_exists(\Pest\Plugin::class)) {
    \Pest\Plugin::uses(TestLinkTrait::class);
}
