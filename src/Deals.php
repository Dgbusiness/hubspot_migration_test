<?php

require 'vendor/autoload.php';

# Dotenv inports
use Dotenv\Dotenv;

# HubSpot Deals
use HubSpot\Factory;
use HubSpot\Client\Crm\Deals\ApiException;
use HubSpot\Client\Crm\Deals\Model\AssociationSpec;
use HubSpot\Client\Crm\Deals\Model\BatchInputSimplePublicObjectInputForCreate;
use HubSpot\Client\Crm\Deals\Model\PublicAssociationsForObject;
use HubSpot\Client\Crm\Deals\Model\PublicObjectId;
use HubSpot\Client\Crm\Deals\Model\SimplePublicObjectInputForCreate;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Deal to Contact
$associationSpec = new AssociationSpec([
    'association_category' => 'HUBSPOT_DEFINED',
    'association_type_id' => 3
]);

/**
 * Create new contact object and push it to the contactList array.
 */
$newDeal = function (array $dealObject, array &$dealList, array &$contactList) use ($associationSpec): void {
    $to = new PublicObjectId([
        'id' => $contactList[$dealObject[3]]
    ]);
    $publicAssociationsForObject = new PublicAssociationsForObject([
        'types' => [$associationSpec],
        'to' => $to
    ]);

    $dealstage = strtolower(str_replace(' ', '', $dealObject[1]));
    $dealstage = ($dealstage == 'pendingresponse' ? '227833480' : ($dealstage == 'pendingcontract' ? '227833481' : $dealstage));

    $properties = [
        'dealname' => $dealObject[0],
        'dealstage' => $dealstage,
        'closedate' => DateTime::createFromFormat('m-d-Y', $dealObject[2])->getTimestamp() * 1000,
    ];
    $simplePublicObjectInputForCreate = new SimplePublicObjectInputForCreate([
        'associations' => [$publicAssociationsForObject],
        'properties' => $properties
    ]);

    array_push($dealList, $simplePublicObjectInputForCreate);
};

/**
 * Use the HS API to create batches of deals.
 */
$createDeals = function (array &$dealList): array {
    try {

        $client = Factory::createWithAccessToken($_ENV['ACCESS_TOKEN']);
        $dealsFinalResult = array();
        foreach (array_chunk($dealList, 100) as $deals) {
            $batchInputSimplePublicObjectInputForCreate = new BatchInputSimplePublicObjectInputForCreate([
                'inputs' => $deals,
            ]);
            $apiResponse = $client->crm()->deals()->batchApi()->create($batchInputSimplePublicObjectInputForCreate);
            $dealsFinalResult = array_merge($dealsFinalResult, $apiResponse['results']);
        }
        return $dealsFinalResult;
    } catch (ApiException $e) {
        var_dump($e);
    }
};
