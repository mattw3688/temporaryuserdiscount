<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class TemporaryUserDiscount extends Module
{
    public function __construct()
    {
        $this->name = 'temporaryuserdiscount';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Matt Wheeler';
        $this->need_instance = 0;
        // Could work with older versions but untested
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Temporary User Discount', [], 'Modules.Temporaryuserdiscount.Admin');
        $this->description = $this->trans('Sets a discount code of a predetermined percentage for and new user when an account is registered', [], 'Modules.Temporaryuserdiscount.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Temporaryuserdiscount.Admin');

        if (!Configuration::get('TEMPORARYUSERDISCOUNT_NAME')) {
            $this->warning = $this->trans('No name provided', [], 'Modules.Temporaryuserdiscount.Admin');
        }
    }

    public function install()
    {
        // in case of multi-store, set config to all stores
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return
            parent::install() &&
            //Register built in prestashop hooks for use in the module
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('displayAfterBodyOpeningTag') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            //Setup config fields
            Configuration::updateValue('TEMPORARYUSERDISCOUNT_NAME', 'Temporary User Discount') &&
            Configuration::updateValue('TEMPORARYUSERDISCOUNT_PERCENTAGE', '0') &&
            Configuration::updateValue('TEMPORARYUSERDISCOUNT_VALIDITY', '1') &&
            Configuration::updateValue('TEMPORARYUSERDISCOUNT_BANNER_CONTENT', 'Hey {firstName} you have a {discountPercent}% discount available - {discountCode}') &&
            Configuration::updateValue('TEMPORARYUSERDISCOUNT_TEXT_COLOUR', '#FFFFFF') &&
            Configuration::updateValue('TEMPORARYUSERDISCOUNT_BG_COLOUR', '#000000') &&
            Configuration::updateValue('TEMPORARYUSERDISCOUNT_HIGHLIGHT', false)
        ;
    }

    public function uninstall()
    {
        return
            parent::uninstall() &&
            Configuration::deleteByName('TEMPORARYUSERDISCOUNT_NAME') &&
            Configuration::deleteByName('TEMPORARYUSERDISCOUNT_PERCENTAGE') &&
            Configuration::deleteByName('TEMPORARYUSERDISCOUNT_VALIDITY') &&
            Configuration::deleteByName('TEMPORARYUSERDISCOUNT_BANNER_CONTENT') &&
            Configuration::deleteByName('TEMPORARYUSERDISCOUNT_BG_COLOUR') &&
            Configuration::deleteByName('TEMPORARYUSERDISCOUNT_TEXT_COLOUR') &&
            Configuration::deleteByName('TEMPORARYUSERDISCOUNT_HIGHLIGHT')
        ;
    }

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submit'.$this->name)) {
            // Retrieve form values
            $bannerContent = Tools::getValue('TEMPORARYUSERDISCOUNT_BANNER_CONTENT');
            $percentage = Tools::getValue('TEMPORARYUSERDISCOUNT_PERCENTAGE');
            $validity = Tools::getValue('TEMPORARYUSERDISCOUNT_VALIDITY');
            $highlight = (bool) Tools::getValue('TEMPORARYUSERDISCOUNT_HIGHLIGHT');
            $bgColour = Tools::getValue('TEMPORARYUSERDISCOUNT_BG_COLOUR');
            $textColour = Tools::getValue('TEMPORARYUSERDISCOUNT_TEXT_COLOUR');

            // Validate inputs
            $errors = $this->validateInputs($percentage, $validity, $bannerContent, $bgColour, $textColour);

            if (empty($errors)) {
                // Update configuration values
                Configuration::updateValue('TEMPORARYUSERDISCOUNT_PERCENTAGE', $percentage);
                Configuration::updateValue('TEMPORARYUSERDISCOUNT_VALIDITY', $validity);
                Configuration::updateValue('TEMPORARYUSERDISCOUNT_HIGHLIGHT', $highlight);
                Configuration::updateValue('TEMPORARYUSERDISCOUNT_BANNER_CONTENT', $bannerContent);
                Configuration::updateValue('TEMPORARYUSERDISCOUNT_BG_COLOUR', $bgColour);
                Configuration::updateValue('TEMPORARYUSERDISCOUNT_TEXT_COLOUR', $textColour);

                $output .= $this->displayConfirmation($this->l('Settings updated.'));
            } else {
                // Display errors if any
                foreach ($errors as $error) {
                    $output .= $this->displayError($error);
                }
            }
        }

        // Display the configuration form
        return $output.$this->displayForm();
    }

    private function validateInputs(int $percentage, int $validity, string $bannerContent, string $bgColour, string $textColour): array
    {
        $errors = [];

        if (!Validate::isUnsignedInt($percentage) || $percentage < 0 || $percentage > 100) {
            $errors[] = $this->l('The discount percentage must be a number between 0 and 100.');
        }

        if (!in_array($validity, ['1', '7', '30'])) {
            $errors[] = $this->l('Invalid validity period. Please select a valid option.');
        }

        if (strlen($bannerContent) > 100) {
            $errors[] = $this->l('Banner content is too long (Max 100 characters)');
        }

        // Validation for colour codes. Uses regex to check for hex code
        if (!preg_match('/^#[0-9A-F]{6}$/i', $bgColour)) {
            $errors[] = $this->l('Background color must be a valid hex code.');
        }

        if (!preg_match('/^#[0-9A-F]{6}$/i', $textColour)) {
            $errors[] = $this->l('Text color must be a valid hex code.');
        }

        return $errors;
    }

    /**
     * Build the configuration form.
     *
     * @return string HTML code
     */
    public function displayForm(): string
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Discount Percentage'),
                    'name' => 'TEMPORARYUSERDISCOUNT_PERCENTAGE',
                    'size' => 20,
                    'required' => true,
                    'desc' => $this->l('Enter the discount percentage for the user. Use a whole number between 0 and 100.'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Validity Period (Days)'),
                    'name' => 'TEMPORARYUSERDISCOUNT_VALIDITY',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id_option' => '1', 'name' => '1 day'],
                            ['id_option' => '7', 'name' => '7 days'],
                            ['id_option' => '30', 'name' => '30 days'],
                        ],
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Highlight Discount'),
                    'name' => 'TEMPORARYUSERDISCOUNT_HIGHLIGHT',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'desc' => $this->l('Automatically highlight and apply discount if conditions are met.'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $fieldsForm[1]['form'] = [
            'legend' => [
                'title' => $this->l('Banner Settings'),
            ],
            'input' => [
                [
                    'type' => 'textarea',
                    'label' => $this->l('Banner Content'),
                    'name' => 'TEMPORARYUSERDISCOUNT_BANNER_CONTENT',
                    'size' => 20,
                    'required' => true,
                    'desc' => $this->l('Use placeholders {firstName}, {lastName}, {discountCode} and {$discountPercent} to display this in your message'),
                ],
                [
                    'type' => 'color',
                    'label' => $this->l('Background Color'),
                    'name' => 'TEMPORARYUSERDISCOUNT_BG_COLOUR',
                    'required' => false,
                ],
                [
                    'type' => 'color',
                    'label' => $this->l('Text Color'),
                    'name' => 'TEMPORARYUSERDISCOUNT_TEXT_COLOUR',
                    'required' => false,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];
        //@todo add comments to each of these to understand whats going on
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->submit_action = 'submit'.$this->name;
        $helper->fields_value = [
            'TEMPORARYUSERDISCOUNT_PERCENTAGE' => Configuration::get('TEMPORARYUSERDISCOUNT_PERCENTAGE'),
            'TEMPORARYUSERDISCOUNT_VALIDITY' => Configuration::get('TEMPORARYUSERDISCOUNT_VALIDITY'),
            'TEMPORARYUSERDISCOUNT_BANNER_CONTENT' => Configuration::get('TEMPORARYUSERDISCOUNT_BANNER_CONTENT'),
            'TEMPORARYUSERDISCOUNT_BG_COLOUR' => Configuration::get('TEMPORARYUSERDISCOUNT_BG_COLOUR'),
            'TEMPORARYUSERDISCOUNT_TEXT_COLOUR' => Configuration::get('TEMPORARYUSERDISCOUNT_TEXT_COLOUR'),
            'TEMPORARYUSERDISCOUNT_HIGHLIGHT' => Configuration::get('TEMPORARYUSERDISCOUNT_HIGHLIGHT'),
        ];

        return $helper->generateForm($fieldsForm);
    }

    /**
     * Create single use discount code on new user registration.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionCustomerAccountAdd(array $params): void
    {
        // Get new customer object from hook parameters
        $newCustomer = $params['newCustomer'];

        // Check customer is valid object
        if ($newCustomer instanceof Customer) {
            // Initialize the CartRule object
            $cartRule = new CartRule();
            $cartRule->name = [Configuration::get('PS_LANG_DEFAULT') => 'New User Automated Discount Code'];
            // Ensure the discount code is unique
            $cartRule->code = $this->generateUniqueDiscountCode($newCustomer->lastname, Configuration::get('TEMPORARYUSERDISCOUNT_PERCENTAGE'));
            $cartRule->description = 'temporaryuserdiscount';
            $cartRule->quantity = 1;
            $cartRule->quantity_per_user = 1;
            $cartRule->reduction_percent = (int) Configuration::get('TEMPORARYUSERDISCOUNT_PERCENTAGE');
            $cartRule->id_customer = $newCustomer->id;
            $validity = (int) Configuration::get('TEMPORARYUSERDISCOUNT_VALIDITY');
            $cartRule->date_from = date('Y-m-d H:i:s');
            $cartRule->date_to = date('Y-m-d H:i:s', strtotime("+$validity days"));
            $cartRule->active = true;
            $cartRule->highlight = Configuration::get('TEMPORARYUSERDISCOUNT_HIGHLIGHT');

            $cartRule->add();
        }
    }


    /**
     * Generates a unique discount code.
     *
     * @param string $lastname
     * @param string $percentage
     *
     * @return string a unique discount code
     */
    private function generateUniqueDiscountCode(string $lastname, string $percentage): string
    {
        $baseCode = 'NIDECKERINTRO'.strtoupper($lastname).$percentage;
        $code = $baseCode;
        $counter = 1;

        // Check if the generated code already exists
        while (CartRule::cartRuleExists($code)) {
            // Append a counter to the code if it already exists
            $code = $baseCode.'_'.$counter++;
        }

        return $code;
    }

    public function hookDisplayAfterBodyOpeningTag(array $params): string
    {
        //Use context to check state of customer
        if ($this->context->customer->isLogged()) {
            //Get cart rule associated with this user
            $discountCode = $this->getCustomerDiscountCode($this->context->customer->id, 'temporaryuserdiscount');

            if ($discountCode) {
                //Original Banner Content
                $bannerContent = Configuration::get('TEMPORARYUSERDISCOUNT_BANNER_CONTENT');
                // Replace placeholders with config values
                $bannerContentProcessed = str_replace(
                    ['{firstName}', '{lastName}', '{discountPercent}', '{discountCode}'],
                    [$this->context->customer->firstname, $this->context->customer->lastname, $discountCode['reductionPercent'], $discountCode['code']],
                    $bannerContent
                );

                // Assign values to Smarty to use in template file
                $this->context->smarty->assign([
                    'bannerContent' => $bannerContentProcessed,
                    'showDiscountBanner' => true,
                    'discountCode' => $discountCode['code'],
                    'discountExpiry' => $discountCode['dateTo'],
                    'reductionPercent' => $discountCode['reductionPercent'],
                    'bgColour' => Configuration::get('TEMPORARYUSERDISCOUNT_BG_COLOUR'),
                    'textColour' => Configuration::get('TEMPORARYUSERDISCOUNT_TEXT_COLOUR'),
                ]);

                return $this->display(__FILE__, 'views/templates/hook/discountBanner.tpl');
            }
        }

        return '';
    }

    public function getCustomerDiscountCode(int $customerId, string $discountDescription): array
    {
        $discountCode = [];
        $cartRules = CartRule::getCustomerCartRules($this->context->language->id, $customerId, true, false);

        foreach ($cartRules as $cartRule) {
            if ($this->isValidDiscountCode($cartRule, $discountDescription)) {
                $discountCode = [
                    'code' => $cartRule['code'],
                    'dateTo' => $cartRule['date_to'],
                    'reductionPercent' => intval($cartRule['reduction_percent']),
                ];
                break; // Valid discount code found, exit loop
            }
        }

        return $discountCode;
    }

    private function isValidDiscountCode(array $cartRule, string $discountDescription): bool
    {
        return
            $cartRule['quantity'] > 0 &&
            $cartRule['description'] == $discountDescription &&
            !empty($cartRule['code']) &&
            !empty($cartRule['date_to']) &&
            isset($cartRule['reduction_percent']);
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        //Register CSS file
        $this->context->controller->registerStylesheet(
            'temporaryuserdiscount-style',
            'modules/'.$this->name.'/views/css/temporaryuserdiscount.css',
            [
                'media' => 'all',
                'priority' => 1000,
            ]
        );
        // Register JavaScript file
        $this->context->controller->registerJavascript(
            'temporaryuserdiscount-script',
            'modules/'.$this->name.'/views/js/temporaryuserdiscount.js',
            [
                'position' => 'bottom',
                'priority' => 1000,
            ]
        );
    }
}
