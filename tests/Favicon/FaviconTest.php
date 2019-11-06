<?php

namespace Favicon;

class FaviconTest extends \PHPUnit_Framework_TestCase {
    const DEFAULT_FAV_CHECK = 'favicon.ico';
    const TEST_LOGO_NAME = 'default.ico';
    private $RESOURCE_FAV_ICO;
    private $CACHE_TEST_DIR;
    const SANDBOX = 'tests/sandbox';
    const RESOURCES = 'tests/resources';

    public function setUp() {
        directory_create(self::SANDBOX, 0775);
        $this->RESOURCE_FAV_ICO = self::RESOURCES .'/'. self::TEST_LOGO_NAME;
        $this->CACHE_TEST_DIR = self::SANDBOX;
    }

    public function tearDown()
    {
        directory_delete(self::SANDBOX);
    }

    /**
    * @covers FaviconOld::__construct
    * @uses   FaviconOld
    */
    public function testUrlIsDefinedByConstructor() {
        $url = 'http://foo.bar';
        $args = array( 'url' => $url );
        $fav = new FaviconOld( $args );
        $this->assertEquals($url, $fav->getUrl());
    }
    
    /**
    * @covers FaviconOld::__construct
    * @covers FaviconOld::cache
    * @uses   FaviconOld
    */
    public function testInitEmptyCache() {
    	$fav = new FaviconOld();
    	$fav->cache();
    	
    	$this->assertTrue(is_writable($fav->getCacheDir()));
    	$this->assertEquals(604800, $fav->getCacheTimeout());
    }
    
    /**
    * @covers FaviconOld::__construct
    * @covers FaviconOld::cache
    * @uses   FaviconOld
    */
    public function testInitNotWritableCache() {
    	$dir = '/f0o/b@r';
    	
    	$fav = new FaviconOld();
    	$params = array(
    		'dir' => $dir,
    		);
    	$fav->cache($params);
    	
    	$this->assertEquals($dir, $fav->getCacheDir());
    	$this->assertFalse(is_writable($fav->getCacheDir()));
    	$this->assertEquals(604800, $fav->getCacheTimeout());
    }
    
    /**
    * @covers FaviconOld::__construct
    * @covers FaviconOld::cache
    * @uses   FaviconOld
    */
    public function testInitWritableCacheAndTimeout() {
    	$timeout = 1000;
    	
    	$fav = new FaviconOld();
    	$params = array(
    		'dir' => $this->CACHE_TEST_DIR,
    		'timeout' => $timeout,
    		);
    	$fav->cache($params);
    	
    	$this->assertEquals($this->CACHE_TEST_DIR, $fav->getCacheDir());
    	$this->assertTrue(is_writable($fav->getCacheDir()));
    	$this->assertEquals($timeout, $fav->getCacheTimeout());
    }
    
    /**
    * @covers FaviconOld::baseUrl
    * @uses   FaviconOld
    */
    public function testBaseFalseUrl() {
    	$fav = new FaviconOld();
    	
    	$notAnUrl = 'fgkljkdf';
    	$notPrefixedUrl = 'domain.tld';
    	$noHostUrl = 'http://';
    	$invalidPrefixUrl = 'ftp://domain.tld';
    	$emptyUrl = '';
    	
    	$this->assertEquals(FALSE, $fav->baseUrl($notAnUrl));
    	$this->assertEquals(FALSE, $fav->baseUrl($notPrefixedUrl));
    	$this->assertEquals(FALSE, $fav->baseUrl($noHostUrl));
    	$this->assertEquals(FALSE, $fav->baseUrl($invalidPrefixUrl));
    	$this->assertEquals(FALSE, $fav->baseUrl($emptyUrl));
    }
    
