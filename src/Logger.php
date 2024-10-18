<?php

/*
 Usage

 1. Create service in addon.setup.php

    'services.singletons' => [
        'Logger' => function () {
            ee()->load->library('logger');
            return new Litzinger\Basee\Logger(
                logger: ee()->logger,
                enabled: bool_config_item('myaddon_enable_logging'),
            );
        },
    ]

 2. Call service in your add-on

    ee('myaddon:Logger')->developer('Message here');

 */

namespace Litzinger\Basee;

class Logger
{
    public function __construct(
        private $logger,
        private bool $enabled = false,
    ){
    }

    public function __call($name, $arguments)
    {
        if ($this->enabled === false) {
            return;
        }

        if (!method_exists($this->logger, $name)) {
            return;
        }

        call_user_func_array([$this->logger, $name], $arguments);
    }
}
