<?php

namespace Favicon;

//use \Favicon\Favicon;

class FaviconTest extends \PHPUnit_Framework_TestCase {
    
    /**
    * @covers \Favicon\Favicon::__construct
    * @uses \Favicon\Favicon
    */
    public function testUrlIsDefinedByConstructor() {
        $url = 'http://foo.bar';
        $args = array( 'url' => $url );
        $fav = new \Favicon\Favicon( $args );
        $this->assertEquals($url, $fav->getUrl());
    }
    
    /**
    * @covers \Favicon\Favicon::__construct
    * @uses \Favicon\Favicon
    */
    public function testUrlAndDefaultIsDefinedByConstructor() {
        $url = 'http://foo.bar';
        $default = 'http://foo.bar/default.ico';
        $args = array( 
            'url' => $url,
            'defaultImg' => $default,
            );
        $fav = new \Favicon\Favicon( $args );
        $this->assertEquals($url, $fav->getUrl());
        $this->assertEquals($default, $fav->getDefaultImg());
    }
    
    /**
    * @covers \Favicon\Favicon::__construct
    * @covers \Favicon\Favicon::cache
    * @uses \Favicon\Favicon
    */
    public function testInitEmptyCache() {
    	$fav = new \Favicon\Favicon();
    	$fav->cache();
    	
    	$this->assertTrue(is_writable($fav->getCacheDir()));
    	$this->assertEquals(0, $fav->getCacheTimeout());
    }
    
    /**
    * @covers \Favicon\Favicon::__construct
    * @covers \Favicon\Favicon::cache
    * @uses \Favicon\Favicon
    */
    public function testInitNotWritableCache() {
    	$dir = '/f0o/b@r';
    	
    	$fav = new \Favicon\Favicon();
    	$params = array(
    		'dir' => $dir,
    		);
    	$fav->cache($params);
    	
    	$this->assertEquals($dir, $fav->getCacheDir());
    	$this->assertFalse(is_writable($fav->getCacheDir()));
    	$this->assertEquals(0, $fav->getCacheTimeout());
    }
    
    /**
    * @covers \Favicon\Favicon::__construct
    * @covers \Favicon\Favicon::cache
    * @uses \Favicon\Favicon
    */
    public function testInitWritableCacheAndTimeout() {
    	$dir = '.';
    	$timeout = 1000;
    	
    	$fav = new \Favicon\Favicon();
    	$params = array(
    		'dir' => $dir,
    		'timeout' => $timeout,
    		);
    	$fav->cache($params);
    	
    	$this->assertEquals($dir, $fav->getCacheDir());
    	$this->assertTrue(is_writable($fav->getCacheDir()));
    	$this->assertEquals($timeout, $fav->getCacheTimeout());
    }
}