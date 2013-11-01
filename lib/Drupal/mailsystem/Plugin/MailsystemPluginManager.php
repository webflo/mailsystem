<?php
/**
* @file
* Contains Drupal\mailsystem\Plugin\MailsystemPluginManager.
*/

namespace Drupal\mailsystem\Plugin;

use Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator;
use Drupal\Component\Utility\String;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for Mailsystem plugins.
 * @package Drupal\mailsystem\Plugin
 */
class MailsystemPluginManager extends DefaultPluginManager {

  /**
   * The ID of the default mailer which is used in case there is none defined.
   */
  const DEFAULT_MAILER = 'php_mail';

  /**
   * Constructor.
   * @param \Traversable $namespaces
   */
  public function __construct(\Traversable $namespaces) {
    parent::__construct('Plugin/mailsystem', $namespaces);
    $this->discovery = new StaticDiscoveryDecorator($this->discovery, array($this, 'registerDefinitions'));
  }

  /**
   * Callback for registering definitions for default Mailsystem classes.
   *
   * @see MailsystemPluginManager::__construct()
   */
  public function registerDefinitions() {
    $this->discovery->setDefinition(self::DEFAULT_MAILER, array(
      'id' => 'php_mail',
      'label' => t('PhpMail'),
      'class' => '\Drupal\Core\Mail\PhpMail',
      'provider' => 'core',
    ));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception If no class was found.
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // Set the default plugin_id in case it is not set.
    $plugin_id = isset($plugin_id) ? $plugin_id : self::DEFAULT_MAILER;

    // First try to create a BasePlugin based Mailplugin, if this fails,
    // use the default method from \Drupal\Core\Mail\MailFactory::get()
    $definition = $this->getDefinition($plugin_id);
    $reflection = new \ReflectionClass($definition['class']);
    if ($reflection->implementsInterface('Drupal\Core\Mail\MailInterface') ||
      $reflection->implementsInterface('Drupal\mailsystem\FormatterInterface') ||
      $reflection->implementsInterface('Drupal\mailsystem\SenderInterface')
    ) {
      if ($reflection->isSubclassOf('Drupal\Component\Plugin\PluginBase')) {
        return parent::createInstance($plugin_id, $configuration);
      }
      else {
        return $reflection->newInstance();
      }
    }
    throw new \Exception(String::format('Class %class does not implement interface %interface', array('%class' => $plugin_id, '%interface' => 'Drupal\Core\Mail\MailInterface')));
  }
}