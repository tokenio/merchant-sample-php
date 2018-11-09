## Token Merchant Checkout Sample: PHP

This sample code shows how to enable the Token Merchant Checkout
experience on a simple web server.
You can learn more about the Quick Checkout flow and relevant APIs at the
[Merchant Quick Checkout documentation](https://developer.token.io/merchant-checkout/).

To run this code, you need PHP 5.5.0 or later.

To run, `composer start`

This starts up a server.

The first time you run the server, it creates a new Member (Token user account).
It saves the Member's private keys in the `keys` directory.
In subsequent runs, the server uses this ID these keys to log the Member in.

The server operates in Token's Sandbox environment. This testing environment
lets you try out UI and payment flows without moving real money.

The server shows a web page at `localhost:9090`. The page has a checkout button.
Clicking the button starts the Token merchant payment flow.
The server handles endorsed payments by redeeming tokens.

Test by going to `localhost:9090`.
You can't get far until you create a customer member as described at the
[Merchant Quick Checkout documentation](https://developer.token.io/merchant-checkout/).

This code uses a publicly-known developer key (the devKey line in the
initializeSDK method). This normally works, but don't be surprised if
it's sometimes rate-limited or disabled. If your organization will do
more Token development and doesn't already have a developer key, contact
Token to get one.

### Troubleshooting

If anything goes wrong, try to clear your browser's cache before retest.
