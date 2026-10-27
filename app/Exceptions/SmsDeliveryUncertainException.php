<?php

namespace App\Exceptions;

use RuntimeException;

/** The provider may have accepted the SMS. Sending it again is unsafe. */
class SmsDeliveryUncertainException extends RuntimeException {}
