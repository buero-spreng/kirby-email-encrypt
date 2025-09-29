# Kirby Email Token

Server-signed, JS-resolved email links that keep addresses out of your HTML.
Keeps bots from collecting your email addresses from the frontend.

## Features

- Emails never appear in markup (no `mailto:` in the DOM).
- HMAC-signed tokens verified on your server.
- Small, robust JS that resolves tokens on click.
- API route at `/api/mailto`.

## Install

Copy the folder to `site/plugins/kb-email-token/`.


## Setup

### 1. Add a secret

#### Simple: directly in `site/config/config.php`:

```php
return [
  'kesabr.emailTokenKey' => 'change-me-to-a-long-random-secret',
];
```

#### Add a layer with `.env`:
To access environment variables, you need the dotenv plugin by Bruno Meilick https://plugins.getkirby.com/bnomei/dotenv

```php
'ready' => function () {
  return [
    'kesabr.emailTokenKey' => env('EMAIL_TOKEN_KEY') ?: 'change-me-to-a-long-random-secret',
  ];
},
```

### 2. JS validation on click

To actually validate the tokens, you need to add the JS to your site. You can do this by adding the following to your `<head>`:

```php
<?php snippet('email-token') ?>
```

## How it works (simplified)

The server signs the email address and generates a token that is embedded in the DOM instead of the actual email. When a user clicks the link, the JavaScript intercepts the click, sends the token to the `/api/mailto` endpoint for validation, and then opens the user's mail client with the resolved `mailto:` link. This way, the email address never appears in the HTML source until the user interacts with the link. Advanced scrapers would need to execute JavaScript and call the API to obtain the email addresses, making scraping significantly harder.

## Use in templates

### Method A: Helper (one line)

Use the helper function `emailTokenLink($email, $label, $attrs)` to generate the email link with a token.


Example Kirby template code:

```php
<?= emailTokenLink('info@example.com', 'Contact Us', ['class' => 'btn']) ?>
```

### Method B: Manual token + anchor

Generate a token manually with `email_token_make($email)` and render the anchor tag yourself:

```php
<?php $token = email_token_make('info@example.com') ?>
<a href="#" class="js-mailto" data-mailto-token="<?= $token ?>">E-Mail</a>
```

---------
Note: If your Kirby site is installed in a subfolder, set the API base URL by adding the following attribute to your `<html>` tag:

```html
<html data-kb-api-base="<?= kirby()->url('api') ?>">
```

This ensures the JS knows where to send token validation requests.
