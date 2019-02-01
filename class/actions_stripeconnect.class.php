<?php
/* Copyright (C) 2009-2016 Regis Houssin  <regis@dolibarr.fr>
 * Copyright (C) 2011      Herve Prot     <herve.prot@symeos.com>
 * Copyright (C) 2014      Philippe Grand <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/multicompany/actions_multicompany.class.php
 *	\ingroup    multicompany
 *	\brief      File Class multicompany
 */
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
$langs->load("stripeconnect@stripeconnect");

/**
 *	\class      ActionsMulticompany
 *	\brief      Class Actions of the module multicompany
 */
class ActionsStripeconnect
{
	/** @var DoliDB */
	var $db;

	private $config=array();

	// For Hookmanager return
	var $resprints;
	var $results=array();


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}
  

	/**
	 *
	 */
	function formObjectOptions($parameters=false, &$object, &$action='')
	{
		global $db,$conf,$user,$langs,$form;

      if (! empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || empty($conf->global->STRIPECONNECT_LIVE) || GETPOST('forcesandbox','alpha')))
{
	$service = 'StripeTest';
	
}
else
{
	$service = 'StripeLive';
}

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$key=$value;
			}
		}
		
if (is_object($object) && $object->element == 'member'){   
				$this->resprints.= '<tr><td>';
				$this->resprints.= '<table width="100%" class="nobordernopadding"><tr><td>';
				$this->resprints.= $langs->trans('SubscriptionStripe');
				$this->resprints.= '<td><td align="right">';
				$this->resprints.= '</td></tr></table>';
				$this->resprints.= '</td>';
				$this->resprints.= '<td colspan="3">';
        $stripe=new Stripe($db);
if (7==4) {
$customer=$stripe->customerStripe($societe,$stripe->getStripeAccount($service));
$this->resprints.= $customer->id;
}
else {
$this->resprints.= $langs->trans("NoStripe");
}		 
				$this->resprints.= '</td></tr>';
     }   
   elseif (is_object($object) && $object->element == 'adherent_type'){ 
				$this->resprints.= '<tr><td>';
				$this->resprints.= '<table width="100%" class="nobordernopadding"><tr><td>';
				$this->resprints.= $langs->trans('PlanStripe');
				$this->resprints.= '<td><td align="right">';
				$this->resprints.= '</td></tr></table>';
				$this->resprints.= '</td>';
				$this->resprints.= '<td colspan="3">';
        $stripe=new Stripe($db);
if (7==4) {
$customer=$stripe->customerStripe($societe,$stripe->getStripeAccount($service));
$this->resprints.= $customer->id;
}
else {
$this->resprints.= $langs->trans("NoStripe");
}		 
				$this->resprints.= '</td></tr>';
     }
		return 0;
	}
  
  	function addMoreActionsButtons($parameters=false, &$object, &$action='')
	{
		global $db,$conf,$user,$langs,$form;
    if (is_object($object) && $object->element == 'facture'){
    			// On verifie si la facture a des paiements
			$sql = 'SELECT pf.amount';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'paiement_facture as pf';
			$sql .= ' WHERE pf.fk_facture = ' . $object->id;

			$result = $db->query($sql);
			if ($result) {
				$i = 0;
				$num = $db->num_rows($result);

				while ($i < $num) {
					$objp = $db->fetch_object($result);
					$totalpaye += $objp->amount;
					$i ++;
				}
			} else {
				dol_print_error($db, '');
			}

			$resteapayer = $object->total_ttc - $totalpaye;
			// Request a direct debit order
			if ($object->statut > Facture::STATUS_DRAFT && $object->statut < Facture::STATUS_ABANDONED && $object->paye == 0)
			{   $stripe=new Stripe($db);
			    if ($resteapayer > 0)
			    {                    
			        if ($stripe->getStripeCustomerAccount($object->socid)) // a modifier avec droit stripe
			        {   
			            $langs->load("withdrawals");
			            print '<a class="butActionDelete" href="'.DOL_MAIN_URL_ROOT.'/stripe/payment.php?facid='.$object->id.'&action=create'.'" title="'.dol_escape_htmltag($langs->trans("StripeConnectPay")).'">'.$langs->trans("StripeConnectPay").'</a>';
			        }
			        else
			        {
			            print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("StripeConnectPay").'</a>';
			        }
			    }
			   elseif ($resteapayer == 0)
			    { 
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("StripeConnectPay").'</a>';
         }    
			}
      else {
      print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("StripeConnectPay").'</a>';
      }
    } 
    elseif (is_object($object) && $object->element == 'invoice_supplier'){ 
print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("StripeConnectPay")).'">'.$langs->trans("StripeConnectPay").'</a>';
     } 
    elseif (is_object($object) && $object->element == 'member'){ 
print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("StripeAutoSubscription")).'">'.$langs->trans("StripeAutoSubscription").'</a>';
     }    		           
		return 0;
	}

}
