<?php

use Io\Token\Proto\Common\Account\BankAccount;
use Io\Token\Proto\Common\Alias\Alias;
use Io\Token\Proto\Common\Transferinstructions\TransferEndpoint;
use Io\Token\Proto\Gateway\StoreTokenRequestRequest;
use Tokenio\TokenCluster;
use Tokenio\TokenEnvironment;
use Tokenio\TokenClientBuilder;
use Tokenio\Security\UnsecuredFileSystemKeyStore;
use Tokenio\Util\Strings;

class TokenSample
{
    private $tokenClient;
    /**
     * @var \Tokenio\Member
     */
    private $member;

    public function __construct()
    {
        $this->tokenClient = $this->initializeSDK();

        $this->member = $this->initializeMember();
    }

    private function initializeSDK()
    {
        $keyStoreDirectory = __DIR__ . '/../keys/';
        /** @var UnsecuredFileSystemKeyStore */
        $keyStore = new UnsecuredFileSystemKeyStore($keyStoreDirectory);
        $builder = new TokenClientBuilder();
        $builder->connectTo(TokenCluster::get(TokenEnvironment::SANDBOX));
        $builder->withKeyStore($keyStore);
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
        $directory = __DIR__ . '/../keys/';

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
        return $this->tokenClient->getMember($memberId);
    }

    private function createMember()
    {
        $email = 'msphp-' . Strings::generateNonce() . '+noverify@example.com';

        $alias = new Alias();
        $alias->setType(Alias\Type::EMAIL);
        $alias->setValue($email);

        $member =  $this->tokenClient->createMember($alias);
        $member->setProfile((new \Io\Token\Proto\Common\Member\Profile())->setDisplayNameFirst("Merchant Demo"));
        $member->setProfilePicture("image/png", file_get_contents("southside.png"));

        return $member;
    }

    /**
     * @return \Tokenio\Member
     */
    public function getMember()
    {
        return $this->member;
    }

    public function generateTokenRequestUrl($data, $csrfToken, $redirectUrl)
    {
        $iban = "DE16700222000072880129";
        $sepa = new BankAccount\Sepa();
        $sepa->setIban($iban);

        $destination = new BankAccount();
        $destination->setSepa($sepa);

        $amount = $data['amount'];
        $currency = $data['currency'];
        $description = $data['description'];

        $alias = $this->member->getFirstAlias();
        $transferEndpoint = new TransferEndpoint();
        $transferEndpoint->setAccount($destination);

        $request = \Tokenio\TokenRequest::transferTokenRequestBuilder($amount, $currency)
            ->setDescription($description)
            ->addDestination($transferEndpoint)
            ->setRefId(Strings::generateNonce())
            ->setToAlias($alias)
            ->setToMemberId($this->member->getMemberId())
            ->setRedirectUrl($redirectUrl)
            ->setCsrfToken($csrfToken)
            ->build();
        $requestId = $this->member->storeTokenRequest($request);

        return $this->tokenClient->generateTokenRequestUrl($requestId);
    }

    public function getTokenRequestCallback($callbackUrl, $csrfToken){
        return $this->tokenClient->parseTokenRequestCallbackUrl($callbackUrl, $csrfToken);
    }
}

$app->get('/', function ($request, $response, array $args) {
    $this->logger->info("Index.");
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/transfer', function ($request, $response, array $args) {
    $this->logger->info("Request transfer (redirect).");

    $csrf = Strings::generateNonce();
    setcookie("csrf_token", $csrf);
    $tokenIo = new TokenSample();
    $uri = $request->getUri();
    $data = $request->getQueryParams();
    $tokenRequestUrl = $tokenIo->generateTokenRequestUrl(
        $data,
        $csrf,
        $uri->getBaseUrl() . "/redeem"
    );
    return $response->withRedirect($tokenRequestUrl, 302);
});

$app->post('/transfer-popup', function ($request, $response, array $args) {
    $this->logger->info("Request transfer (popup).");

    $csrf = Strings::generateNonce();
    setcookie("csrf_token", $csrf);
    $tokenIo = new TokenSample();
    $uri = $request->getUri();
    return $tokenIo->generateTokenRequestUrl(
        $request->getParsedBody(),
        $csrf,
        $uri->getBaseUrl() . "/redeem-popup"
    );
});

$app->get('/redeem', function ($request, $response, array $args) {
    $this->logger->info("Request redeem (redirect).");

    $tokenSample = new TokenSample();
    $callback = $tokenSample->getTokenRequestCallback($request->getUri(), $request->getCookieParams()["csrf_token"]);
    $member = $tokenSample->getMember();
    $token = $member->getToken($callback->getTokenId());

    $transfer = $member->redeemToken($token);

    return 'Success! Redeemed transfer ' . $transfer->getId();
});

$app->get('/redeem-popup', function ($request, $response, array $args) {
    $this->logger->info("Request redeem (popup).");

    $tokenSample = new TokenSample();
    $callback = $tokenSample->getTokenRequestCallback($request->getUri(), $request->getCookieParams()["csrf_token"]);
    $member = $tokenSample->getMember();
    $token = $member->getToken($callback->getTokenId());

    $transfer = $member->redeemToken($token);

    return 'Success! Redeemed transfer ' . $transfer->getId();
});
