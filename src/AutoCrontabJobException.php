<?php

namespace Battis;

/**
 * All exceptions thrown by AutoCrontabJob
 *
 * @author Seth Battis <seth@battis.net>
 **/
class AutoCrontabJobException extends \Exception
{

    /** Error constructing CanvasDataCollector */
    const CONSTRUCTOR_ERROR = 1;
}
