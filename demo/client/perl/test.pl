#!/usr/local/bin/perl

use Frontier::Client;

my $serverURL='http://localhost/demo/server/server.php';

# try the simplest example

my $client = Frontier::Client->new(
    'url' => $serverURL, 'debug' => 0, 'encoding' => 'iso-8859-1'
);
my $resp = $client->call("examples.getStateName", 32);

print "Got '${resp}'\n";

# test echoing of characters works fine

$resp = $client->call("examples.echo", 'Three "blind" mice - ' . "See 'how' they run");
print $resp . "\n";

# test name and age example. this exercises structs and arrays

$resp = $client->call("examples.sortByAge",
    [
        { 'name' => 'Dave', 'age' => 35},
        { 'name' => 'Edd', 'age' => 45 },
        { 'name' => 'Fred', 'age' => 23 },
        { 'name' => 'Barney', 'age' => 36 }
    ]
);

my $e;
foreach $e (@$resp) {
    print $$e{'name'} . ", " . $$e{'age'} . "\n";
}

# test base64

$resp = $client->call("examples.decode64",
    $client->base64("TWFyeSBoYWQgYSBsaXR0bGUgbGFtYiBTaGUgdGllZCBpdCB0byBhIHB5bG9u")
);

print $resp . "\n";
