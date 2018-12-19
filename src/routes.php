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
    private $member;

    public function __construct()
    {
        $this->keyStoreDirectory = __DIR__ . '/../keys/';
        $this->keyStore = new UnsecuredFileSystemKeyStore($this->keyStoreDirectory);

        $this->tokenIO = $this->initializeSDK();
        $this->member = $this->initializeMember();
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