    /**
    * @covers FaviconOld::baseUrl
    * @uses   FaviconOld
    */
    public function testBaseUrlValid() {
    	$fav = new FaviconOld();
    	
    	$simpleUrl = 'http://domain.tld';
    	$simpleHttpsUrl = 'https://domain.tld';
    	$simpleUrlWithTraillingSlash = 'http://domain.tld/';
    	$simpleWithPort = 'http://domain.tld:8080';
    	$userWithoutPasswordUrl = 'http://user@domain.tld';
    	$userPasswordUrl = 'http://user:password@domain.tld';
    	$urlWithUnusedInfo = 'http://domain.tld/index.php?foo=bar&bar=foo#foobar';
    	$urlWithPath = 'http://domain.tld/my/super/path';
    	
    	$this->assertEquals(self::slash($simpleUrl), $fav->baseUrl($simpleUrl));
    	$this->assertEquals(self::slash($simpleHttpsUrl), $fav->baseUrl($simpleHttpsUrl));
    	$this->assertEquals(self::slash($simpleUrlWithTraillingSlash), $fav->baseUrl($simpleUrlWithTraillingSlash));
    	$this->assertEquals(self::slash($simpleWithPort), $fav->baseUrl($simpleWithPort));
    	$this->assertEquals(self::slash($userWithoutPasswordUrl), $fav->baseUrl($userWithoutPasswordUrl));
    	$this->assertEquals(self::slash($userPasswordUrl), $fav->baseUrl($userPasswordUrl));
    	$this->assertEquals(self::slash($simpleUrl), $fav->baseUrl($urlWithUnusedInfo));
    	$this->assertEquals(self::slash($simpleUrl), $fav->baseUrl($urlWithPath, false));
    	$this->assertEquals(self::slash($urlWithPath), $fav->baseUrl($urlWithPath, true));
    }
    
    /**
    * @covers FaviconOld::info
    * @uses   FaviconOld
    */
    public function testBlankInfo() {
        $fav = new FaviconOld();
        $this->assertFalse($fav->info(''));
    }
    
    /**
    * @covers FaviconOld::info
    * @uses   FaviconOld
    */
    public function testInfoOk() {
        $fav = new FaviconOld();
        $dataAccess = $this->getMock('Favicon\DataAccess');
        $header = array(
            0 => 'HTTP/1.1 200 OK',
        );
        $dataAccess->expects($this->once())->method('retrieveHeader')->will($this->returnValue($header));
        $fav->setDataAccess($dataAccess);
        
        $url = 'http://domain.tld';
        
        $res = $fav->info($url);
        $this->assertEquals($url, $res['url']);
        $this->assertEquals('200', $res['status']);
    }
    
    /**
    * @covers FaviconOld::info
    * @uses   FaviconOld
    */
    public function testInfoRedirect() {
        $dataAccess = $this->getMock('Favicon\DataAccess');
        $fav = new FaviconOld();
        $fav->setDataAccess($dataAccess);
        
        // Data
        $urlRedirect = 'http://redirected.domain.tld';
        $urlRedirect2 = 'http://redirected.domain.tld2';
        $url = 'http://domain.tld';
        $headerRedirect = array(
            0 => 'HTTP/1.0 302 Found',
            'location' => $urlRedirect,
        );
        $headerOk = array(0 => 'HTTP/1.1 200 OK');
        
        // Simple redirect
        $dataAccess->expects($this->at(0))->method('retrieveHeader')->will($this->returnValue($headerRedirect));
        $dataAccess->expects($this->at(1))->method('retrieveHeader')->will($this->returnValue($headerOk));

        $res = $fav->info($url);
        $this->assertEquals($urlRedirect, $res['url']);
        $this->assertEquals('200', $res['status']);

        // Redirect array
        $headerRedirect['location'] = array($urlRedirect, $urlRedirect2);
        $dataAccess->expects($this->at(0))->method('retrieveHeader')->will($this->returnValue($headerRedirect));
        $dataAccess->expects($this->at(1))->method('retrieveHeader')->will($this->returnValue($headerOk));
        $res = $fav->info($url);
        $this->assertEquals($urlRedirect2, $res['url']);
        $this->assertEquals('200', $res['status']);

        // Redirect loop
        $dataAccess->expects($this->exactly(5))->method('retrieveHeader')->will($this->returnValue($headerRedirect));
        $res = $fav->info($url);
        $this->assertEquals($urlRedirect2, $res['url']);
        $this->assertEquals('302', $res['status']);
    }

