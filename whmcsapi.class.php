<?php
/**
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU LesserGeneral Public License as published
    by the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    For support, please visit http://ultraunix.net
*/
/**
 * WHMCS API Class (php5)
 * 
 * A php class script to utilize WHMCS API features
 *
 * @author Amirul A. Ali <amirul@ultraunix.net>
 * @copyright Copyright (c) 2010, Amirul A. Ali
 *
 * @version 1.0 
 **/

/**
 * Include XMLparser by Adam A. Flynn
 */ 
include_once('xmlparser.php');

class WHMCSAPI 
{
	/**
	* Constructor. Putting credentials together
	*
	* @return string API Access details
	*/
	function __construct($api_user, $api_pass, $api_url, $xml = true)
	{
		// Construct Curl's postfields
		$this->api->url = $api_url;
		$this->api->postfields = array 
			(
				'username' => $api_user,
				'password' => md5($api_pass),
				'responsetype' => 'xml',
			);

		if (!$xml) unset($this->api->postfields['responsetype']);
	}
	
	/**
	* Get Client Details
	*
	* @param array email|clientid
	*/
	function getclientsdetails($param) 
	{
		$this->api->postfields = array_merge($this->api->postfields, array('action' => 'getclientsdetails'), $param);
		$this->fetch();
		
		// Client Details
		$totalresult = (count($this->fetch->client[0]->tagChildren) - 1);
		for($i = 0; $i <= $totalresult; $i++)
		{
			$tagName = $this->fetch->client[0]->tagChildren[$i]->tagName;
			$tagData = $this->fetch->client[0]->tagChildren[$i]->tagData;
			$this->data->getclientsdetails->$tagName = $tagData;
		}
	}
	
	/**
	* Get Client Password
	*
	* @param integer userid
	*/
	function getclientpassword($param) 
	{
		$this->api->postfields = array_merge($this->api->postfields, array('action' => 'getclientpassword', 'userid' => $param));
		$this->fetch();
		
		$tagName = $this->fetch->password[0]->tagName;
		$tagData = $this->fetch->password[0]->tagData;
		
		$this->data->getclientpassword->$tagName = $tagData;		
	}
	
	/**
	* Get Client Products
	*
	* @param integer clientid
	*/
	function getclientsproducts($param) 
	{
		$this->api->postfields = array_merge($this->api->postfields, array('action' => 'getclientsproducts', 'clientid' => $param));
		$this->fetch();
		
		$this->data->getclientsproducts->clientid = $this->fetch->clientid[0]->tagData;
		$this->data->getclientsproducts->total = $this->fetch->totalresults[0]->tagData;
		
		$totalresult = $this->data->getclientsproducts->total - 1;

		for($i = 0; $i <= $totalresult; $i++)
		{
			foreach($this->fetch->products[0]->product[$i]->tagChildren as $entry)
			{
				$this->data->getclientsproducts->product[$i][$entry->tagName] = $entry->tagData;
				
				// customfields entries
				if($entry->tagName === 'customfields')
				{
					foreach($entry->tagChildren as $customfields)
					{
						$this->data->getclientsproducts->product[$i]['customfields'][$customfields->tagName] = $customfields->tagData;
					}
				}				
			}
		}
	}
	
	/**
	* Get Tickets
	*
	* @param integer|string array clientid|limitstart|limitnum|deptid|status|subject (optional)
	*/
	function gettickets($param = '')
	{
		$this->api->postfields = array_merge($this->api->postfields, array('action' => 'gettickets'), $param);
		$this->fetch();

		$this->data->gettickets->result = $this->fetch->result[0]->tagData;
		$this->data->gettickets->total = $this->fetch->totalresults[0]->tagData;
		$this->data->gettickets->startnum = $this->fetch->startnumber[0]->tagData;
		
		$totalresult = (count($this->fetch->tickets[0]->tagChildren) - 1); //fix offset
		for($i = 0; $i <= $totalresult; $i++)
		{
			foreach($this->fetch->tickets[0]->ticket[$i]->tagChildren as $entry)
			{
				$this->data->gettickets->tickets[$i][$entry->tagName] = $entry->tagData;
			}
		}
	}

	/**
	* Get Client Ticket
	*
	* @param integer ticketid
	*/
	function getticket($param)
	{
		$this->api->postfields = array_merge($this->api->postfields, array('action' => 'getticket', 'ticketid' => $param));
		$this->fetch();

		print_r($this->fetch);
/*
		$this->postfields = array('action' => 'gettickets', 'ticketid' => $ticketid);
		$this->data();
		
		//Additional details
		$this->data->result = $this->xmldata->result[0]->tagData;
		$this->data->total = $this->xmldata->totalresults[0]->tagData;
		$this->data->startnum = $this->xmldata->startnumber[0]->tagData;
		$this->data->numreturned = $this->xmldata->numreturned[0]->tagData;

		// Put tickets into array
		$totalresult = (count($this->xmldata->tickets[0]->tagChildren) - 1);
		for($i = 0; $i <= $totalresult; $i++) {
			foreach($this->xmldata->tickets[0]->ticket[$i]->tagChildren as $entry) {
				$this->data->ticket[$i][$entry->tagName] = $entry->tagData;
			}
		}
		
		return $this->data;
*/
	}

	// Fetch data function
	function data() {

		// Exec curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->api->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->api->postfields);
		$data = curl_exec($ch);
		
		// XMLParser
		$parsed = new XMLParser($data);
		$parsed->Parse();		
		$this->xmldata = $parsed->document;

		return $this->xmldata;
	}	
	
	function fetch()
	{
		// Exec curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->api->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->api->postfields);
		$data = curl_exec($ch);
		
		// XMLParser
		$parsed = new XMLParser($data);
		$parsed->Parse();
		
		$this->data->result = $parsed->document->result[0]->tagData;

		// Report error
		if($this->data->result === 'error')
		{
			$this->data->message = $parsed->document->message[0]->tagData;
			echo '<h1>'.$this->data->result.': '.$this->data->message.'</h1>';
			die;
		}
		
		// If no error append into object
		else 
		{
			$this->fetch = $parsed->document;
		}
	}
}
