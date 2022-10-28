<?php
/* Copyright (C) 2018       Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Put here all includes required by your class file

// require '../../main.inc.php';
// Dolibarr environment
$res = 0;
if (! $res && file_exists("../main.inc.php"))
{
	$res = @include "../main.inc.php";
}
if (! $res && file_exists("../../main.inc.php"))
{
	$res = @include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php"))
{
	$res = @include "../../../main.inc.php";
}
if (! $res)
{
	die("Main include failed");
}
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'salaries', 'bills', 'hrm', 'stripe'));

// Security check
$socid = GETPOST("socid", "int");
if ($user->socid) $socid = $user->socid;
//$result = restrictedArea($user, 'salaries', '', '', '');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$rowid = GETPOST("rowid", 'alpha');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;



/*
 * View
 */

$form = new Form($db);
$societestatic = new Societe($db);
$memberstatic = new Adherent($db);
$acc = new Account($db);
$stripe = new Stripe($db);

llxHeader('', $langs->trans("StripeAccountList"));

if (!empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha')))
{
	$service = 'StripeTest';
	$servicestatus = '0';
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
} else {
	$service = 'StripeLive';
	$servicestatus = '1';
}

$stripeacc = $stripe->getStripeAccount($service);
/*if (empty($stripeaccount))
{
	print $langs->trans('ErrorStripeAccountNotDefined');
}*/

if (!$rowid)
{
	$option = array('limit' => $limit + 1);
	$num = 0;

	if (GETPOSTISSET('starting_after_'.$page)) $option['starting_after'] = GETPOST('starting_after_'.$page, 'alphanohtml');

	try {
		if ($stripeacc)
		{
			$list = \Stripe\Account::all($option, array("stripe_account" => $stripeacc));
		} else {
			$list = \Stripe\Account::all($option);
		}
    //print $list;
		$num = count($list->data);

		$totalnboflines = '';

		$param = '';
		//if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
		$param .= '&starting_after_'.($page + 1).'='.$list->data[($limit - 1)]->id;
		//$param.='&ending_before_'.($page+1).'='.$list->data[($limit-1)]->id;

		$moreforfilter = '';

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	    print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	    print '<input type="hidden" name="action" value="list">';
	    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	    print '<input type="hidden" name="page" value="'.$page.'">';

	    $title = $langs->trans("StripeAccountList");
	    $title .= ($stripeacc ? ' (Stripe connection with Stripe OAuth Connect account '.$stripeacc.')' : ' (Stripe connection with keys from Stripe module setup)');

		print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'stripe-s', 0, '', 'hidepaginationprevious', $limit);

	    print '<div class="div-table-responsive">';
	    print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	    print '<tr class="liste_titre">';
	    print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	    print_liste_field_titre("Name", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
      	print_liste_field_titre("Customer", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
      	if (isModEnabled('multicompany') print_liste_field_titre("Entity", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	    print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	    print_liste_field_titre("Origin", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder); 	 
      	print_liste_field_titre("Currency", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder, 'right ');
	    print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder, 'center ');
	    print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "", "", "", '', '', '', 'right ');
	    print "</tr>\n";

		//print $list;
		$i = 0;
		foreach ($list->data as $charge)
		{
			if ($i >= $limit) {
				break;
			}
		$sql = "SELECT sa.fk_soc as fk_soc";
		$sql .= " FROM ".MAIN_DB_PREFIX."oauth_token as sa";
		$sql .= " WHERE sa.entity IN (".getEntity('oauth_token').") AND sa.tokenstring LIKE '%".$db->escape($charge->id)."%'";
		$sql .= " AND sa.service = '".$service."'";

		$result = $db->query($sql);
		if ($result) {
			if ($db->num_rows($result)) {
				$obj = $db->fetch_object($result);
				$socid = $obj->fk_soc;
			}
		}

			print '<tr class="oddeven">';

			// Ref
			$url = 'https://dashboard.stripe.com/test/connect/accounts/'.$charge->id;
	        if ($servicestatus)
	        {
	        	$url = 'https://dashboard.stripe.com/connect/accounts/'.$charge->id;
	        }
			print "<td>";
	    print "<a href='".$url."' target='_stripe'>".img_picto($langs->trans('ShowInStripe'), 'globe')." ".$charge->id."</a>";
			print "</td>\n";

			// Stripe customer
			print "<td>";
      print $charge->company->name;
	    print "</td>\n";
      
		// Customer
		print "<td>";
		if ($socid > 0) {
        	$societestatic->fetch($socid);
			print $societestatic->getNomUrl(1);
		}
	    print "</td>";
      
			// Entity
	if (isModEnabled('multicompany'){
			print "<td>";
      dol_include_once('/multicompany/class/actions_multicompany.class.php');
 	$sql = "SELECT entity";
	$sql .= " FROM ".MAIN_DB_PREFIX."oauth_token";
	$sql .= " WHERE service = '".$db->escape($service)."' and tokenstring LIKE '%".$db->escape($charge->id)."%'";

	dol_syslog(get_class($db)."::fetch", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result)
	{
		if ($db->num_rows($result))
		{
			$obj = $db->fetch_object($result);
			$key = $obj->entity;
		} else {
			$key = 1;
		}
	} else {
		$key = 1;
	}
      $action = new ActionsMulticompany($db);
	    $action->getInfo($key);
      print $action->label;
      print "</td>\n";
}

			// Type
			print "<td>";
      print $charge->type;
	    print "</td>\n";

			// Origin
			print "<td>";
      $img = picto_from_langcode($charge->country);
			print $img ? $img.' ' : '';
      print getCountry($charge->country, 1);
	    print "</td>\n";
      
		  // Currency
		  print '<td class="right">'.$langs->trans("Currency".strtoupper($charge->default_currency)).'</td>';
			
      // Date payment
		  print '<td class="center">'.dol_print_date($charge->created, '%d/%m/%Y %H:%M')."</td>\n";

		    // Status
		    print '<td class="right">';
		    print dolGetStatus($charge->details_submitted, !empty($charge->details_submitted) ? $langs->trans("Completed") : $langs->trans("Pending"), '', ($charge->details_submitted) ? 'status4' : 'status0', 5);
		    print "</td>\n";

		    print "</tr>\n";

		    $i++;
		}

		print '</table>';
		print '</div>';
		print '</form>';
	} catch (Exception $e) {
		print $e->getMessage();
	}
}

// End of page
llxFooter();
$db->close();
