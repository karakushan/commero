<?php

namespace Commero\Payments;

use RuntimeException;

/**
 * The gateway refused or failed to start a payment; the message is shown to the customer.
 */
class PaymentException extends RuntimeException {}
