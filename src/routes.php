<?php

use Io\Token\Proto\Common\Account\BankAccount;
use Io\Token\Proto\Common\Alias\Alias;
use Io\Token\Proto\Common\Transferinstructions\TransferEndpoint;
use Tokenio\TokenCluster;
use Tokenio\TokenEnvironment;
use Tokenio\TokenClientBuilder;
use Tokenio\TokenRequest;
use Tokenio\TokenRequestOptions;
use Tokenio\TransferTokenBuilder;
use Tokenio\Security\UnsecuredFileSystemKeyStore;
use Tokenio\Util\Strings;

class TokenSample
{
    const DEVELOPER_KEY = '4qY7lqQw8NOl9gng0ZHgT4xdiDqxqoGVutuZwrUYQsI';

    /**
     * @var UnsecuredFileSystemKeyStore
     */
    private $keyStore;

    private $keyStoreDirectory;
    private $tokenIO;
    /**
     * @var \Tokenio\Member
     */
    private $member;
    private $customizationID;

    public function __construct()
    {
        $this->keyStoreDirectory = __DIR__ . '/../keys/';
        $this->keyStore = new UnsecuredFileSystemKeyStore($this->keyStoreDirectory);

        $this->tokenIO = $this->initializeSDK();

        $this->member = $this->initializeMember();

        $payload = new \Io\Token\Proto\Common\Blob\Blob\Payload();
        $payload->setOwnerId($this->member->getMemberId())
            ->setType('image/png')
            ->setData(base64_decode("iVBORw0KGgoAAAANSUhEUgAAAD8AAAA/CAIAAADYPYeIAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA+9pVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTMyIDc5LjE1OTI4NCwgMjAxNi8wNC8xOS0xMzoxMzo0MCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ0MgMjAxNS41IChNYWNpbnRvc2gpIiB4bXA6Q3JlYXRlRGF0ZT0iMjAxNi0xMS0xMlQxMjoyNzozMC0wODowMCIgeG1wOk1vZGlmeURhdGU9IjIwMTYtMTEtMTJUMjA6Mjc6MzEtMDg6MDAiIHhtcDpNZXRhZGF0YURhdGU9IjIwMTYtMTEtMTJUMjA6Mjc6MzEtMDg6MDAiIGRjOmZvcm1hdD0iaW1hZ2UvcG5nIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkM3OUZERUQ0QTEzQTExRTZBRkIzOUMzQ0Y2RjM3RTg3IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkM3OUZERUQ1QTEzQTExRTZBRkIzOUMzQ0Y2RjM3RTg3Ij4gPHhtcE1NOkRlcml2ZWRGcm9t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"))
            ->setAccessMode(\Io\Token\Proto\Common\Blob\Blob\AccessMode::PBPUBLIC);
        $colors = [];
        $colors["color-primary"]= "#FF05A5F0";
        $this->customizationID = $this->member->createCustomization(
            "Northside",
            $payload,
            null,
            $colors);
    }

    private function initializeSDK()
    {
        $builder = new TokenClientBuilder();
        $builder->connectTo(TokenCluster::get(TokenEnvironment::SANDBOX));
        $builder->developerKey(self::DEVELOPER_KEY);
        $builder->withKeyStore($this->keyStore);
        return $builder->build();
    }

    private function initializeMember()
    {
        $memberId = $this->getFirstMemberId();
        if (!empty($memberId)) {
            return $this->loadMember($memberId);
        } else {
            return $this->createMember();
        }
    }

    /**
     * Finds the first member id in keystore
     *
     * @return string|null
     */
    private function getFirstMemberId()
    {
        $directory = $this->keyStoreDirectory;

        if (!file_exists($directory) || !is_dir($directory)) {
            return null;
        }

        $files = array_diff(scandir($directory), array('.', '..'));
        foreach ($files as $file) {
            $filePath = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                return str_replace('_', ':', $file);
            }
        }

        return null;
    }

    private function loadMember($memberId)
    {
        return $this->tokenIO->getMember($memberId);
    }

    private function createMember()
    {
        $email = 'msphp-' . Strings::generateNonce() . '+noverify@example.com';

        $alias = new Alias();
        $alias->setType(Alias\Type::EMAIL);
        $alias->setValue($email);

        return $this->tokenIO->createBusinessMember($alias);
    }

    /**
     * @return \Tokenio\Member
     */
    public function getMember()
    {
        return $this->member;
    }

    public function generateTokenRequestUrl($data)
    {
        $destinationData = json_decode($data['destination'], true);
        $sepa = new BankAccount\Sepa();
        $sepa->setIban($destinationData['sepa']['iban']);

        $destination = new BankAccount();
        $destination->setSepa($sepa);

        $amount = $data['amount'];
        $currency = $data['currency'];
        $description = $data['description'];

        $alias = $this->member->getFirstAlias();

        $tokenBuilder = new TransferTokenBuilder($this->member, $amount, $currency);
        $tokenBuilder->setDescription($description);

        $transferEndpoint = new TransferEndpoint();
        $transferEndpoint->setAccount($destination);
        $tokenBuilder->addDestination($transferEndpoint);

        $tokenBuilder->setToAlias($alias);
        $tokenBuilder->setToMemberId($this->member->getMemberId());

        $request = TokenRequest::builder($tokenBuilder->build())
            ->addOption(TokenRequestOptions::REDIRECT_URL, 'http://localhost:3000/redeem')
            ->setCustomizationId($this->customizationID)
            ->build();
        $requestId = $this->member->storeTokenRequest($request);

        return $this->tokenIO->generateTokenRequestUrl($requestId);
    }
}

$app->get('/', function ($request, $response, array $args) {
    $this->logger->info("Index.");
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/transfer', function ($request, $response, array $args) {
    $this->logger->info("Request transfer.");

    $tokenIo = new TokenSample();
    return $response->withRedirect($tokenIo->generateTokenRequestUrl($request->getParsedBody()), 302);
});

$app->get('/redeem', function ($request, $response, array $args) {
    $this->logger->info("Request redeem.");

    $tokenId = $request->getQueryParam('tokenId');
    if (empty($tokenId)) {
        return 'No token id found.';
    }

    $tokenIo = new TokenSample();
    $member = $tokenIo->getMember();
    $token = $member->getToken($tokenId);

    $transfer = $member->redeemToken($token);

    return 'Success! Redeemed transfer ' . $transfer->getId();
});
