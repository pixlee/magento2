<?php
namespace Pixlee\Pixlee\Cron;

class ExportCron {
    protected $logger;

    public function __construct(
      \Pixlee\Pixlee\Helper\Logger\PixleeLogger $logger,
      \Pixlee\Pixlee\Helper\Data $pixleeData
    ) {
        $this->_logger = $logger;
        $this->_pixleeData = $pixleeData;
    }

   /**
    * Export products
    *
    * @return void
    */
    public function execute() {
        $this->_logger->info('Exporting products from Cron Job');
        $this->_pixleeData->exportProducts(0);
    }
}
