<?php

require_once dirname(__FILE__) . '/common.inc.php';

class RemoteTest extends PaperciteTestCase {

    public function setUp() {
	parent::setUp();

	$cachedir = Papercite::getCacheDirectory();
        foreach(glob("$cachedir/*") as $file) {
          unlink($file);
        }

	if ( file_exists( $cachedir ) ) {
          rmdir( $cachedir );
	}
    }

    function testRemote() {
        $this->test();
    }

    function testRemoteCache() {
        $this->test();
    }

    function test() {
        $url = "https://gist.githubusercontent.com/bpiwowar/9793f4e2da48dfb34cde/raw/5fbff41218107aa9dcfab4fc53fe8e2b86ea8416/test.bib";
        $doc = $this->process_post("[bibtex ssl_check=true file=$url]");

        $xpath = new DOMXpath($doc);   

        $items = $xpath->evaluate("//ul[@class = 'papercite_bibliography']/li");
        $this->assertTrue($items->length == 1, "{$items->length} items detected - expected 1");
        $text = $items->item(0)->textContent;

        $this->assertRegExp("#Piwowarski#", $text);
    }

    public function testGetCachedShouldCreateCacheDirectory() {
	$cachedir = Papercite::getCacheDirectory();

	$p = new Papercite();
        $url = "https://gist.githubusercontent.com/bpiwowar/9793f4e2da48dfb34cde/raw/5fbff41218107aa9dcfab4fc53fe8e2b86ea8416/test.bib";
	$cached = $p->getCached( $url );

	$this->assertNotEmpty( $cached );
	$this->assertTrue( file_exists( $cachedir ) );
    }

    public function testGetCachedShouldCreateLocalCopyOfRemoteFile() {
	$cachedir = Papercite::getCacheDirectory();

	$p = new Papercite();
        $url = "https://gist.githubusercontent.com/bpiwowar/9793f4e2da48dfb34cde/raw/5fbff41218107aa9dcfab4fc53fe8e2b86ea8416/test.bib";
	$cached = $p->getCached( $url );

        $name = strtolower(preg_replace("@[/:]@","_",$url));

	$this->assertNotEmpty( $cached );
	$this->assertTrue( file_exists( $cached[0] ) );
	$this->assertTrue( file_exists( $cachedir . '/' . $name . '.bib' ) );
    }
}

