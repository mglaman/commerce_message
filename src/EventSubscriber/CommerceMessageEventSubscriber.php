<?php

namespace Drupal\commerce_message\EventSubscriber;

use Drupal\commerce_checkout\Event\CheckoutCompleteEvent;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\message_notify\MessageNotifier;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to events for Commerce Message.
 */
class CommerceMessageEventSubscriber implements EventSubscriberInterface {


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The message notifier.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * Creates a new CommerceMessageEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\message_notify\MessageNotifier $message_notify_sender
   *   The message notifier.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessageNotifier $message_notify_sender) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messageNotifier = $message_notify_sender;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CheckoutEvents::CHECKOUT_COMPLETE] = ['checkoutComplete'];

    return $events;
  }

  /**
   * Sends the checkout complete messages.
   *
   * @param \Drupal\commerce_checkout\Event\CheckoutCompleteEvent $event
   *   The checkout complete event.
   */
  public function checkoutComplete(CheckoutCompleteEvent $event) {
    $order = $event->getOrder();
    // @todo Make this configurable.
    $admin_user = $this->entityTypeManager->getStorage('user')->load(1);

    $customer_message = $this->createOrderMessage('commerce_checkout_completed', $order->getOwner(), $order);
    $admin_message = $this->createOrderMessage('commerce_checkout_admin_message', $admin_user, $order);
    if (!$this->messageNotifier->send($customer_message, [], 'email')) {
      throw new \Exception('Unable to send customer email');
    }
    if (!$this->messageNotifier->send($admin_message, [], 'email')) {
      throw new \Exception('Unable to send admin email');
    }
  }

  /**
   * Creates a message which references an order.
   *
   * @param string $type
   *   The message type.
   * @param \Drupal\user\UserInterface $owner
   *   The message owner.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\message\MessageInterface
   *   The message entity.
   */
  protected function createOrderMessage($type, UserInterface $owner, OrderInterface $order) {
    $message_storage = $this->entityTypeManager->getStorage('message');

    return $message_storage->create([
      'type' => $type,
      'uid' => $owner->id(),
      'field_order' => $order,
    ]);
  }

}
