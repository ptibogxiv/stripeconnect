<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2012	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2013-2014   Florian Henry   <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \defgroup agefodd Module AGeFoDD (Assistant de GEstion de la FOrmation Dans Dolibarr)
 * \brief agefodd module descriptor.
 * \file /core/modules/modAgefodd.class.php
 * \ingroup agefodd
 * \brief Description and activation file for module agefodd
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * \class modAgefodd
 * \brief Description and activation class for module agefodd
 */
class modStripeConnect extends DolibarrModules {
	var $error;
	/**
	 * Constructor.
	 *
	 * @param DoliDB		Database handler
	 */
	function __construct($db) {
    global $langs, $conf;
		
		$this->db = $db;
		
		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 431320;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'stripeconnect';
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
    	$this->editor_name = 'ptibogxiv.eu';
    	$this->editor_url = 'https://www.ptibogxiv.eu';
		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "interface";
    	// Can be enabled / disabled only in the main company with superadmin account
		$this->core_enabled = 1;
		// Module label, used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Module StripeConnect";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '16.0.3';
		
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 1;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/images directory, use this->picto=DOL_URL_ROOT.'/module/images/file.png'
		$this->picto = 'stripe';
		
    
    // Dependencies
    $this->depends = array();		// List of modules id that must be enabled if this module is enabled
    $this->requiredby = array();	// List of modules id to disable if this one is disabled
    $this->conflictwith = array();
    $this->phpmin = array(5,0);					// Minimum version of PHP required by module
    $this->need_dolibarr_version = array(5,0);	// Minimum version of Dolibarr required by module
    $this->langfiles = array("stripeconnect@stripeconnect");


       // Config pages. Put here list of php page, stored into oblyon/admin directory, to use to setup module.
    $this->config_page_url = array("stripeconnect.php@stripeconnect");
    
		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
				    'hooks' => array(
						'data' => array(
                'thirdpartycard',
                'invoicecard',
                'invoicesuppliercard',
                'membercard',
                'membertypecard'
						),
						'entity' => '0'
				),
		);

        // New pages on tabs
        // -----------------
		$this->tabs = array(array(
            'data' => 'mycompany_admin:+stripeaccount:StripeAccount:stripeconnect@stripeconnect:$user->rights->banque->lire:/stripeconnect/account.php',
            'entity' => '0')
            );

        // Boxes
        //------
        $this->boxes = array();

		// Main menu entries
      $r=0;
	    $this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=bank,fk_leftmenu=stripe',
			'type' => 'left',
			'titre' => 'StripeAccountList',
			'url' => '/stripeconnect/list.php',
			'langs' => 'stripeconnect@stripeconnect',
			'position' => 104,
			'enabled' => '$conf->stripeconnect->enabled && $conf->banque->enabled',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);

        // Dictionnaries
        if (! isset($conf->stripeconnect) || ! isset($conf->stripeconnect->enabled)) {
            $conf->stripeconnect = new stdClass();
            $conf->stripeconnect->enabled = 0;
        }

        $this->dictionaries = array(
            'langs' => 'stripeconnect@stripeconnect',
            'tabname' => array(MAIN_DB_PREFIX . "c_merchantcategorycodes"),
            'tablib' => array("MerchantCategoryCodes"),
            'tabsql' => array('SELECT f.rowid as rowid, f.code, f.label, f.stripe_enabled, f.active, f.use_default FROM ' . MAIN_DB_PREFIX . 'c_merchantcategorycodes as f'),
            'tabsqlsort' => array("code ASC"),
            'tabfield' => array("code,label,stripe_enabled,use_default"),
            'tabfieldvalue' => array("code,label,stripe_enabled,use_default,active"),
            'tabfieldinsert' => array("code,label,stripe_enabled,use_default,active"),
            'tabrowid' => array("rowid"),
            'tabcond' => array($conf->stripeconnect->enabled),
            'tabhelp' => array(array('code'=>$langs->trans("EnterAnyCode"), 'use_default'=>$langs->trans("Enter0or1"))),
        );
  }
   	/**
	 * Function called when module is enabled.
	 * The init function adds tabs, constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options   Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *                          'noboxes' = Do not insert boxes
	 *                          'newboxdefonly' = For boxes, insert def of boxes only and not boxes activation
	 * @return int				1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('STRIPE_TEST_PUBLISHABLE_KEY', 1)." AND entity != '0' ",
			"DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('STRIPE_TEST_SECRET_KEY', 1)." AND entity != '0' ",
      "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('STRIPE_LIVE_PUBLISHABLE_KEY', 1)." AND entity != '0' ",
			"DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('STRIPE_LIVE_SECRET_KEY', 1)." AND entity != '0' ",
		);

		return $this->_init($sql);
	}

	/**
	 * Function called when module is disabled.
	 * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             		1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('STRIPE_TEST_PUBLISHABLE_KEY', 1)." AND entity != '0' ",
			"DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('STRIPE_TEST_SECRET_KEY', 1)." AND entity != '0' ",
      "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('STRIPE_LIVE_PUBLISHABLE_KEY', 1)." AND entity != '0' ",
			"DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('STRIPE_LIVE_SECRET_KEY', 1)." AND entity != '0' ",
      "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('MAIN_MODULE_STRIPECONNECT_TABS_0', 1)." AND entity = '0' "
		);

		return $this->_remove($sql, $options);
	}


	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	public function load_tables()
	{
		return $this->_load_tables('/stripeconnect/sql/');
	}

}

?>