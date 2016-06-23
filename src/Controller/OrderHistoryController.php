<?php

namespace Drupal\commerce_message\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides the order history route controller.
 */
class OrderHistoryController extends ControllerBase {

  /**
   * Returns the page with the order history messages.
   */
  public function getPage(OrderInterface $order) {
    return [];
  }

}
