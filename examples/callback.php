<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/azericard-config.php";

use \elnurxf\AzeriCard\AzeriCard;
use \elnurxf\AzeriCard\Exceptions\EmptyRequiredParametersException;
use \elnurxf\AzeriCard\Exceptions\FailedTransactionException;
use \elnurxf\AzeriCard\Exceptions\LogException;
use \elnurxf\AzeriCard\Exceptions\NoConfigException;
use \elnurxf\AzeriCard\Exceptions\NoParametersException;
use \elnurxf\AzeriCard\Exceptions\WrongHashException;

try {

    $azericard = new AzeriCard($config);
    $azericard->setLogPath(__DIR__ . DIRECTORY_SEPARATOR . 'logs'); // Comment to disable logs
    $azericard->setCallBackParameters($_POST);

    try {

        $azericard->handleCallback();

        // SUCCESS PAYMENT. PROCEED YOUR ORDER.

        try {

            // FINALIZE PAYMENT. IF USING AUTHORIZATION
            $azericard->completeCheckout();

        } catch (FailedTransactionException $e) {

            die('Failed transaction. Reason: ' . $e->getMessage() . ' Code: ' . $e->getCode());

        } catch (NoParametersException $e) {

            die('Error: callback parameters are empty');

        } catch (\Exception $e) {

            die('Error: cURL error occured:' . $e->getMessage());

        }

    } catch (FailedTransactionException $e) {

        die('Failed transaction. Reason: ' . $e->getMessage() . ' Code: ' . $e->getCode());

    } catch (NoParametersException $e) {

        die('Error: callback parameters are empty');

    } catch (EmptyRequiredParametersException $e) {

        die('Error: Not all required parameters are received. ' . $e->getMessage());

    } catch (WrongHashException $e) {

        die('Error: Hash mismatch');

    } catch (LogException $e) {

        die('Error: Can\'t save log file. Reason: ' . $e->getMessage());

    }

} catch (NoConfigException $e) {

    die('Error: Config are not set');

}
