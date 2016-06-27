<?php

namespace Drupal\sapi;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sapi\Exception\MissingConfigurationObject;

/**
 * Base class for Statistics action handler plugins that implements
 * PluginFormInterface and provides configuration form.
 * Configuration file should use sapi.plugin.{plugin_id} namespace in order for
 * formSubmit to save to it automatically. Otherwise editableConfiguration
 * must be provided in plugin construct.
 */
abstract class ConfigurableActionHandlerBase extends PluginBase implements ActionHandlerInterface, PluginFormInterface, ConfigurablePluginInterface {
  use ConfigFormBaseTrait;
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Configuration name for this plugin
   *
   * @var array $editableConfiguration
   */
  protected $editableConfiguration;

  /**
   * Constructs a \Drupal\sapi\ConfigurableActionHandlerBase object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory ) {
    $this->configFactory = $config_factory;
    $this->editableConfiguration = 'sapi.plugin.'.$plugin_id;
    parent:: __construct($configuration + $this->getConfiguration(), $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configFactory->get($this->editableConfiguration)->get();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [$this->editableConfiguration];
  }

  /**
   *{@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   *{@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_base_keys = array_fill_keys(
      array(
        'submit',
        'cancel',
        'form_build_id',
        'form_token',
        'form_id',
        'op'),
      '');
    $form_configurations = array_diff_key($form_state->getValues(), $form_base_keys);
    foreach ($form_configurations as $key => $value) {
      /** Store in configuration only values that have been set in form */
      $this->configuration[$key] = is_array($form_state->getValue($key))
        ? array_filter($form_state->getValue($key))
        : $form_state->getValue($key);
    }
    if($this->setConfiguration($this->configuration)) {
      drupal_set_message('Plugin: '.$this->getPluginId().' configurations has been saved successfully.');
    } else {
      throw new MissingConfigurationObject('Error saving configurations for plugin: ' . $this->getPluginId());
    }
  }

  /**
   *{@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    foreach ($configuration as $key => $value) {
      $this->config($this->editableConfiguration)->set($key, $value);
    }
    return $this->config($this->editableConfiguration)->save();
  }

  /**
   * Provides dynamic page title.
   *
   * @return string Returns a page title.
   */
  public function getTitle() {
    return $this->t('Configure').' '.$this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * Sets form to defaults if defaults are set.
   */
  public function resetFormToDefaults() {
    $this->setConfiguration($this->defaultConfiguration());
    drupal_set_message('Plugin: '.$this->getPluginId().' has been reset to default values.');
  }
  
}
