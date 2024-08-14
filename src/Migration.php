<?php

use HubSpot\Client\Crm\Contacts\ApiException;
use HubSpot\Client\Crm\Deals\ApiException as DealsException;

require("Contacts.php");
require("Deals.php");

// Import Contacts to HubSpot Account.
$row = 1;
$contactList = array();
if (($handle = fopen("contacts.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        //Create new contact by row
        if ($row == 1) {
            ++$row;
            continue;
        }
        $newContact($data, $contactList);
        ++$row;
    }
    fclose($handle);
    try {
        // Call HS API to create contacts batch.
        /* Initially contactList is a list of contacts to be created as batches in hubspot, 
        then used as a key-value pair containing name => id for setting associations later on. */
        $contactList = $createContacts($contactList);
    } catch (ApiException $th) {
        throw $th;
    }
}

// Import Deals to HubSpot account.
$row = 1;
$dealsList = array();
if (($handle = fopen("deals.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        //Create new deal by row
        if ($row == 1) {
            ++$row;
            continue;
        }
        $newDeal($data, $dealsList, $contactList);
        ++$row;
    }
    fclose($handle);
    try {
        // Call HS API to create deals batch
        $createDeals($dealsList);
    } catch (DealsException $th) {
        throw $th;
    }
}
