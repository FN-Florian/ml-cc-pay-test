<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Vertragsabschluss Test</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    </head>
    <body>
        <div class='container'>
            <?php
            $tenant = "";
            if(isset($_GET['tenant']))
            {
                $tenant = $_GET['tenant'];
            }
            else
            {
                $tenant = "";
            }

            $studioid = 0;  
            if(isset($_GET['studioid']))
            {
                $studioid = $_GET['studioid'];
            }
            else
            {
                $studioid = 0;
            }

            if(isset($_ENV['ADYEN_CLIENT_KEY']))
            {
                $clientKey = $_ENV['ADYEN_CLIENT_KEY'];
            }
            else
            {
                $clientKey = "";
            }




            if($tenant == "" || $studioid == 0)
            {
                echo "Error: Tenant or Studio ID not set!";
                
                ?>
                <form method="get" action="index.php">
                    <div class="mb-3">
                        <label for="tenant" class="form-label">Tenant</label>
                        <input type="text" class="form-control" id="tenant" name="tenant">
                    </div>
                    <div class="mb-3">
                        <label for="studioid" class="form-label">Studio ID</label>
                        <input type="text" class="form-control" id="studioid" name="studioid">
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>

                <?php
            }
            else
            {
                // Tarif Konfigurationen
                $rateBundleID   = 5198834930;
                $TermID         = 5198840140;
                $creditCardToken= "";


                echo "Tenant: " . $tenant . "<br>";
                echo "Studio ID: " . $studioid . "<br>";

                $studioInfo = file_get_contents('https://'.$tenant.'.api.magicline.com/connect/v2/studio/'.$studioid);
                $studioData = json_decode($studioInfo, true);

                echo "
                <br><br>
                <h2>Kreditkarten Vertragsabschluss - ".$studioData['studioName']."</h2>";
                                
                $paymentMethods = file_get_contents('https://'.$tenant.'.api.magicline.com/connect/v2/creditcard/tokenization/payment-methods?studioId='.$studioid.'&countryCode='.$studioData['address']['countryCodeAlpha2'].'&locale=de_DE');
                $paymentMethodsData = json_decode($paymentMethods, true);

                var_dump($paymentMethodsData);




                $data = array();
                $data = [
                    'studioId' => $studioid,
                    'contract' => array(
                        'rateBundleTermId'  => $TermID,
                        'startDate'         => date('Y-m-d')
                        ),
                    'customer' => array(
                        'firstname'     => 'Max',
                        'lastname'      => 'Mustermann',
                        'dateOfBirth'   => '1990-01-01',
                        'email'         => 'ninow@ronmiller.de',
                        'street'        => 'Musterstrasse',
                        'houseNumber'   => '1',
                        'city'          => 'Musterstadt',
                        'zipCode'       => '12345',
                        'countryCode'   => 'DE',
                        'paymentChoice' => 'CREDIT_CARD',
                        'telephone_mobile'=> '0123456789',
                        'creditCard' => array(
                            'tokenizationReference' => $creditCardToken
                            )
                        )
                ];
                
                ?>
                <div id='component-container'></div>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const paymentMethodsResponse = <?php echo json_encode($paymentMethodsData); ?>;
                        handlePaymentMethodsResponse(paymentMethodsResponse);
                    });

                    
                    function handlePaymentMethodsResponse(response) 
                    {
                        const configuration = {
                            paymentMethodsResponse: response,
                            clientKey: '<?php echo $clientKey ?>',
                            locale: 'de_DE',
                            environment: 'live',
                            showPayButton: true,
                            paymentMethodsConfiguration: {
                                card: {
                                    hasHolderName: true,
                                    holderNameRequired: true,
                                    name: 'Credit or debit card',
                                    amount: {
                                        value: 0,
                                        currency: '<?php echo $studioData['currencyCode'] ?>'
                                    }
                                }
                            },
                            onSubmit: (state, component) => {
                                submit(state, component);
                            },
                            onAdditionalDetails: (state, component) => {
                                additionalDetails(state, component);
                            }
                        };
                        const checkout = new AdyenCheckout(configuration);
                        checkout.create('card').mount('#component-container');
                    }
                </script>


                <?php
            }

            ?>


            <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/4.5.0/adyen.js"
            integrity="sha384-Co94gRjtPsf3110lIIB8CaogV5Khwg8lcSh4fK5r1gzfWZHxaqpBXwEFjaFGcLaj"
            crossorigin="anonymous"></script>

            <link rel="stylesheet"
            href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/4.5.0/adyen.css"
            integrity="sha384-8EGo5meqBqlQ4MFf3nbQYD/onCuTfclYfNl3a5uQ1swwv0XXcTkda75dvjlYbZI8"
            crossorigin="anonymous">

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
        </div>
    </body>
</html>