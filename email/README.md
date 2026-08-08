# Kodhe Email

Standalone Composer package of CodeIgniter's Email Library. A full-featured email library for sending emails using PHP's `mail()`, Sendmail, or SMTP.

## Installation

```bash
composer require kodhe/email


<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kodhe\Library\Email\Email;

echo "=== Kodhe Email Tests ===\n\n";

// Test 1: Basic initialization
echo "Test 1: Basic Initialization\n";
$email = new Email();
assert($email->useragent === 'CodeIgniter', 'Default useragent should be CodeIgniter');
assert($email->protocol === 'mail', 'Default protocol should be mail');
assert($email->mailtype === 'text', 'Default mailtype should be text');
assert($email->charset === 'UTF-8', 'Default charset should be UTF-8');
assert($email->priority === 3, 'Default priority should be 3');
assert($email->wordwrap === true, 'Default wordwrap should be true');
assert($email->wrapchars === 76, 'Default wrapchars should be 76');
echo "✓ Passed\n\n";

// Test 2: Initialize with config
echo "Test 2: Initialize with Config Array\n";
$config = [
    'protocol' => 'smtp',
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => 587,
    'smtp_user' => 'user@example.com',
    'smtp_pass' => 'password',
    'mailtype' => 'html',
    'charset' => 'UTF-8',
    'wordwrap' => false,
    'wrapchars' => 100,
    'priority' => 1,
    'newline' => "\r\n",
    'crlf' => "\r\n",
];
$email2 = new Email($config);
assert($email2->protocol === 'smtp', 'Protocol should be smtp');
assert($email2->smtp_host === 'smtp.example.com', 'SMTP host should be set');
assert($email2->smtp_port === 587, 'SMTP port should be 587');
assert($email2->smtp_user === 'user@example.com', 'SMTP user should be set');
assert($email2->smtp_pass === 'password', 'SMTP pass should be set');
assert($email2->mailtype === 'html', 'Mailtype should be html');
assert($email2->wordwrap === false, 'Wordwrap should be false');
assert($email2->priority === 1, 'Priority should be 1');
echo "✓ Passed\n\n";

// Test 3: Set From address
echo "Test 3: Set From Address\n";
$email3 = new Email();
$email3->from('sender@example.com', 'Sender Name');
echo "✓ From set\n\n";

// Test 4: Set From with Reply-To
echo "Test 4: Set From with Reply-To\n";
$email4 = new Email();
$email4->from('noreply@example.com', 'No Reply');
$email4->reply_to('support@example.com', 'Support Team');
echo "✓ From and Reply-To set\n\n";

// Test 5: Set Recipients
echo "Test 5: Set Recipients (To, CC, BCC)\n";
$email5 = new Email();
$email5->from('sender@example.com');
$email5->to('recipient1@example.com');
$email5->to('recipient2@example.com');
$email5->cc('carboncopy@example.com');
$email5->bcc('blindcopy@example.com');
echo "✓ Recipients set\n\n";

// Test 6: Email Validation
echo "Test 6: Email Validation\n";
$email6 = new Email();

// Valid emails
assert($email6->valid_email('user@example.com') === true, 'Valid email should pass');
assert($email6->valid_email('user.name+tag@example.co.uk') === true, 'Valid email with plus should pass');
assert($email6->valid_email('user@subdomain.example.com') === true, 'Valid subdomain email should pass');

// Invalid emails
assert($email6->valid_email('not-an-email') === false, 'Invalid email should fail');
assert($email6->valid_email('@example.com') === false, 'Email without username should fail');
assert($email6->valid_email('user@') === false, 'Email without domain should fail');

echo "✓ Email validation works\n\n";

// Test 7: Clean Email
echo "Test 7: Clean Email Addresses\n";
$email7 = new Email();

// Single email with name
$cleaned = $email7->clean_email('John Doe <john@example.com>');
assert($cleaned === 'john@example.com', 'Should extract email from name format');

// Array of emails
$cleaned_array = $email7->clean_email([
    'Alice <alice@example.com>',
    'bob@example.com',
    'Charlie <charlie@example.com>'
]);
assert($cleaned_array[0] === 'alice@example.com', 'Should extract first email');
assert($cleaned_array[1] === 'bob@example.com', 'Should keep plain email');
assert($cleaned_array[2] === 'charlie@example.com', 'Should extract third email');

echo "✓ Email cleaning works\n\n";

// Test 8: Subject Encoding
echo "Test 8: Subject Setting\n";
$email8 = new Email();
$email8->subject('Test Subject with UTF-8: Café résumé');
echo "✓ Subject set\n\n";

// Test 9: Message Body
echo "Test 9: Message Body Setting\n";
$email9 = new Email();
$email9->message("This is a test message.\nMultiple lines.\nSpecial chars: & < > \" '");
echo "✓ Message set\n\n";

// Test 10: Word Wrap
echo "Test 10: Word Wrap\n";
$email10 = new Email();
$long_text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.";
$wrapped = $email10->word_wrap($long_text, 50);

// Check that lines don't exceed 50 characters (allowing for newline)
$lines = explode("\n", $wrapped);
foreach ($lines as $line) {
    if (!empty($line)) {
        assert(strlen($line) <= 50 + strlen($email10->newline), 'Each line should be within wrap limit: ' . strlen($line));
    }
}
echo "✓ Word wrap works\n\n";

// Test 11: HTML Mailtype
echo "Test 11: HTML Mailtype\n";
$email11 = new Email();
$email11->set_mailtype('html');
assert($email11->mailtype === 'html', 'Mailtype should be html');

$html_message = '<html><body><h1>Hello World</h1><p>This is HTML content.</p></body></html>';
$email11->message($html_message);
echo "✓ HTML message set\n\n";

// Test 12: Alternative Message
echo "Test 12: Alternative Message\n";
$email12 = new Email();
$email12->set_mailtype('html');
$email12->set_alt_message('This is the plain text version of the HTML email.');
assert($email12->alt_message !== '', 'Alt message should be set');
echo "✓ Alternative message set\n\n";

// Test 13: Priority Setting
echo "Test 13: Priority Setting\n";
$email13 = new Email();

$email13->set_priority(1);
assert($email13->priority === 1, 'Priority should be 1 (Highest)');

$email13->set_priority(3);
assert($email13->priority === 3, 'Priority should be 3 (Normal)');

$email13->set_priority(5);
assert($email13->priority === 5, 'Priority should be 5 (Lowest)');

$email13->set_priority(10); // Invalid, should default to 3
assert($email13->priority === 3, 'Invalid priority should default to 3');

echo "✓ Priority setting works\n\n";

// Test 14: Clear Method
echo "Test 14: Clear Method\n";
$email14 = new Email();
$email14->from('sender@example.com');
$email14->to('recipient@example.com');
$email14->subject('Test Subject');
$email14->message('Test Message');
$email14->clear();

// After clear, these should be empty
$reflection = new \ReflectionClass($email14);
$prop = $reflection->getProperty('_subject');
$prop->setAccessible(true);
assert($prop->getValue($email14) === '', 'Subject should be empty after clear');

$prop2 = $reflection->getProperty('_body');
$prop2->setAccessible(true);
assert($prop2->getValue($email14) === '', 'Body should be empty after clear');

echo "✓ Clear method works\n\n";

// Test 15: Protocol Setting
echo "Test 15: Protocol Setting\n";
$email15 = new Email();

$email15->set_protocol('mail');
assert($email15->protocol === 'mail', 'Protocol should be mail');

$email15->set_protocol('sendmail');
assert($email15->protocol === 'sendmail', 'Protocol should be sendmail');

$email15->set_protocol('smtp');
assert($email15->protocol === 'smtp', 'Protocol should be smtp');

$email15->set_protocol('invalid');
assert($email15->protocol === 'mail', 'Invalid protocol should default to mail');

echo "✓ Protocol setting works\n\n";

// Test 16: Newline Setting
echo "Test 16: Newline Setting\n";
$email16 = new Email();

$email16->set_newline("\n");
assert($email16->newline === "\n", 'Newline should be LF');

$email16->set_newline("\r\n");
assert($email16->newline === "\r\n", 'Newline should be CRLF');

$email16->set_newline("invalid");
assert($email16->newline === "\n", 'Invalid newline should default to LF');

echo "✓ Newline setting works\n\n";

// Test 17: CRLF Setting
echo "Test 17: CRLF Setting\n";
$email17 = new Email();

$email17->set_crlf("\n");
assert($email17->crlf === "\n", 'CRLF should be LF');

$email17->set_crlf("\r\n");
assert($email17->crlf === "\r\n", 'CRLF should be CRLF');

$email17->set_crlf("invalid");
assert($email17->crlf === "\n", 'Invalid CRLF should default to LF');

echo "✓ CRLF setting works\n\n";

// Test 18: Debugger Output
echo "Test 18: Debugger\n";
$email18 = new Email();
$email18->from('sender@example.com');
$email18->to('recipient@example.com');
$email18->subject('Debug Test');
$email18->message('Debug message body.');

// Try to send (will likely fail without proper config, but we can check debugger)
@$email18->send();

$debug = $email18->print_debugger();
assert(is_string($debug), 'Debug output should be a string');
echo "✓ Debugger works\n\n";

// Test 19: Config with SMTP
echo "Test 19: SMTP Configuration\n";
$email19 = new Email([
    'protocol' => 'smtp',
    'smtp_host' => 'smtp.mailtrap.io',
    'smtp_port' => 2525,
    'smtp_user' => 'test_user',
    'smtp_pass' => 'test_pass',
    'smtp_crypto' => 'tls',
    'smtp_timeout' => 30,
    'smtp_keepalive' => true,
]);
assert($email19->protocol === 'smtp', 'Protocol should be smtp');
assert($email19->smtp_crypto === 'tls', 'Crypto should be tls');
assert($email19->smtp_timeout === 30, 'Timeout should be 30');
assert($email19->smtp_keepalive === true, 'Keepalive should be true');
echo "✓ SMTP configuration works\n\n";

// Test 20: Multiple methods chaining
echo "Test 20: Method Chaining\n";
$email20 = new Email();
$result = $email20
    ->from('sender@example.com', 'Sender')
    ->to('recipient@example.com')
    ->subject('Chained Test')
    ->message('Testing method chaining.')
    ->set_priority(2)
    ->set_mailtype('text');

assert($result instanceof Email, 'Method chaining should return Email instance');
echo "✓ Method chaining works\n\n";

// Test 21: Validate Email Array
echo "Test 21: Validate Email Array\n";
$email21 = new Email();

// Valid array
$result = $email21->validate_email(['user1@example.com', 'user2@example.com']);
assert($result === true, 'Valid email array should pass');

// Invalid array
$result = $email21->validate_email(['invalid-email', 'user@example.com']);
assert($result === false, 'Invalid email array should fail');

// Non-array
$result = $email21->validate_email('not-an-array');
assert($result === false, 'Non-array should fail');

echo "✓ Email array validation works\n\n";

// Test 22: BCC Batch Mode
echo "Test 22: BCC Batch Mode\n";
$email22 = new Email();
$email22->bcc('user1@example.com, user2@example.com', 1);
assert($email22->bcc_batch_mode === true, 'BCC batch mode should be enabled');
assert($email22->bcc_batch_size === 1, 'BCC batch size should be 1');
echo "✓ BCC batch mode works\n\n";

// Test 23: Address with Display Name
echo "Test 23: Complex From Address\n";
$email23 = new Email();
$email23->from('sender@example.com', 'Spécial Chars: José & María');
echo "✓ Complex name handled\n\n";

// Test 24: Empty config
echo "Test 24: Empty Config Initialization\n";
$email24 = new Email([]);
assert($email24 instanceof Email, 'Should create instance with empty config');
echo "✓ Empty config works\n\n";

// Test 25: Re-initialize
echo "Test 25: Re-initialize\n";
$email25 = new Email();
$email25->initialize([
    'protocol' => 'sendmail',
    'mailpath' => '/usr/sbin/sendmail',
    'wordwrap' => false,
]);
assert($email25->protocol === 'sendmail', 'Protocol should be sendmail');
assert($email25->wordwrap === false, 'Wordwrap should be false');
echo "✓ Re-initialize works\n\n";

echo "=== All Email Tests Passed ===\n";