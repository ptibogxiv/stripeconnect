<?php
/* Copyright (C) 2018-2019  Thibault FOUCART        <support@ptibogxiv.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// Put here all includes required by your class file

if(is_file('../main.inc.php'))$dir = '../';
else  if(is_file('../../../main.inc.php'))$dir = '../../../';
else $dir = '../../';

include($dir."main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'salaries', 'bills', 'hrm', 'stripe', 'stripeconnect@stripeconnect'));

// Security check
$socid = GETPOST("socid", "int");
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$rowid = GETPOST('rowid', 'int') ?GETPOST('rowid', 'int') : GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'salaries', '', '', '');

/*
 * View
 */

$form = new Form($db);
$acc = new Account($db);
$stripe = new Stripe($db);
$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);

llxHeader('', $langs->trans("StripePayoutList"));

if (! empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha'))) {
	$service = 'StripeTest';
	$servicestatus = '0';
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
}
else
{
	$service = 'StripeLive';
	$servicestatus = '1';
}

$stripeacc = $stripe->getStripeAccount($service);

if ($confirm == 'success') {

setEventMessages($langs->trans('StripeAccountUpdateSuccess'), null, 'mesgs');

}

if ($confirm == 'fail') {

setEventMessages($langs->trans('StripeAccountUpdateFail'), null, 'errors');

}

if ($action == 'update' && ($user->rights->banque->configurer))
{

$account_links = \Stripe\AccountLink::create([
    'account' => $stripeacc,
    'failure_url' => dol_buildpath('/stripeconnect/account.php?confirm=fail', 2),
    'success_url' => dol_buildpath('/stripeconnect/account.php?confirm=success', 2),
    'type' => 'custom_account_update',
    'collect' => 'eventually_due'
]);

header("Location: ".$account_links->url);
exit;

}

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') {
        print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    }
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	print '<input type="hidden" name="page" value="' . $page . '">';

	$title=$langs->trans("StripeAccount");
	$title.=($stripeacc?' (Stripe connection with Stripe OAuth Connect account '.$stripeacc.')':' (Stripe connection with keys from Stripe module setup)');

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_accountancy.png', 0, '', '', '');


	if ($stripeacc)
	{
    $account = \Stripe\Account::retrieve($stripeacc);
	}
  
