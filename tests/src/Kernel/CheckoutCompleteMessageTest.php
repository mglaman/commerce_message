<?php

namespace Drupal\Tests\commerce_message\Kernel;

use Drupal\commerce_checkout\Event\CheckoutCompleteEvent;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\message\Entity\MessageType;
use Drupal\user\Entity\User;

/**
 * Tests the checkout complete event and email notifications.
 *
 * @group commerce_message
 */
class CheckoutCompleteMessageTest extends KernelTestBase {

  use AssertMailTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The order to test.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'field',
    'options',
    'user',
    'entity',
    'views',
    'address',
    'profile',
    'state_machine',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'commerce_checkout',
    'message',
    'message_notify',
    'commerce_message',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->eventDispatcher = $this->container->get('event_dispatcher');

    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('message');
    $this->installConfig([
      'commerce_order',
      'message',
      'message_notify',
      'commerce_message',
    ]);

    $this->container->get('config.factory')
      ->getEditable('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();
  }

  /**
   * Triggers checkout complete event, verifies notifications sent.
   */
  public function testCheckoutCompleteMessage() {
    $customer_message_type = MessageType::load('commerce_checkout_completed');
    $this->assertNotNull($customer_message_type);

    $admin_message_type = MessageType::load('commerce_checkout_admin_message');
    $this->assertNotNull($admin_message_type);

    User::create([
      'uid' => 1,
      'name' => $this->randomString(),
      'mail' => 'mail+1@example.org',
    ])
      ->enforceIsNew(TRUE)->save();
    $user = User::create([
      'uid' => 2,
      'name' => $this->randomString(),
      'mail' => 'mail+2@example.org',
    ]);
    $user->enforceIsNew(TRUE)->save();

    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'order_number' => '6',
      'mail' => 'test@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
    ]);

    $order->save();

    $this->assertNotNull($order->getOwner());

    $event = new CheckoutCompleteEvent($order);
    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher */
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch(CheckoutEvents::CHECKOUT_COMPLETE, $event);

    $emails = $this->getMails();

    // Once chained tokens working, test $emails[0]['subject'];
    // Two emails should have sent.
    $this->assertEquals(2, count($emails));
  }

}
