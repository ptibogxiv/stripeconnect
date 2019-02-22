<?php
/* Copyright (C) 2018		Thibault FOUCART		<support@ptibogxiv.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/stripe/admin/stripe.php
 * \ingroup    stripe
 * \brief      Page to setup stripe module
 */

require '../../../main.inc.php';
dol_include_once('/stripeconnect/lib/stripeconnect.lib.php');
dol_include_once('/multicompany/class/dao_multicompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

$servicename='Stripe';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'paypal', 'paybox', 'stripe', 'stripeconnect@stripeconnect', 'multicompany@multicompany'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');


if ($action == 'setvalue' && $user->admin)
{
	$db->begin();

	$result=dolibarr_set_const($db, "STRIPE_TEST_PUBLISHABLE_KEY",GETPOST('STRIPE_TEST_PUBLISHABLE_KEY','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_TEST_SECRET_KEY",GETPOST('STRIPE_TEST_SECRET_KEY','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_LIVE_PUBLISHABLE_KEY",GETPOST('STRIPE_LIVE_PUBLISHABLE_KEY','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_LIVE_SECRET_KEY",GETPOST('STRIPE_LIVE_SECRET_KEY','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
  $result=dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_ID",GETPOST('STRIPE_TEST_WEBHOOK_ID','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
  $result=dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_KEY",GETPOST('STRIPE_TEST_WEBHOOK_KEY','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;  	
  $result=dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_CONNECT_ID",GETPOST('STRIPE_TEST_WEBHOOK_CONNECT_ID','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;  
	$result=dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_CONNECT_KEY",GETPOST('STRIPE_TEST_WEBHOOK_CONNECT_KEY','alpha'),'chaine',0,'',0); 
	if (! $result > 0) $error++;                                                
  $result=dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_ID",GETPOST('STRIPE_LIVE_WEBHOOK_ID','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_LIVE_WEBHOOK_KEY",GETPOST('STRIPE_LIVE_WEBHOOK_KEY','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
  $result=dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_ID",GETPOST('STRIPE_LIVE_WEBHOOK_CONNECT_ID','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_LIVE_WEBHOOK_CONNECT_KEY",GETPOST('STRIPE_LIVE_WEBHOOK_CONNECT_KEY','alpha'),'chaine',0,'',0);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPECONNECT_PRINCIPAL",GETPOST('STRIPECONNECT_PRINCIPAL','alpha'),'chaine',0,'',0);
  if (! $result > 0) $error++;  
	$result=dolibarr_set_const($db, "STRIPE_APPLICATION_FEE_PERCENT",price2num(GETPOST('STRIPE_APPLICATION_FEE_PERCENT','alpha')),'chaine',0,'',0);
  	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_APPLICATION_FEE",price2num(GETPOST('STRIPE_APPLICATION_FEE','alpha')),'chaine',0,'',0);
    	if (! $result > 0) $error++;
  $result=dolibarr_set_const($db, "STRIPE_APPLICATION_FEE_MINIMAL",price2num(GETPOST('STRIPE_APPLICATION_FEE_MINIMAL','alpha')),'chaine',0,'',0);
    if (! $result > 0) $error++;
  $result=dolibarr_set_const($db, "STRIPE_APPLICATION_FEE_MAXIMAL",price2num(GETPOST('STRIPE_APPLICATION_FEE_MAXIMAL','alpha')),'chaine',0,'',0);
    if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_APPLICATION_MENSUAL_MINIMAL",price2num(GETPOST('STRIPE_APPLICATION_MENSUAL_MINIMAL','alpha')),'chaine',0,'',0);
    if (! $result > 0) $error++;
    	$result=dolibarr_set_const($db, "STRIPE_APPLICATION_FEE_PRODUCT_ID",GETPOST('STRIPE_APPLICATION_FEE_PRODUCT_ID','alpha'),'chaine',0,'',0);
    if (! $result > 0) $error++;
	
    if (! $error)
  	{
  		$db->commit();
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
  	}
  	else
  	{
  		$db->rollback();
		dol_print_error($db);
    }
}

if ($action=="mode")
{
	$modeenable = GETPOST('platform','int');
	$res = dolibarr_set_const($db, "STRIPECONNECT_PLATFORM_MODE", $modeenable,'yesno',0,'',0);
	if (! $res > 0) $error++;
	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action=="setlive")
{
	$liveenable = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "STRIPE_LIVE", $liveenable, 'yesno', 0, '', $conf->entity);
	if ($res > 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}
//TODO: import script for stripe account saving in alone or connect mode for stripe.class.php


/*
 *	View
 */

$form=new Form($db);
if (! empty($conf->stripe->enabled))
{
	$service = 'StripeTest';
	$servicestatus = 0;
	if (! empty($conf->global->STRIPE_LIVE) && ! GETPOST('forcesandbox', 'alpha'))
	{
		$service = 'StripeLive';
		$servicestatus = 1;
	}

	$stripe=new Stripe($db);
	$stripeacc = $stripe->getStripeAccount($service);
}

llxHeader('',$langs->trans("StripeConnectSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' StripeConnect',$linkback);

$head=stripeconnectadmin_prepare_head();

$stripearrayofwebhookevents=array('payout.created','payout.paid','charge.pending','charge.refunded','charge.succeeded','charge.failed','source.chargeable','customer.deleted');

// Test Webhook
if ( !empty($conf->global->STRIPE_TEST_WEBHOOK_KEY) && empty($conf->global->STRIPE_LIVE) && !empty($conf->global->STRIPE_TEST_WEBHOOK_ID) ) {
$endpoint = \Stripe\WebhookEndpoint::retrieve($conf->global->STRIPE_TEST_WEBHOOK_ID);
$endpoint->enabled_events = $stripearrayofwebhookevents;
$endpoint->url = dol_buildpath('/public/stripe/ipn.php?test', 2);
$endpoint->save();
print $endpoint;
}

// Connect Test Webhook
if ( !empty($conf->global->STRIPE_TEST_WEBHOOK_CONNECT_KEY) && empty($conf->global->STRIPE_LIVE) && !empty($conf->global->STRIPE_TEST_WEBHOOK_CONNECT_ID) ) {
$endpoint = \Stripe\WebhookEndpoint::retrieve($conf->global->STRIPE_TEST_WEBHOOK_CONNECT_ID);
$endpoint->enabled_events = $stripearrayofwebhookevents;
$endpoint->url = dol_buildpath('/public/stripe/ipn.php?connect&test', 2);
$endpoint->save();
print $endpoint;
}

// Live Webhook
if ( !empty($conf->global->STRIPE_LIVE_WEBHOOK_KEY) && !empty($conf->global->STRIPE_LIVE) && !empty($conf->global->STRIPE_LIVE_WEBHOOK_ID) ) {
$endpoint = \Stripe\WebhookEndpoint::retrieve($conf->global->STRIPE_LIVE_WEBHOOK_ID);
$endpoint->enabled_events = $stripearrayofwebhookevents;
$endpoint->url = dol_buildpath('/public/stripe/ipn.php', 2);
$endpoint->save();
print $endpoint;
}

// Connect Live Webhook
if ( !empty($conf->global->STRIPE_LIVE_WEBHOOK_CONNECT_KEY) && !empty($conf->global->STRIPE_LIVE) && !empty($conf->global->STRIPE_LIVE_WEBHOOK_CONNECT_ID) ) {
$endpoint = \Stripe\WebhookEndpoint::retrieve($conf->global->STRIPE_LIVE_WEBHOOK_CONNECT_ID);
$endpoint->enabled_events = $stripearrayofwebhookevents;
$endpoint->url = dol_buildpath('/public/stripe/ipn.php?connect', 2);
$endpoint->save();
print $endpoint;
}

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

dol_fiche_head($head, 'stripeaccount', '', -1);

print $langs->trans("Module431320Desc")."<br>\n";

print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("StripeLiveEnabled").'</td><td>';
  if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('STRIPE_LIVE');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("STRIPE_LIVE", $arrval, $conf->global->STRIPE_LIVE);
}
print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_TEST_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_TEST_PUBLISHABLE_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': pk_test_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="titlefield fieldrequired">'.$langs->trans("STRIPE_TEST_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_SECRET_KEY" value="'.$conf->global->STRIPE_TEST_SECRET_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': sk_test_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("STRIPE_TEST_WEBHOOK_KEY").'</span></td><td>';
  print '<input class="minwidth500" type="text" name="STRIPE_TEST_WEBHOOK_ID" value="'.$conf->global->STRIPE_TEST_WEBHOOK_ID.'"><br>';
	print '<input class="minwidth500" type="text" name="STRIPE_TEST_WEBHOOK_KEY" value="'.$conf->global->STRIPE_TEST_WEBHOOK_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx';
  $out = img_picto('', 'object_globe.png').' '.$langs->trans("ToOfferALinkForTestWebhook").'<br>';
  $url = dol_buildpath('/public/stripe/ipn.php?test', 2);
	$out.= '<input type="text" id="onlinetestwebhookurl" class="quatrevingtpercent" value="'.$url.'">';
	$out.= ajax_autoselect("onlinetestwebhookurl", 0);
	print '<br />'.$out; 
	print '</td></tr>';
  
  print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("STRIPE_TEST_WEBHOOK_CONNECT_KEY").'</span></td><td>';
  print '<input class="minwidth500" type="text" name="STRIPE_TEST_WEBHOOK_CONNECT_ID" value="'.$conf->global->STRIPE_TEST_WEBHOOK_CONNECT_ID.'"><br>';
	print '<input class="minwidth500" type="text" name="STRIPE_TEST_WEBHOOK_CONNECT_KEY" value="'.$conf->global->STRIPE_TEST_WEBHOOK_CONNECT_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx';
  $out = img_picto('', 'object_globe.png').' '.$langs->trans("ToOfferALinkForTestConnectWebhook").'<br>';
  $url = dol_buildpath('/public/stripe/ipn.php?connect&test', 2);
	$out.= '<input type="text" id="onlinetestconnectwebhookurl" class="quatrevingtpercent" value="'.$url.'">';
	$out.= ajax_autoselect("onlinetestconnectwebhookurl", 0);
	print '<br />'.$out; 
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_LIVE_PUBLISHABLE_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': pk_live_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_SECRET_KEY" value="'.$conf->global->STRIPE_LIVE_SECRET_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': sk_live_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("STRIPE_LIVE_WEBHOOK_KEY").'</span></td><td>';
  print '<input class="minwidth500" type="text" name="STRIPE_LIVE_WEBHOOK_CONNECT_ID" value="'.$conf->global->STRIPE_LIVE_CONNECT_ID.'"><br>';
	print '<input class="minwidth500" type="text" name="STRIPE_LIVE_WEBHOOK_KEY" value="'.$conf->global->STRIPE_LIVE_WEBHOOK_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx';
  $out = img_picto('', 'object_globe.png').' '.$langs->trans("ToOfferALinkForLiveWebhook").'<br>';
  $url = dol_buildpath('/public/stripe/ipn.php', 2);
	$out.= '<input type="text" id="onlinelivewebhookurl" class="quatrevingtpercent" value="'.$url.'">';
	$out.= ajax_autoselect("onlinelivewebhookurl", 0);
	print '<br />'.$out; 
	print '</td></tr>';
  
  print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("STRIPE_LIVE_WEBHOOK_CONNECT_KEY").'</span></td><td>';
  print '<input class="minwidth500" type="text" name="STRIPE_LIVe_WEBHOOK_CONNECT_ID" value="'.$conf->global->STRIPE_LIVE_WEBHOOK_CONNECT_ID.'"><br>';
	print '<input class="minwidth500" type="text" name="STRIPE_LIVE_WEBHOOK_CONNECT_KEY" value="'.$conf->global->STRIPE_LIVE_WEBHOOK_CONNECT_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx';
  $out = img_picto('', 'object_globe.png').' '.$langs->trans("ToOfferALinkForLiveConnectWebhook").'<br>';
  $url = dol_buildpath('/public/stripe/ipn.php?connect', 2);
	$out.= '<input type="text" id="onlineliveconnectwebhookurl" class="quatrevingtpercent" value="'.$url.'">';
	$out.= ajax_autoselect("onlineliveconnectwebhookurl", 0);
  print '<br />'.$out; 
	print '</td></tr>';

print '</table>';

print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UsageParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

// Mode of payment direct or destination charges
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// TODO Not used by current code
{
print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("StripeConnectPlatformMode").'</td><td>';
if (!empty($conf->global->STRIPECONNECT_PLATFORM_MODE))
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=mode&platform=0">';
	print img_picto($langs->trans("Activated"),'switch_on');
}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=mode&platform=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
}
print '</td></tr>';
}

// Choose principal/platform entity
if ( ! empty($conf->multicompany->enabled) ) {
$dao = new DaoMulticompany($db);
$dao->getEntities($login, $exclude);
print '<tr class="oddeven"><td>'.$langs->trans("STRIPECONNECT_PRINCIPAL").'</td>';
print '<td>';
		if (is_array($dao->entities))
		{
			print '<select class="flat maxwidth200onsmartphone minwidth100" id="STRIPECONNECT_PRINCIPAL" name="STRIPECONNECT_PRINCIPAL">';

			 //print '<option value="-1"';
			//		if ($conf->global->STRIPECONNECT_PRINCIPAL == -1) {
			//			print ' selected="selected"';
			//		}      
      //print '>'.$langs->trans('NoPrincipalEntity').'</option>';

			foreach ($dao->entities as $entity)
			{
				if ($entity->active == 1 && ($entity->visible == 1 || ($user->admin && ! $user->entity)))
				{
					if (is_array($only) && ! empty($only) && ! in_array($entity->id, $only)) continue;
					if (! empty($user->login) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && ! empty($user->entity) && $this->checkRight($user->id, $entity->id) < 0) continue;

					print '<option value="'.$entity->id.'"';
					if ($conf->global->STRIPECONNECT_PRINCIPAL == $entity->id) {
						print ' selected="selected"';
					}
					print '>';
					print $entity->label;
					if (empty($entity->visible)) {
						print ' ('.$langs->trans('Hidden').')';
					}
					print '</option>';
				}
			}

			print '</select>';
		}
		else {
			print $langs->trans('NoEntityAvailable');
		}
    
    // Make select dynamic
		include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		print ajax_combobox('STRIPECONNECT_PRINCIPAL');

print '</td></tr>';
}

print '<tr class="oddeven"><td>'.$langs->trans("STRIPE_APPLICATION_FEE_PLATFORM").'</td><td>';
print '<input size="5" type="text" name="STRIPE_APPLICATION_FEE_PERCENT" value="'.price($conf->global->STRIPE_APPLICATION_FEE_PERCENT).'">';
print '% + ';
print '<input size="5" type="text" name="STRIPE_APPLICATION_FEE" value="'.price($conf->global->STRIPE_APPLICATION_FEE).'">';
print ''.$langs->getCurrencySymbol($conf->currency).' '.$langs->trans("minimum").' <input size="5" type="text" name="STRIPE_APPLICATION_FEE_MINIMAL" value="'.price($conf->global->STRIPE_APPLICATION_FEE_MINIMAL).'"> '.$langs->getCurrencySymbol($conf->currency).' '.$langs->trans("maximum").' <input size="5" type="text" name="STRIPE_APPLICATION_FEE_MAXIMAL" value="'.price($conf->global->STRIPE_APPLICATION_FEE_MAXIMAL).'"> '.$langs->getCurrencySymbol($conf->currency).'</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("STRIPE_APPLICATION_FEE_PLATFORM_MINIMAL").'</td><td>';
print '<input size="5" type="text" name="STRIPE_APPLICATION_MENSUAL_MINIMAL" value="'.price($conf->global->STRIPE_APPLICATION_MENSUAL_MINIMAL).'">';
print ''.$langs->getCurrencySymbol($conf->currency).' HT</td></tr>';

	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		print '<tr class="oddeven"><td>'.$langs->trans("STRIPE_APPLICATION_FEE_PRODUCT_ID").'</td>';
		print '<td>';
		$form->select_produits($conf->global->STRIPE_APPLICATION_FEE_PRODUCT_ID, 'STRIPE_APPLICATION_FEE_PRODUCT_ID');
		print '</td></tr>';
	} 

print '</table>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';


$token='';

include DOL_DOCUMENT_ROOT.'/core/tpl/onlinepaymentlinks.tpl.php';

print info_admin($langs->trans("ExampleOfTestCreditCard", '4242424242424242', '4000000000000101', '4000000000000069', '4000000000000341'));

if (! empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
            $("#apidoc").hide();
            $("#apidoca").click(function() {
                $("#apidoc").show();
            	$("#apidoca").hide();
            });
    });';
	print '</script>';
}


llxFooter();
$db->close();
