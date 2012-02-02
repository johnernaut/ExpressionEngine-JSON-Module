<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wc_city {

	public $return_data = '';
	public $rss_final = array();
	public $name;
	public $limit;
	public $cachedFeed;
	
	// -----------------------------------------
	// Constructor
	// -----------------------------------------
	
	function __construct()
	{
		$this->EE =& get_instance();
		
		if(($n = $this->EE->TMPL->fetch_param('name'))) {
			$this->name = $n;
			$this->cachedFeed = dirname(__FILE__) . "/cache/{$this->name}.json";
		} else {
			$this->cachedFeed = dirname(__FILE__) . "/cache/default.json";
		}
		
		if(($l = $this->EE->TMPL->fetch_param('limit'))) {
			$this->limit = intval($l);
		}
	}
	
	function entries()
	{
		$isCached = $this->checkCache();
		$this->parseFeed($isCached);
		
		$myOut = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $this->rss_final);
		
		return $myOut;
	}
	
	function getFeed()
	{
		if($this->name !== null) {
			$json = @file_get_contents('http://wc.onthecity.org/plaza/'.$this->name.'/events?format=json');
		} else {
			$json = @file_get_contents('http://wc.onthecity.org/plaza/events?format=json');
		}
		$feed = json_decode($json);
		$cachefile = fopen($this->cachedFeed, 'w');
		fwrite($cachefile, json_encode($feed));
		fclose($cachefile);
		
		return $feed;
	}
	
	function cachedFeed()
	{
		$json = @file_get_contents($this->cachedFeed);
		$feed = json_decode($json);
		
		return $feed;
	}
	
	function parseFeed($isCached)
	{
		if($isCached) {
			$feed = $this->cachedFeed();
		} else {
			$feed = $this->getFeed();
		}
		
		$rss = array();
		
		$count = 0;
		if($this->limit !== null) {
			$num = $this->limit;
		} else { $num = 50; }
		
		foreach($feed as $obj) {
			$item = $obj->global_event;
			
			$trunc = $this->truncate(strip_tags($item->body));
			$month = '<div class="month">' . date('M', strtotime($item->updated_at)) . '</div>';
			$year = '<div class="year">' . date('jS', strtotime($item->updated_at)) . '</div>';
			$rss_item = array(
				'title' => $item->title,
				'link' => $item->short_url,
				'author' => $item->user->long_name,
				'content' => $trunc,
				'date' => $month . $year,
				'type' => 'Woodlands Church'
			);
			array_push($rss, $rss_item);
		}
		
		while($count < $num) {
			if(isset($rss[$count])) {
				array_push($this->rss_final, $rss[$count]);
			}
			$count++;
		}
		
	}
	
	function checkCache()
	{
		if(@filemtime($this->cachedFeed) < (time() - 10800)) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	function truncate($str, $startPos = 0, $maxLength = 75) 
	{
		if(strlen($str) > $maxLength) {
			$excerpt   = substr($str, $startPos, $maxLength-3);
			$lastSpace = strrpos($excerpt, ' ');
			$excerpt   = substr($excerpt, 0, $lastSpace);
			$excerpt  .= '...';
		} else {
			$excerpt = $str;
		}
	
		return $excerpt;
	}	
}