    /**
     * @covers FaviconOld::info
     * @uses   FaviconOld
     */
    public function testInfoFalse()
    {
        $dataAccess = $this->getMock('Favicon\DataAccess');
        $fav = new FaviconOld();
        $fav->setDataAccess($dataAccess);
        $url = 'http://domain.tld';

        $dataAccess->expects($this->at(0))->method('retrieveHeader')->will($this->returnValue(null));
        $this->assertFalse($fav->info($url));
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetExistingFavicon() {
        $url = 'http://domain.tld/';
        $path = 'sub/';
        
        $fav = new FaviconOld(array('url' => $url . $path));
        
        // No cache
        $fav->cache(array('dir' => $this->CACHE_TEST_DIR));
        
        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);

        // Header MOCK
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnCallback(array($this, 'headerExistingFav')));
        
        // Get from URL MOCK
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnCallback(array($this, 'contentExistingFav')));
        $this->assertEquals(self::slash($url . $path) . self::TEST_LOGO_NAME, $fav->get());
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetOriginalFavicon() {
        $url = 'http://domain.tld/original';
        $logo = 'default.ico';
        $fav = new FaviconOld(array('url' => $url));
        
        // No cache
        $fav->cache(array('dir' => $this->CACHE_TEST_DIR));
        
        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);
        
        // Header MOCK
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnCallback(array($this, 'headerOriginalFav')));
        
        // Get from URL MOCK
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnCallback(array($this, 'contentOriginalFav')));
        $this->assertEquals(self::slash($url) . $logo, $fav->get());
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetDefaultFavicon() {
        $url = 'http://domain.tld/';
        $fav = new FaviconOld(array('url' => $url));
        
        // No cache
        $fav->cache(array('dir' => $this->CACHE_TEST_DIR));
        
        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);
        
        // Header MOCK
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 200 KO')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue(file_get_contents($this->RESOURCE_FAV_ICO)));
        
        $this->assertEquals(self::slash($url) . self::DEFAULT_FAV_CHECK, $fav->get());
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetCachedFavicon() {
        $url = 'http://domaincache.tld/';
        $fav = new FaviconOld(array('url' => $url));
        
        // 30s
        $fav->cache(array('timeout' => 30, 'dir' => $this->CACHE_TEST_DIR));
        
        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);
        
        // Header MOCK
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 200 OK')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue(file_get_contents($this->RESOURCE_FAV_ICO)));
        
        // Save default favicon in cache
        $fav->get();
        
        $fav = new FaviconOld(array('url' => $url));
        $fav->cache(array('timeout' => 30, 'dir' => $this->CACHE_TEST_DIR));
        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 404 KO')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue('<head><crap></crap></head>'));
        
        $this->assertEquals(self::slash($url) . self::DEFAULT_FAV_CHECK, $fav->get());
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetFaviconEmptyUrl() {
    	$fav = new FaviconOld();
    	$this->assertFalse($fav->get());
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetNotFoundFavicon() {
    	$url = 'http://domain.tld';
        $fav = new FaviconOld(array('url' => $url));
        // No cache
        $fav->cache(array('dir' => $this->CACHE_TEST_DIR));
        
        $dataAccess = $this->getMock('Favicon\DataAccess');
        $fav->setDataAccess($dataAccess);
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 404 KO')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue('<head><crap></crap></head>'));
        
        $this->assertFalse($fav->get());
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetFalsePositive() {
    	$url = 'http://domain.tld';
        $fav = new FaviconOld(array('url' => $url));
        // No cache
        $fav->cache(array('dir' => $this->CACHE_TEST_DIR));
        
        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 200 OK')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue('<head><crap></crap></head>'));
        
        $this->assertFalse($fav->get());
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetNoHtmlHeader() {
    	$url = 'http://domain.tld/original';
        $fav = new FaviconOld(array('url' => $url));
        
        // No cache
        $fav->cache(array('dir' => $this->CACHE_TEST_DIR));
        
        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);
        
        // MOCK
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 404 KO')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue('<crap></crap>'));
        
        $this->assertFalse($fav->get());
    }
    
    /**
    * @covers FaviconOld::get
    * @uses   FaviconOld
    */
    public function testGetValidFavNoCacheSetup() {
    	$url = 'http://domain.tld';
        $fav = new FaviconOld(array('url' => $url));
        
        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);
        
        // MOCK
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 200 OK')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue(file_get_contents($this->RESOURCE_FAV_ICO)));
        
        $this->assertEquals(self::slash($url) . self::DEFAULT_FAV_CHECK, $fav->get());
        directory_clear(__DIR__ .'/../../resources/cache');
        touch(__DIR__ .'/../../resources/cache/.gitkeep');
    }

    public function testGetDownloadedFavPath()
    {
        $url = 'http://domain.tld';
        $fav = new FaviconOld(array('url' => $url));
        $fav->cache(array(
            'dir' => $this->CACHE_TEST_DIR
        ));

        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);

        // MOCK
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 200 OK')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue(file_get_contents($this->RESOURCE_FAV_ICO)));

        $expected = 'img'. md5('http://domain.tld');
        $this->assertEquals($expected, $fav->get('', FaviconDLType::DL_FILE_PATH));
    }

    public function testGetRawImageFav()
    {
        $url = 'http://domain.tld';
        $fav = new FaviconOld(array('url' => $url));
        $fav->cache(array(
            'dir' => $this->CACHE_TEST_DIR
        ));

        $dataAccess = $this->getMock('Favicon\DataAccess', array('retrieveHeader', 'retrieveUrl'));
        $fav->setDataAccess($dataAccess);

        // MOCK
        $dataAccess->expects($this->any())->method('retrieveHeader')->will($this->returnValue(array(0 => 'HTTP/1.1 200 OK')));
        $dataAccess->expects($this->any())->method('retrieveUrl')->will($this->returnValue(file_get_contents($this->RESOURCE_FAV_ICO)));

        $expected = file_get_contents(self::RESOURCES .'/'. self::TEST_LOGO_NAME);
        $this->assertEquals($expected, $fav->get('', FaviconDLType::RAW_IMAGE));
    }
    
    /**
     * Callback function for retrieveHeader in testGetExistingRootFavicon
     * If it checks default fav (favicon.ico), return 404
     * Return 200 while checking existing favicon
     **/
    public function headerExistingFav() {
        $headerOk = array(0 => 'HTTP/1.1 200 OK');
        $headerKo = array(0 => 'HTTP/1.1 404 KO');
        $args = func_get_args();
        
        if( strpos($args[0], self::DEFAULT_FAV_CHECK) !== false ) {
            return $headerKo;
        }
        return $headerOk;
    }
    
    /**
     * Callback function for contentExistingFav in testGetExistingRootFavicon
     * return valid header, or icon file content if url contain '.ico'.
     * Return 200 while checking existing favicon
     **/
    public function contentExistingFav() {
        $xml = '<head><link rel="icon" href="'. self::TEST_LOGO_NAME .'" /></head>';
        $ico = file_get_contents($this->RESOURCE_FAV_ICO);
        $args = func_get_args();
        
        if( strpos($args[0], '.ico') !== false ) {
            return $ico;
        }
        return $xml;
    }
    
    /**
     * Callback function for retrieveHeader in testGetOriginalFavicon
     * If it checks default fav (favicon.ico), return 404
     * Also return 404 if not testing original webdir (original/)
     * Return 200 while checking existing favicon in web subdir
     **/
    public function headerOriginalFav() {
        $headerOk = array(0 => 'HTTP/1.1 200 OK');
        $headerKo = array(0 => 'HTTP/1.1 404 KO');
        $args = func_get_args();
        
        if( strpos($args[0], 'original') === false || strpos($args[0], self::DEFAULT_FAV_CHECK) !== false ) {
            return $headerKo;
        }
        
        return $headerOk;
    }
    
    /**
     * Callback function for retrieveUrl in testGetOriginalFavicon
     * Return crap if it we're not in web sub directory
     * Return proper <head> otherwise
     * Return img for final check
     **/
    public function contentOriginalFav() {
        $logo = 'default.ico';
        $xmlOk = '<head><link rel="icon" href="'. $logo .'" /></head>';
        $xmlKo = '<head><crap></crap></head>';
        $ico = file_get_contents($this->RESOURCE_FAV_ICO);
        $args = func_get_args();
        
        if( strpos($args[0], '.ico') !== false ) {
            return $ico;
        }
        if( strpos($args[0], 'original') === false ) {
            return $xmlKo;
        }
        
        return $xmlOk;
    }
    
    public static function slash($url) {
    	return $url . ($url[strlen($url) - 1] == '/' ? '' : '/');
    }
}
