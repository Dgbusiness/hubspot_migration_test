<?php

require 'vendor/autoload.php';

# Dotenv inports
use Dotenv\Dotenv;

# HubSpot Contacts
use HubSpot\Factory;
use HubSpot\Client\Crm\Contacts\ApiException;
use HubSpot\Client\Crm\Contacts\Model\BatchInputSimplePublicObjectInputForCreate;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInputForCreate;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

/**
 * Create new contact object and push it to the contactList array.
 */
$newContact = function (array $contactObject, array &$contactList): void {
    $properties = [
        'firstname' => $contactObject[0],
        'lastname' => $contactObject[1],
        'email' => trim($contactObject[2]),
        'is_customer' => filter_var($contactObject[3], FILTER_VALIDATE_BOOLEAN),
        'last_purchase_date' => DateTime::createFromFormat('m-d-Y',$contactObject[4])->getTimestamp()*1000,
        'unique_id' => hash('sha256', $contactObject[0] . $contactObject[1] . $contactObject[2])
    ];
    $simplePublicObjectInputForCreate = new SimplePublicObjectInputForCreate([
        'properties' => $properties
    ]);

    array_push($contactList, $simplePublicObjectInputForCreate);
};

/**
 * Parse the HS API result into a key-value pair array using name and id.
 */
$contactsParser = function (array $contactsResult): array {

    $contactsParsedResult = array();

    foreach ($contactsResult as $contact) {
        $contactsParsedResult[$contact['properties']['firstname']] = $contact['id'];
    }

    return $contactsParsedResult;
};

/**
 * Use the HS API to create batches of contacts.
 */
$createContacts = function (array &$contactList) use ($contactsParser): array {
    try {

        $client = Factory::createWithAccessToken($_ENV['ACCESS_TOKEN']);
        $contactsFinalParser = array();
        foreach (array_chunk($contactList, 100) as $contacts) {
            $batchInputSimplePublicObjectInputForCreate = new BatchInputSimplePublicObjectInputForCreate([
                'inputs' => $contacts,
            ]);
            $apiResponse = $client->crm()->contacts()->batchApi()->create($batchInputSimplePublicObjectInputForCreate);
            $contactsFinalParser = array_merge($contactsFinalParser, $contactsParser($apiResponse['results']));
        }
        return $contactsFinalParser;
    } catch (ApiException $e) {
        var_dump($e);
    }
};
