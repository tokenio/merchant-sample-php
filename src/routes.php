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
            ->setType('image/gif')
            ->setData("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")
            ->setAccessMode(\Io\Token\Proto\Common\Blob\Blob\AccessMode::PBPUBLIC);
        $this->customizationID = $this->member->createCustomization("Test Title", $payload);
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
