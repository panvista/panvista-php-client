<?php
/**
 * Copyright 2013 Panvista Corp.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Panvista' . DIRECTORY_SEPARATOR . 'Client.php';

class ClientTest extends PHPUnit_Framework_TestCase
{
    const clientToken  = 'CLIENT_TOKEN';
    const clientSecret = 'CLIENT_SECRET';

    public function testSetup()
    {
        $client = new Panvista\Client(self::clientToken, self::clientSecret);
        $this->assertEquals(self::clientToken, $client->getClientToken());
        $this->assertEquals(self::clientSecret, $client->getClientSecret());
    }

    public function testValidCall()
    {
        $stub = $this->getMockBuilder('\Panvista\Client')
                     ->setConstructorArgs(array(self::clientToken, self::clientSecret))
                     ->setMethods(array('_sendRequest'))
                     ->getMock();

        $stub->expects($this->any())
             ->method('_sendRequest')
             ->will($this->returnValue(array(200, json_encode(array('success' => true)))));

        $result = $stub->call('/test/endpoint/');
        $this->assertTrue($result['success']);
    }

    /**
     * @expectedException \Panvista\ServiceInactive
     */
    public function testServiceInactiveCall()
    {
        $stub = $this->getMockBuilder('\Panvista\Client')
                     ->setConstructorArgs(array(self::clientToken, self::clientSecret))
                     ->setMethods(array('_sendRequest'))
                     ->getMock();

        $stub->expects($this->any())
             ->method('_sendRequest')
             ->will($this->returnValue(array(503, json_encode(array('detail' => 'Service Inactive')))));

        $stub->call('/test/endpoint/');
    }

    /**
     * @expectedException \Panvista\AuthenticationFailed
     */
    public function testAuthenticationFailedCall()
    {
        $stub = $this->getMockBuilder('\Panvista\Client')
                     ->setConstructorArgs(array(self::clientToken, self::clientSecret))
                     ->setMethods(array('_sendRequest'))
                     ->getMock();

        $stub->expects($this->any())
             ->method('_sendRequest')
             ->will($this->returnValue(array(403, json_encode(array('detail' => 'Authentication Failed')))));

        $stub->call('/test/endpoint/');
    }

    /**
     * @expectedException \Panvista\BadRequest
     */
    public function testBadRequestCall()
    {
        $stub = $this->getMockBuilder('\Panvista\Client')
                     ->setConstructorArgs(array(self::clientToken, self::clientSecret))
                     ->setMethods(array('_sendRequest'))
                     ->getMock();

        $stub->expects($this->any())
             ->method('_sendRequest')
             ->will($this->returnValue(array(400, json_encode(array('errors' => array('Please Enter in a title'))))));

        $stub->call('/test/endpoint/');
    }

    /**
     * @expectedException \Panvista\NotFound
     */
    public function testNotFoundCall()
    {
        $stub = $this->getMockBuilder('\Panvista\Client')
                     ->setConstructorArgs(array(self::clientToken, self::clientSecret))
                     ->setMethods(array('_sendRequest'))
                     ->getMock();

        $stub->expects($this->any())
             ->method('_sendRequest')
             ->will($this->returnValue(array(404, json_encode(array('detail' => 'Not Found')))));

        $stub->call('/test/endpoint/');
    }

    /**
     * @expectedException \Panvista\Exception
     */
    public function testInvalidCall()
    {
        $stub = $this->getMockBuilder('\Panvista\Client')
                     ->setConstructorArgs(array(self::clientToken, self::clientSecret))
                     ->setMethods(array('_sendRequest'))
                     ->getMock();

        $stub->expects($this->any())
             ->method('_sendRequest')
             ->will($this->returnValue(array(500, json_encode(array('detail' => 'Unknown Error')))));

        $stub->call('/test/endpoint/');
    }

    public function testRequestUrl()
    {
        $client = new Panvista\Client(self::clientToken, self::clientSecret);
        $client->setApiUrl('http://test.com');
        $this->assertEquals('http://test.com', $client->getApiUrl());
        $this->assertEquals(7, stripos($client->getRequestUrl('/test/endpoint/', 'GET'), 'test.com'));
    }
}