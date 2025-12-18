<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class PassbookController extends Controller
{
    public function generateTestPass(Request $request)
    {
        if (!class_exists('Byte5\\PassGenerator')) {
            return response('PassGenerator package not installed', 500);
        }

        try {
            // Minimal pass definition for testing
            $definition = [
                "description"       => "description",
                "formatVersion"     => 1,
                "organizationName"  => "CoverManager",
                "passTypeIdentifier"=> "pass.com.covermanager.wallet",
                "serialNumber"      => "123456",
                "teamIdentifier"    => "H7TVGT2YV3",
                "foregroundColor"   => "rgb(99, 99, 99)",
                "backgroundColor"   => "rgb(212, 212, 212)",
                "barcode" => [
                    "message"   => "encodedmessageonQR",
                    "format"    => "PKBarcodeFormatQR",
                    "altText"   => "altextfortheQR",
                    "messageEncoding"=> "utf-8",
                ],
                "boardingPass" => [
                    "headerFields" => [
                        [
                            "key" => "destinationDate",
                            "label" => "Trip to: BCN-SANTS",
                            "value" => "15/09/2015"
                        ]
                    ],
                    "primaryFields" => [
                        [
                            "key" => "boardingTime",
                            "label" => "MURCIA",
                            "value" => "13:54",
                            "changeMessage" => "Boarding time has changed to %@"
                        ],
                        [
                            "key" => "destination",
                            "label" => "BCN-SANTS",
                            "value" => "21:09"
                        ]

                    ],
                    "secondaryFields" => [
                        [
                            "key" => "passenger",
                            "label" => "Passenger",
                            "value" => "J.DOE"
                        ],
                        [
                            "key" => "bookingref",
                            "label" => "Booking Reference",
                            "value" => "4ZK6FG"
                        ]
                    ],
                    "auxiliaryFields" => [
                        [
                            "key" => "train",
                            "label" => "Train TALGO",
                            "value" => "00264"
                        ],
                        [
                            "key" => "car",
                            "label" => "Car",
                            "value" => "009"
                        ],
                        [
                            "key" => "seat",
                            "label" => "Seat",
                            "value" => "04A"
                        ],
                        [
                            "key" => "classfront",
                            "label" => "Class",
                            "value" => "Tourist"
                        ]
                    ],
                    "backFields" => [
                        [
                            "key" => "ticketNumber",
                            "label" => "Ticket Number",
                            "value" => "7612800569875"
                        ], [
                            "key" => "passenger-name",
                            "label" => "Passenger",
                            "value" => "John Doe"
                        ], [
                            "key" => "classback",
                            "label" => "Class",
                            "value" => "Tourist"
                        ]
                    ],
                    "locations" => [
                        [
                            "latitude" => 37.97479,
                            "longitude" => -1.131522,
                            "relevantText" => "Departure station"
                        ]
                    ],
                    "transitType" => "PKTransitTypeTrain"
                ],
            ];

            // Create pass generator instance with a unique pass id and allow replacement
            $identifier = $definition['passTypeIdentifier'] ?? 'pass.com.example.test';
            $passId = ($definition['serialNumber'] ?? null) ?: ('pass_' . preg_replace('/[^a-z0-9_.-]/i', '_', $identifier) . '_' . time());
            $pass = new \Byte5\PassGenerator($passId, true);
            
            // Add required assets with absolute paths
            $pass->addAsset(base_path('resources/assets/wallet/images/favicon.png'), 'icon.png');
            $pass->addAsset(base_path('resources/assets/wallet/images/walletHeaderCM3.png'), 'logo.png');
            $pass->addAsset(base_path('resources/assets/wallet/images/walletHeaderCM3@2x.png'), 'logo@2x.png');

            
            $pass->setPassDefinition($definition);

            // If assets or certificate paths are configured via config or env,
            // PassGenerator will use them according to its own config.

            $pkpass = $pass->create();

            $headers = [
                'Content-Transfer-Encoding' => 'binary',
                'Content-Description' => 'File Transfer',
                'Content-Disposition' => 'attachment; filename="test.pkpass"',
                    'Content-Type' => \Byte5\PassGenerator::getPassMimeType(),
                'Pragma' => 'no-cache',
            ];

            return response($pkpass, 200, $headers);

        } catch (\Exception $e) {
            $certificatePath = config('passgenerator.certificate_store_path');
            $certificateExists = $certificatePath ? file_exists($certificatePath) : false;
            $wwdrPath = config('passgenerator.wwdr_certificate_path');
            $wwdrExists = $wwdrPath ? file_exists($wwdrPath) : false;
            
            $debugInfo = "\nDebug Info:\n";
            $debugInfo .= "Certificate Path: {$certificatePath}\n";
            $debugInfo .= "Certificate Exists: " . ($certificateExists ? 'Yes' : 'No') . "\n";
            $debugInfo .= "WWDR Path: {$wwdrPath}\n";
            $debugInfo .= "WWDR Exists: " . ($wwdrExists ? 'Yes' : 'No') . "\n";
            
            logger()->error('Pass generation failed: ' . $e->getMessage() . $debugInfo);
            return response('Pass generation failed: ' . $e->getMessage() . $debugInfo, 500);
        }
    }
}
