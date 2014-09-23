<?php
namespace Etiquettes\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Tools\URL;
use Thelia\Core\Event\PdfEvent;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Template\TemplateHelper;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

/**
 * Class GenerateController
 * @package Etiquettes\Controller
 * @author HubChannel <mlemarchand@hubchannel.fr>
 */
class GenerateController extends BaseAdminController
{

    public function indexAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, ['etiquettes'], AccessManager::UPDATE)) {
            return $response;
        }

        $order_ids = (array)explode(',', $this->getRequest()->get('cmd'));
        
        $html = $this->renderRaw(
            'etiquettes',
            array(
                'order_id' => implode(',', $order_ids)
            ),
            TemplateHelper::getInstance()->getActivePdfTemplate()
        );

        try {
            $pdfEvent = new PdfEvent($html);
            $pdfEvent->setOrientation('L');
            $pdfEvent->setFormat(array(36,89));
            $pdfEvent->setMarges(array(2, 2, 2, 2));
            
            $this->dispatch(TheliaEvents::GENERATE_PDF, $pdfEvent);

            if ($pdfEvent->hasPdf()) {
                
                /*$orders = OrderQuery::create()->findPks($order_ids);  
                $status = OrderStatusQuery::create()->findOneByCode(OrderStatus::CODE_PROCESSING);
                
                foreach($orders as $order) {
                    $event = new OrderEvent($order);
                    $event->setStatus($status->getId());
                    
                    $this->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
                }*/
                
                return $this->pdfResponse($pdfEvent->getPdf(), 'etiquettes');
            }

        } catch (\Exception $e) {
            die($e->getMessage());
            \Thelia\Log\Tlog::getInstance()->error(sprintf('error during generating invoice pdf for order id(s) : %d with message "%s"', implode(',', $order_ids), $e->getMessage()));

        }
    }
} 