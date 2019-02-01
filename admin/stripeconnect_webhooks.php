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
 *	\file       stripeconnect/admin/stripeconnect_webhooks.php
 *	\ingroup    stripeconnect
 *	\brief      Page to setup webhooks
 */

require '../../../main.inc.php';
dol_include_once('/stripeconnect/lib/stripeconnect.lib.php');
dol_include_once('/multicompany/class/dao_multicompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

/*
 *	View
 */

$form=new Form($db);

llxHeader('',$langs->trans("StripeConnectSetup"));



require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
$stripe=new Stripe($db);
print \Stripe\WebhookEndpoint::all();

llxFooter();
$db->close();