if ($account->type == 'standard') {
//print $account;
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Account").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// Company
print '<tr class="oddeven"><td class="fieldrequired"><label for="name">'.$langs->trans("Name").'</label></td><td>';
print $account->business_profile->name;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

// Company
print '<tr class="oddeven"><td><label for="name">'.$langs->trans("Type").'</label></td><td>';
print $account->type;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

// Company
print '<tr class="oddeven"><td><label for="name">'.$langs->trans("MerchantCategoryCode").'</label></td><td>';
print $langs->getLabelFromKey($db, $account->business_profile->mcc, 'c_merchantcategorycodes', 'code', 'label');
print " (".$account->business_profile->mcc.")";
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("EMail").'</label></td><td>';
print $account->email;
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';
print '</td></tr>'."\n";    

print '<tr class="oddeven"><td><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td class="maxwidthonsmartphone">';
print getCountry($account->country);
//if (empty($country_selected)) $country_selected=substr($langs->defaultlang,-2);    // By default, country of localization
//print $form->select_country($mysoc->country_id, 'country_id');
//if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="currency">'.$langs->trans("CompanyCurrency").'</label></td><td>';  
print currency_name(strtoupper($account->default_currency), 1);
//print $form->selectCurrency($conf->currency, "currency");
print '</td></tr>'."\n";

// support
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Support").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_ADDRESS">'.$langs->trans("CompanyAddress").'</label></td><td>';
print $account->business_profile->support_address->line1;
print '<br>'.$account->business_profile->support_address->line2;
//print '<textarea name="MAIN_INFO_SOCIETE_ADDRESS" id="MAIN_INFO_SOCIETE_ADDRESS" class="quatrevingtpercent" rows="'.ROWS_3.'">'. ($conf->global->MAIN_INFO_SOCIETE_ADDRESS?$conf->global->MAIN_INFO_SOCIETE_ADDRESS:GETPOST("MAIN_INFO_SOCIETE_ADDRESS", 'nohtml')) . '</textarea>;
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_ZIP">'.$langs->trans("CompanyZip").'</label></td><td>';
print $account->business_profile->support_address->postal_code;
//print '<input class="minwidth100" name="MAIN_INFO_SOCIETE_ZIP" id="MAIN_INFO_SOCIETE_ZIP" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_ZIP?$conf->global->MAIN_INFO_SOCIETE_ZIP:GETPOST("MAIN_INFO_SOCIETE_ZIP", 'alpha')) . '">';
print '</td></tr>'."\n";


print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_TOWN">'.$langs->trans("CompanyTown").'</label></td><td>';
print $account->business_profile->support_address->city;
//print '<input name="MAIN_INFO_SOCIETE_TOWN" class="minwidth100" id="MAIN_INFO_SOCIETE_TOWN" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TOWN?$conf->global->MAIN_INFO_SOCIETE_TOWN:GETPOST("MAIN_INFO_SOCIETE_TOWN", 'nohtml')) . '">';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td class="maxwidthonsmartphone">';
print getCountry($account->business_profile->support_address->country);
//if (empty($country_selected)) $country_selected=substr($langs->defaultlang,-2);    // By default, country of localization
//print $form->select_country($mysoc->country_id, 'country_id');
//if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
print '</td></tr>'."\n";

if (!empty($account->business_profile->support_address->state)) {
print '<tr class="oddeven"><td><label for="state_id">'.$langs->trans("State").'</label></td><td class="maxwidthonsmartphone">';
print getState($account->business_profile->support_address->state);
//$state_id = 0;
//if (!empty($conf->global->MAIN_INFO_SOCIETE_STATE))
//{
//	$tmp = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
//	$state_id = $tmp[0];
//}
//$formcompany->select_departement($state_id, $mysoc->country_code, 'state_id');
print '</td></tr>'."\n";
}

print '<tr class="oddeven"><td><label for="phone">'.$langs->trans("Phone").'</label></td><td>';
print $account->business_profile->support_phone;
//print '<input name="tel" id="phone" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TEL).'">';
print '</td></tr>';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("EMail").'</label></td><td>';
print $account->business_profile->support_email;
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';
print '</td></tr>'."\n";

// Web
print '<tr class="oddeven"><td><label for="web">'.$langs->trans("SupportWeb").'</label></td><td>';
print $account->business_profile->support_url;
//print '<input name="web" id="web" class="minwidth300" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_WEB).'">';
print '</td></tr>';
print '</td></tr>'."\n";

// Web
print '<tr class="oddeven"><td><label for="web">'.$langs->trans("Web").'</label></td><td>';
print $account->business_profile->url;
//print '<input name="web" id="web" class="minwidth300" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_WEB).'">';
print '</td></tr>';
print '</td></tr>'."\n";

// Settings
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Display").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("DisplayName").'</label></td><td>';
print $account->settings->dashboard->display_name;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("Timezone").'</label></td><td>';
print $account->settings->dashboard->timezone;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("StatementDescriptor").'</label></td><td>';
print $account->settings->payments->statement_descriptor;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

if (!empty($account->settings->payments->statement_descriptor_kana)) {
print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("StatementDescriptorKana").'</label></td><td>';
print $account->settings->payments->statement_descriptor_kana;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";
}

if (!empty($account->settings->payments->statement_descriptor_kanji)) {
print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("StatementDescriptorKanji").'</label></td><td>';
print $account->settings->payments->statement_descriptor_kanji;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";
}

print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("PrimaryColor").'</label></td><td>';
print $account->settings->branding->primary_color;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

// Logo
print '<tr class="oddeven"><td><label for="logo">'.$form->textwithpicto($langs->trans("Logo"), 'png, jpg').'</label></td><td>';
//print \Stripe\FileLink::create([
//  'file' => $account->settings->branding->logo,
//]);
//print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini).'">';
//print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
//print '<input type="file" class="flat minwidth200" name="logo" id="logo" accept="image/*">';
//print '</td><td class="nocellnopadd right" valign="middle">';
//if (!empty($mysoc->logo_mini)) {
//	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removelogo">'.img_delete($langs->trans("Delete")).'</a>';
//	if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini)) {
//		print ' &nbsp; ';
//		print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini).'">';
//	}
//} else {
//	print '<img height="30" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
//}
//print '</td></tr></table>';
print '</td></tr>';

// Logo (squarred)
print '<tr class="oddeven"><td><label for="logo_squarred">'.$form->textwithpicto($langs->trans("LogoSquarred"), 'png, jpg').'</label></td><td>';
print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
//print '<input type="file" class="flat minwidth200" name="logo_squarred" id="logo_squarred" accept="image/*">';
print '</td><td class="nocellnopadd right" valign="middle">';
//if (!empty($mysoc->logo_squarred_mini)) {
//	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removelogosquarred">'.img_delete($langs->trans("Delete")).'</a>';
//	if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_mini)) {
//		print ' &nbsp; ';
//		print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini).'">';
//	}
//} else {
//	print '<img height="30" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
//}
print '</td></tr></table>';
print '</td></tr>';

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Settings").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("Capabilities").'</label></td><td>';
if ($account->capabilities->card_payments) print $langs->trans("card_payments").': '.dolGetStatus($account->capabilities->card_payments, $langs->trans(ucfirst($account->capabilities->card_payments)), '', ($account->capabilities->card_payments == 'active') ? 'status4' : 'status1', 5).'<br>';
if ($account->capabilities->legacy_payments) print $langs->trans("legacy_payments").': '.dolGetStatus($account->capabilities->legacy_payments, $langs->trans(ucfirst($account->capabilities->legacy_payments)), '', ($account->capabilities->legacy_payments == 'active') ? 'status4' : 'status1', 5).'<br>';
if ($account->capabilities->platform_payments) print $langs->trans("platform_payments").': '.dolGetStatus($account->capabilities->platform_payments, $langs->trans(ucfirst($account->capabilities->platform_payments)), '', ($account->capabilities->platform_payments == 'active') ? 'status4' : 'status1', 5);
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("Charges").'</label></td><td>';
print dolGetStatus($account->charges_enabled, !empty($account->charges_enabled) ? $langs->trans("Active") : $langs->trans("Inactive"), '', ($account->charges_enabled) ? 'status4' : 'status0', 5);
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("Payouts").'</label></td><td>';
print dolGetStatus($account->payouts_enabled, !empty($account->payouts_enabled) ? $langs->trans("Active") : $langs->trans("Inactive"), '', ($account->payouts_enabled) ? 'status4' : 'status0', 5);
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("Details").'</label></td><td>';
print dolGetStatus($account->details_submitted, !empty($account->details_submitted) ? $langs->trans("Completed") : $langs->trans("Pending"), '', ($account->details_submitted) ? 'status4' : 'status0', 5);
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '</table>';
} elseif ($account->type == 'custom') {
//print $account;

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Account").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// Company
print '<tr class="oddeven"><td class="fieldrequired"><label for="name">'.$langs->trans("Name").'</label></td><td>';
print $account->business_profile->name;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

// Company
print '<tr class="oddeven"><td><label for="name">'.$langs->trans("Type").'</label></td><td>';
print $account->type;
print " - ".$langs->trans($account->business_type);
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="name">'.$langs->trans("MerchantCategoryCode").'</label></td><td>';
print $langs->getLabelFromKey($db, $account->business_profile->mcc, 'c_merchantcategorycodes', 'code', 'label');
print " (".$account->business_profile->mcc.")";
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("EMail").'</label></td><td>';
print $account->email;
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td class="maxwidthonsmartphone">';
print getCountry($account->country);
//if (empty($country_selected)) $country_selected=substr($langs->defaultlang,-2);    // By default, country of localization
//print $form->select_country($mysoc->country_id, 'country_id');
//if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="currency">'.$langs->trans("CompanyCurrency").'</label></td><td>';  
print currency_name(strtoupper($account->default_currency), 1);
//print $form->selectCurrency($conf->currency, "currency");
print '</td></tr>'."\n"; 

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Company").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_TOWN">'.$langs->trans("CompanyName").'</label></td><td>';
print $account->company->name;
//print '<input name="MAIN_INFO_SOCIETE_TOWN" class="minwidth100" id="MAIN_INFO_SOCIETE_TOWN" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TOWN?$conf->global->MAIN_INFO_SOCIETE_TOWN:GETPOST("MAIN_INFO_SOCIETE_TOWN", 'nohtml')) . '">';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_ADDRESS">'.$langs->trans("CompanyAddress").'</label></td><td>';
print $account->company->address->line1;
print '<br>'.$account->company->address->line2;
//print '<textarea name="MAIN_INFO_SOCIETE_ADDRESS" id="MAIN_INFO_SOCIETE_ADDRESS" class="quatrevingtpercent" rows="'.ROWS_3.'">'. ($conf->global->MAIN_INFO_SOCIETE_ADDRESS?$conf->global->MAIN_INFO_SOCIETE_ADDRESS:GETPOST("MAIN_INFO_SOCIETE_ADDRESS", 'nohtml')) . '</textarea>;
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_ZIP">'.$langs->trans("CompanyZip").'</label></td><td>';
print $account->company->address->postal_code;
//print '<input class="minwidth100" name="MAIN_INFO_SOCIETE_ZIP" id="MAIN_INFO_SOCIETE_ZIP" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_ZIP?$conf->global->MAIN_INFO_SOCIETE_ZIP:GETPOST("MAIN_INFO_SOCIETE_ZIP", 'alpha')) . '">';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_TOWN">'.$langs->trans("CompanyTown").'</label></td><td>';
print $account->company->address->city;
//print '<input name="MAIN_INFO_SOCIETE_TOWN" class="minwidth100" id="MAIN_INFO_SOCIETE_TOWN" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TOWN?$conf->global->MAIN_INFO_SOCIETE_TOWN:GETPOST("MAIN_INFO_SOCIETE_TOWN", 'nohtml')) . '">';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td class="maxwidthonsmartphone">';
print getCountry($account->company->address->country);
//if (empty($country_selected)) $country_selected=substr($langs->defaultlang,-2);    // By default, country of localization
//print $form->select_country($mysoc->country_id, 'country_id');
//if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="state_id">'.$langs->trans("State").'</label></td><td class="maxwidthonsmartphone">';
if (!empty($account->company->address->state)) print getState($account->company->address->state);
//$state_id = 0;
//if (!empty($conf->global->MAIN_INFO_SOCIETE_STATE))
//{
//	$tmp = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
//	$state_id = $tmp[0];
//}
//$formcompany->select_departement($state_id, $mysoc->country_code, 'state_id');
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="phone">'.$langs->trans("Phone").'</label></td><td>';
print $account->company->phone;
//print '<input name="tel" id="phone" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TEL).'">';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="web">'.$langs->trans("Web").'</label></td><td>';
print $account->business_profile->url;
//print '<input name="web" id="web" class="minwidth300" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_WEB).'">';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="profid1">'.$langs->transcountry("ProfId1", $mysoc->country_code).'</label></td><td>';
print dolGetStatus($account->company->tax_id_provided, !empty($account->company->tax_id_provided) ? $langs->trans("Active") : $langs->trans("Inactive"), '', ($account->charges_enabled) ? 'status4' : 'status0', 5);
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="verification">'.$langs->trans("DocumentVerification").'</label></td><td>';
//print $account->company->verification->document->front;
print " ".dolGetStatus($account->company->verification->document->details, !empty($account->company->tax_id_provided) ? $langs->trans("Verified") : $langs->trans(ucfirst($account->company->verification->document->details_code)), '', empty($account->company->verification->document->details_code) ? 'status4' : 'status0', 5);
//print '<input name="web" id="web" class="minwidth300" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_WEB).'">';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="intra_vat">'.$langs->trans("VATIntra").'</label></td><td>';
print dolGetStatus($account->company->vat_id_provided, $account->company->vat_id_provided, '', ($account->company->vat_id_provided) ? 'status4' : 'status0', 3);
//print '<input name="web" id="web" class="minwidth300" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_WEB).'">';
print '</td></tr>'."\n";

// Settings
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Display").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("DisplayName").'</label></td><td>';
print $account->settings->dashboard->display_name;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("Timezone").'</label></td><td>';
print $account->settings->dashboard->timezone;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("StatementDescriptor").'</label></td><td>';
print $account->settings->payments->statement_descriptor;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

if (!empty($account->settings->payments->statement_descriptor_kana)) {
print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("StatementDescriptorKana").'</label></td><td>';
print $account->settings->payments->statement_descriptor_kana;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";
}

if (!empty($account->settings->payments->statement_descriptor_kanji)) {
print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("StatementDescriptorKanji").'</label></td><td>';
print $account->settings->payments->statement_descriptor_kanji;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";
}

print '<tr class="oddeven"><td ><label for="name">'.$langs->trans("PrimaryColor").'</label></td><td>';
print $account->settings->branding->primary_color;
//<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'>
print '</td></tr>'."\n";

// Logo
print '<tr class="oddeven"><td><label for="logo">'.$form->textwithpicto($langs->trans("Logo"), 'png, jpg').'</label></td><td>';
//print \Stripe\FileLink::create([
//  'file' => $account->settings->branding->logo,
//]);
//print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini).'">';
//print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
//print '<input type="file" class="flat minwidth200" name="logo" id="logo" accept="image/*">';
//print '</td><td class="nocellnopadd right" valign="middle">';
//if (!empty($mysoc->logo_mini)) {
//	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removelogo">'.img_delete($langs->trans("Delete")).'</a>';
//	if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini)) {
//		print ' &nbsp; ';
//		print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini).'">';
//	}
//} else {
//	print '<img height="30" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
//}
//print '</td></tr></table>';
print '</td></tr>';

// Logo (squarred)
print '<tr class="oddeven"><td><label for="logo_squarred">'.$form->textwithpicto($langs->trans("LogoSquarred"), 'png, jpg').'</label></td><td>';
print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
//print '<input type="file" class="flat minwidth200" name="logo_squarred" id="logo_squarred" accept="image/*">';
print '</td><td class="nocellnopadd right" valign="middle">';
//if (!empty($mysoc->logo_squarred_mini)) {
//	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removelogosquarred">'.img_delete($langs->trans("Delete")).'</a>';
//	if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_mini)) {
//		print ' &nbsp; ';
//		print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini).'">';
//	}
//} else {
//	print '<img height="30" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
//}
print '</td></tr></table>';
print '</td></tr>';

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Board").'</th><th></th></tr>'."\n";
$persons = \Stripe\Account::allPersons($stripeacc);
foreach ($persons as $person) {
print "<tr class='oddeven'><td colspan='2'>".$person->first_name." ".$person->last_name.", ".$person->relationship->title." ".dolGetStatus($person->verification->status, $langs->trans(ucfirst($person->verification->status)), '', ($person->verification->status == 'verified') ? 'status4' : 'status1', 5)."<br>";
print "".$person->address->line1;
if (!empty($person->address->line2)) print " ".$person->address->line2;
print ", ".$person->address->postal_code." ".$person->address->city.", ".getCountry($person->address->country);
if (!empty($person->address->state)) { print " - ".getState($person->address->state); }
print "<br>".$langs->trans("IDCardFront").': '.$person->verification->document->front." ".$person->verification->details;
print "<br>".$langs->trans("IDCardBack").': '.$person->verification->document->back." ".$person->verification->details;
if (!empty($person->verification->additional_document->front) && !empty($person->verification->additional_document->back)) print "<br>".$langs->trans("IDCardAdditionnal").': '.$person->verification->additional_document->front." ".$person->verification->additional_document->back." ".$person->verification->additional_document->details;
if (!empty($person->requirements->currently_due)) {
print "<br>".$langs->trans("Currently").':';
foreach ($person->requirements->currently_due as $currently) {
print '<li>'.$currently.'</li>';
} }
if (!empty($person->requirements->eventually_due)) {
print '<br>'.$langs->trans("Eventually").':';
foreach ($person->requirements->eventually_due as $eventually) {
print '<li>'.$eventually.'</li>';
} }
if (!empty($person->requirements->past_due)) {
print '<br>'.$langs->trans("Past").':';
foreach ($person->requirements->past_due as $past) {
print '<li>'.$past.'</li>';
} }
if (!empty($person->requirements->pending_verification)) {
print '<br>'.$langs->trans("Waiting").':';
foreach ($person->requirements->pending_verification as $pending) {
print '<li>'.$pending.'</li>';
}
}
print "<br>".$langs->trans("Representative").": ".dolGetStatus($person->relationship->representative, $account->relationship->representative, '', ($person->relationship->representative) ? 'status4' : 'status0', 3).", ";
print $langs->trans("Director").": ".dolGetStatus($person->relationship->director, $account->relationship->director, '', ($person->relationship->director) ? 'status4' : 'status0', 3).", ";
print $langs->trans("Executive").": ".dolGetStatus($person->relationship->executive, $account->relationship->executive, '', ($person->relationship->executive) ? 'status4' : 'status0', 3).", ";
print $langs->trans("Owner").": ".dolGetStatus($person->relationship->owner, $account->relationship->owner, '', ($person->relationship->owner) ? 'status4' : 'status0', 3);
if (!empty($person->relationship->percent_ownership)) print " ".$person->relationship->percent_ownership."%";
print "</td></tr>";
}

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("BankAccount").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n"; 
print '<tr><td colspan="2">'.$account->external_accounts.'</td></tr>';
foreach ($persons as $person) {


}

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Settings").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n"; 

print '<tr class="oddeven"><td><label for="capabilities">'.$langs->trans("Capabilities").'</label></td><td>';
if ($account->capabilities->card_payments) print $langs->trans("card_payments").': '.dolGetStatus($account->capabilities->card_payments, $langs->trans(ucfirst($account->capabilities->card_payments)), '', ($account->capabilities->card_payments == 'active') ? 'status4' : 'status1', 5).'<br>';
if ($account->capabilities->legacy_payments) print $langs->trans("legacy_payments").': '.dolGetStatus($account->capabilities->legacy_payments, $langs->trans(ucfirst($account->capabilities->legacy_payments)), '', ($account->capabilities->legacy_payments == 'active') ? 'status4' : 'status1', 5).'<br>';
if ($account->capabilities->platform_payments) print $langs->trans("platform_payments").': '.dolGetStatus($account->capabilities->platform_payments, $langs->trans(ucfirst($account->capabilities->platform_payments)), '', ($account->capabilities->platform_payments == 'active') ? 'status4' : 'status1', 5);
if ($account->capabilities->transfers) print $langs->trans("platform_payments").': '.dolGetStatus($account->capabilities->transfers, $langs->trans(ucfirst($account->capabilities->transfers)), '', ($account->capabilities->transfers == 'active') ? 'status4' : 'status1', 5);
print '</td></tr>';  

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("Charges").'</label></td><td>';
print dolGetStatus($account->charges_enabled, !empty($account->charges_enabled) ? $langs->trans("Active") : $langs->trans("Inactive"), '', ($account->charges_enabled) ? 'status4' : 'status0', 5);
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("Payouts").'</label></td><td>';
print dolGetStatus($account->payouts_enabled, !empty($account->payouts_enabled) ? $langs->trans("Active") : $langs->trans("Inactive"), '', ($account->payouts_enabled) ? 'status4' : 'status0', 5);
print ' '.$langs->trans("Schedule").": ".$langs->trans($account->settings->payouts->schedule->interval).": ";
if ($account->settings->payouts->schedule->interval == 'monthly') print $langs->trans("every")." ".$account->settings->payouts->schedule->monthly_anchor." ".$langs->trans("DayOfMonth");
if ($account->settings->payouts->schedule->interval == 'weekly') print $langs->trans("every")." ".$langs->trans($account->settings->payouts->schedule->weekly_anchor);
print ", ".$langs->trans("StatementDescriptor").": ".$account->settings->payouts->statement_descriptor;
print ", ".$langs->trans("TransfertDelay").": ".$account->settings->payouts->schedule->delay_days." ".$langs->trans("days");
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("Details").'</label></td><td>';
print dolGetStatus($account->details_submitted, !empty($account->details_submitted) ? $langs->trans("Completed") : $langs->trans("Pending"), '', ($account->details_submitted) ? 'status4' : 'status0', 5);
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("Requirements").'</label></td><td>';
if (empty($account->requirements->currently_due) && empty($account->requirements->eventually_due) && empty($account->requirements->past_due)) {
print dolGetStatus($langs->trans("Completed"), $langs->trans("Completed"), '', 'status4', 5);
} else {
if (!empty($account->requirements->current_deadline)) print $langs->trans("Deadline").": ".dol_print_date($account->requirements->current_deadline, 'dayhour');
if (!empty($account->requirements->currently_due)) {
print '<br>'.$langs->trans("Currently").':';
foreach ($account->requirements->currently_due as $currently) {
print '<li>'.$currently.'</li>';
} }
if (!empty($account->requirements->eventually_due)) {
print '<br>'.$langs->trans("Eventually").':';
foreach ($account->requirements->eventually_due as $eventually) {
print '<li>'.$eventually.'</li>';
} }
if (!empty($account->requirements->past_due)) {
print '<br>'.$langs->trans("Past").':';
foreach ($account->requirements->past_due as $past) {
print '<li>'.$past.'</li>';
} }
if (!empty($account->requirements->disabled_reason)) print $account->requirements->disabled_reason;
if (!empty($account->requirements->pending_verification)) {
print '<br>'.$langs->trans("Waiting").':';
foreach ($account->requirements->pending_verification as $pending) {
print '<li>'.$pending.'</li>';
}
}
}
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("DateCreation").'</label></td><td>';
print dol_print_date($account->created, 'dayhour');
//print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("ServicesAgreementAcceptance").'</label></td><td>';
print $langs->trans("Date").': '.dol_print_date($account->tos_acceptance->date, 'dayhour');
print '<br>'.$langs->trans("IP").': '.$account->tos_acceptance->ip;
print '<br>'.$langs->trans("UserAgent").': '.$account->tos_acceptance->user_agent;
print '</td></tr>';

print '</table>';

print '<div class="tabsAction">'."\n";
print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?&action=update" title="'.dol_escape_htmltag($langs->trans("Update")).'">'.$langs->trans("Update").'</a>';
print '</div>'."\n";

}

// End of page
llxFooter();
$db->close();
