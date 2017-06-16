<?php

if (!defined('_PS_VERSION_'))
	exit;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;


class Paymentendofthemonth extends PaymentModule
{
	const FLAG_DISPLAY_PAYMENT_INVITE = 'BANK_WIRE_PAYMENT_INVITE';

	protected $_html = '';
	protected $_postErrors = array();

	public $details;
	public $owner;
	public $address;
	public $extra_mail_vars;
	public function __construct()
	{
		$this->name = 'paymentendofthemonth';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'PrestaShop';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$config = Configuration::getMultiple(array('BANK_WIRE_DETAILS', 'BANK_WIRE_OWNER', 'BANK_WIRE_ADDRESS'));
		if (!empty($config['BANK_WIRE_OWNER']))
			$this->owner = $config['BANK_WIRE_OWNER'];
		if (!empty($config['BANK_WIRE_DETAILS']))
			$this->details = $config['BANK_WIRE_DETAILS'];
		if (!empty($config['BANK_WIRE_ADDRESS']))
			$this->address = $config['BANK_WIRE_ADDRESS'];

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Payment at the end of the month');
		$this->description = $this->l('Accept payments for your products via bank wire transfer, at the end of the month.');
		$this->confirmUninstall = $this->l('Are you sure about removing these details?');
		if (!isset($this->owner) || !isset($this->details) || !isset($this->address))
			$this->warning = $this->l('Account owner and account details must be configured before using this module.');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');

		$this->extra_mail_vars = array(
										'{paymentendofthemonth_owner}' => Configuration::get('BANK_WIRE_OWNER'),
										'{paymentendofthemonth_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
										'{paymentendofthemonth_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
										);
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment')
			|| ! $this->registerHook('displayPaymentEU')
			|| !$this->registerHook('paymentReturn')
			|| !$this->registerHook('paymentOptions')
			|| !Configuration::updateValue('PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER', '')
		)
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('BANK_WIRE_DETAILS')
				|| !Configuration::deleteByName('BANK_WIRE_OWNER')
				|| !Configuration::deleteByName('BANK_WIRE_ADDRESS')
				|| !Configuration::deleteByName('PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER')
				|| !parent::uninstall())
			return false;
		return true;
	}

	protected function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('BANK_WIRE_DETAILS'))
				$this->_postErrors[] = $this->l('Account details are required.');
			elseif (!Tools::getValue('BANK_WIRE_OWNER'))
				$this->_postErrors[] = $this->l('Account owner is required.');
		}
	}

	protected function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('BANK_WIRE_DETAILS', Tools::getValue('BANK_WIRE_DETAILS'));
			Configuration::updateValue('BANK_WIRE_OWNER', Tools::getValue('BANK_WIRE_OWNER'));
			Configuration::updateValue('BANK_WIRE_ADDRESS', Tools::getValue('BANK_WIRE_ADDRESS'));
			Configuration::updateValue('PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER', Tools::getValue('PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}

	protected function _displayBankWire()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}

	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';

		$this->_html .= $this->_displayBankWire();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_bw' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active)
			return;

		if (!$this->checkCurrency($params['cart']))
			return;


		$payment_options = array(
			'cta_text' => $this->l('Payment at the end of the month	'),
			'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/paymentendofthemonth.jpg'),
			'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
		);

		return $payment_options;
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active || !Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)) {
			return;
		}

		$state = $params['order']->getCurrentState();
		if (
		in_array(
			$state,
			array(
				Configuration::get('PS_OS_BANKWIRE'),
				Configuration::get('PS_OS_OUTOFSTOCK'),
				Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'),
			)
		)) {
			$bankwireOwner = $this->owner;
			if (!$bankwireOwner) {
				$bankwireOwner = '___________';
			}

			$bankwireDetails = Tools::nl2br($this->details);
			if (!$bankwireDetails) {
				$bankwireDetails = '___________';
			}

			$bankwireAddress = Tools::nl2br($this->address);
			if (!$bankwireAddress) {
				$bankwireAddress = '___________';
			}

			$this->smarty->assign(array(
				'shop_name' => $this->context->shop->name,
				'total' => Tools::displayPrice(
					$params['order']->getOrdersTotalPaid(),
					new Currency($params['order']->id_currency),
					false
				),
				'paymentendofthemonthDetails' => $bankwireDetails,
				'paymentendofthemonthAddress' => $bankwireAddress,
				'paymentendofthemonthOwner' => $bankwireOwner,
				'status' => 'ok',
				'reference' => $params['order']->reference,
				'contact_url' => $this->context->link->getPageLink('contact', true)
			));
		} else {
			$this->smarty->assign(
				array(
					'status' => 'failed',
					'contact_url' => $this->context->link->getPageLink('contact', true),
				)
			);
		}

		return $this->fetch('module:paymentendofthemonth/views/templates/hook/payment_return.tpl');
	}

	public function checkCurrency($cart)
	{

		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function renderForm()
	{

		$group = Group::getGroups($this->context->language->id);


		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Contact details'),
					'icon' => 'icon-envelope'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Account owner'),
						'name' => 'BANK_WIRE_OWNER',
						'required' => true
					),
					array(
						'type' => 'textarea',
						'label' => $this->l('Details'),
						'name' => 'BANK_WIRE_DETAILS',
						'desc' => $this->l('Such as bank branch, IBAN number, BIC, etc.'),
						'required' => true
					),
					array(
						'type' => 'textarea',
						'label' => $this->l('Bank address'),
						'name' => 'BANK_WIRE_ADDRESS',
						'required' => true
					),

					array(
						'type' => 'select',
						'lang' => true,
						'label' => $this->l('Group customer'),
						'name' => 'PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER',
						'options' => array(
							'query' => $group ,
							'id' => 'id_group',
							'name' => 'name'
						)
					),

				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'BANK_WIRE_DETAILS' => Tools::getValue('BANK_WIRE_DETAILS', Configuration::get('BANK_WIRE_DETAILS')),
			'BANK_WIRE_OWNER' => Tools::getValue('BANK_WIRE_OWNER', Configuration::get('BANK_WIRE_OWNER')),
			'BANK_WIRE_ADDRESS' => Tools::getValue('BANK_WIRE_ADDRESS', Configuration::get('BANK_WIRE_ADDRESS')),
			'PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER' => Tools::getValue('PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER', Configuration::get('PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER')),
		);
	}

	public function hookPaymentOptions($params)
	{
		if(Group::getCurrent()->id == Configuration::get('PAYMENTENDOFTHEMONTH_GROPE_CUSTOMER') ) {
			if (!$this->active) {
				return;
			}

			if (!$this->checkCurrency($params['cart'])) {
				return;
			}

			$this->smarty->assign(
				$this->getTemplateVarInfos()
			);

			$newOption = new PaymentOption();
			$newOption->setModuleName($this->name)
				->setCallToActionText($this->trans('Payment at the end of the month', array(), 'Modules.Paymentendofthemonth.Shop'))
				->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
				->setAdditionalInformation($this->fetch('module:paymentendofthemonth/views/templates/hook/paymentendofthemonth_intro.tpl'));
			$payment_options = [
				$newOption,
			];

			return $payment_options;
		}
	}

	public function getTemplateVarInfos()
	{
		$cart = $this->context->cart;
		$total = sprintf(
			$this->trans('%1$s (tax incl.)', array(), 'Modules.Wirepayment.Shop'),
			Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH))
		);

		$paymentendofthemonthOwner = $this->owner;
		if (!$paymentendofthemonthOwner) {
			$paymentendofthemonthOwner = '___________';
		}

		$paymentendofthemonthDetails = Tools::nl2br($this->details);
		if (!$paymentendofthemonthDetails) {
			$paymentendofthemonthDetails = '___________';
		}

		$paymentendofthemonthAddress = Tools::nl2br($this->address);
		if (!$paymentendofthemonthAddress) {
			$paymentendofthemonthAddress = '___________';
		}


		return array(
			'total' => $total,
			'paymentendofthemonthDetails' => $paymentendofthemonthDetails,
			'paymentendofthemonthAddress' => $paymentendofthemonthAddress,
			'paymentendofthemonthOwner' => $paymentendofthemonthOwner,
		);
	}

}
