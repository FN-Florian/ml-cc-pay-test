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
            $tenant = $_GET['tenant'] ?? '';
            $studioid = $_GET['studioid'] ?? 0;
            $clientKey = $_ENV['ADYEN_CLIENT_KEY'] ?? '';

            if (empty($tenant) || $studioid == 0) {
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
            } else {
                // Tarif Konfigurationen
                $rateBundleID = 5198834930;
                $TermID = 5198840140;

                echo "Tenant: " . htmlspecialchars($tenant) . "<br>";
                echo "Studio ID: " . htmlspecialchars($studioid) . "<br>";

                $studioInfo = file_get_contents('https://' . $tenant . '.api.magicline.com/connect/v2/studio/' . $studioid);
                $studioData = json_decode($studioInfo, true);

                echo "
                <br><br>
                <h2>Kreditkarten Vertragsabschluss - " . htmlspecialchars($studioData['studioName']) . "</h2>";

                $paymentMethods = file_get_contents('https://' . $tenant . '.api.magicline.com/connect/v2/creditcard/tokenization/payment-methods?studioId=' . $studioid . '&countryCode=' . $studioData['address']['countryCodeAlpha2'] . '&locale=de_DE');
                $paymentMethodsData = json_decode($paymentMethods, true);

                // Debug: Display the payment methods data
                echo "<pre>";
                var_dump($paymentMethodsData);
                echo "</pre>";

                ?>
                <div id='component-container'></div>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const paymentMethodsResponse = <?php echo json_encode($paymentMethodsData); ?>;
                        handlePaymentMethodsResponse(paymentMethodsResponse);
                    });

                    function handlePaymentMethodsResponse(response) {
                        const configuration = {
                            paymentMethodsResponse: response,
                            clientKey: '<?php echo htmlspecialchars($clientKey); ?>',
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
                                        currency: '<?php echo htmlspecialchars($studioData['currencyCode']); ?>'
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

                    function submit(state, component) {
                        if (state.isValid) {
                            const postData = {
                                paymentMethod: state.data.paymentMethod,
                                browserInfo: state.data.browserInfo,
                                studioId: <?php echo (int)$studioid; ?>,
                                returnUrl: "https://ml-cc-pay-test.ninow.eu/index.php?tenant=<?php echo urlencode($tenant); ?>&studioid=<?php echo (int)$studioid; ?>",
                                origin: window.location.origin
                            };
                            post("https://<?php echo htmlspecialchars($tenant); ?>.api.magicline.com/connect/v1/creditcard/tokenization/initiate", postData, function(data, status) {
                                handleInitiateResponse(data, status, component);
                            });
                        }
                    }

                    function additionalDetails(data, component) {
                        const postData = {
                            threeDSResult: data.data.details.threeDSResult,
                            redirectResult: null
                        };
                        post("https://<?php echo htmlspecialchars($tenant); ?>.api.magicline.com/connect/v1/creditcard/tokenization/" + tokenizationReference + "/complete", postData, function(response, status) {
                            const redirectData = {
                                threeDSResult: data.data.details.threeDSResult,
                                tokenizationReference: tokenizationReference
                            };
                            redirectToPhpPage(redirectData);
                        });
                    }

                    function handleInitiateResponse(data, status, component) {
                        tokenizationReference = data.reference;
                        if (data.action) {
                            if (data.action.type === "redirect") {
                                storeCurrentState();
                            }
                            component.handleAction(data.action);
                            const postData = {
                                tokenizationReference: tokenizationReference
                            };
                            redirectToPhpPage(postData);
                        }
                    }

                    function redirectToPhpPage(postData) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'https://ml-cc-pay-test.ninow.eu/response.php?tenant=<?php echo urlencode($tenant); ?>&studioid=<?php echo (int)$studioid; ?>';

                        for (const key in postData) {
                            if (postData.hasOwnProperty(key)) {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = key;
                                input.value = postData[key];
                                form.appendChild(input);
                            }
                        }

                        document.body.appendChild(form);
                        form.submit();
                    }
                </script>
                <?php
            }
            ?>
            <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/4.5.0/adyen.js" integrity="sha384-Co94gRjtPsf3110lIIB8CaogV5Khwg8lcSh4fK5r1gzfWZHxaqpBXwEFjaFGcLaj" crossorigin="anonymous"></script>
            <link rel="stylesheet" href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/4.5.0/adyen.css" integrity="sha384-8EGo5meqBqlQ4MFf3nbQYD/onCuTfclYfNl3a5uQ1swwv0XXcTkda75dvjlYbZI8" crossorigin="anonymous">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        </div>
    </body>
</html>
