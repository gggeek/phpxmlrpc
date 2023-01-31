<?php

namespace App\Controller;

use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class ClientController extends AbstractController
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    #[Route('/getStateName/{stateNo}', name: 'getstatename', methods: ['GET'])]
    public function getStateName(int $stateNo): Response
    {
        $response = $this->client->send(new Request('examples.getStateName', [
            new Value($stateNo, Value::$xmlrpcInt)
        ]));
        if ($response->faultCode()) {
            throw new HttpException(502, $response->faultString());
        } else {
            return new Response("<html><body>State number $stateNo is: " . $response->value()->scalarVal() . '</body></html>');
        }
    }
}
