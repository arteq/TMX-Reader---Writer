<?php
/**
 * UNIT test for TMX reader/writer
 *
 * @author 		Artur Grącki <arteq@arteq.org>
 * @copyright 	Copyright (c) 2019. All rights reserved.
 */

use PHPUnit\Framework\TestCase;
use ArteQ\CSX\Tmx;

class TmxTest extends TestCase
{
	private $tmx;

	/* ====================================================================== */
	
	public function setUp()
	{
		$tmx = new Tmx(__DIR__.'/test.tmx', $create = false);
		$this->tmx = $tmx;
	}

	/* ====================================================================== */
	
	public function testCanReadExisting()
	{
		$data = $this->tmx->get();

		$this->assertInternalType('array', $data);
		$this->assertCount(2, $data);

		$this->assertArrayHasKey('test1', $data);
		$this->assertArrayHasKey('test2', $data);
		
		$this->assertArrayHasKey('en_UK', $data['test1']);
		$this->assertEquals('My text', $data['test1']['en_UK']);
	}

	/* ====================================================================== */
	
	public function testCantReadNonExisting()
	{
		$this->expectException(\Exception::class);
		$tmx = new \ArteQ\CSX\Tmx(__DIR__.'/missing.tmx', $create = false);
	}

	/* ====================================================================== */

	public function testCanGetByLang()
	{
		$data = $this->tmx->getLang('en_UK');

		$this->assertInternalType('array', $data);
		$this->assertCount(2, $data);

		$this->assertArrayHasKey('test1', $data);
		$this->assertArrayHasKey('test2', $data);

		$this->assertArrayHasKey('en_UK', $data['test1']);
		$this->assertEquals('My text', $data['test1']['en_UK']);
	}

	/* ====================================================================== */
	
	public function testCanGetByTuid()
	{
		$data = $this->tmx->get('test1');

		$this->assertInternalType('array', $data);
		$this->assertCount(1, $data);

		$this->assertArrayHasKey('en_UK', $data);
		$this->assertEquals('My text', $data['en_UK']);
	}

	/* ====================================================================== */
	
	public function testCanGetByTuidAndLang()
	{
		$data = $this->tmx->get('test2', 'en_UK');

		$this->assertInternalType('string', $data);
		$this->assertEquals('My second text', $data);
	}

	/* ====================================================================== */
		
	public function testCanCreate()
	{
		$file = __DIR__.'/out.tmx';
		@unlink($file);

		$tmx = new Tmx($file, $create = true);

		$headerProps = [
			'xxx' => 123,
			'yyy' => 'zzz',
		];
		$tmx->setHeaderProperties($headerProps);

		$tmx->set('id-123', 'pl_PL', 'tekst po polsku');
		$tmx->set('id-123', 'en_EN', 'english text');
		$tmx->setAttribute('id-123', 'changedate', date('Ymd\THis\Z') );
		$tmx->setAttribute('id-123', 'creationdate', date('Ymd\THis\Z') );
		$tmx->setAttribute('id-123', 'creationid', 'user-123');

		$tmx->setProperty('id-123', 'client', 'ACME Ltd.');
		$tmx->write();

		$this->assertFileExists($file);
		
		// $content = file_get_contents($file);
		@unlink($file);
	}

	/* ====================================================================== */
	
	public function testCanAddSegment()
	{
		$this->tmx->set('tuid-123', 'pl_PL', 'Tekst polski');
		$this->tmx->set('tuid-123', 'en_EN', 'English text');

		$data = $this->tmx->get('tuid-123');

		$this->assertInternalType('array', $data);
		$this->assertCount(2, $data);

		$this->assertArrayHasKey('en_EN', $data);
		$this->assertArrayHasKey('pl_PL', $data);
		$this->assertEquals('English text', $data['en_EN']);
		$this->assertEquals('Tekst polski', $data['pl_PL']);
	}

	/* ====================================================================== */
	
	public function testCanRemoveWholeSegment()
	{
		$this->tmx->delete('test2');

		$data = $this->tmx->get();

		$this->assertInternalType('array', $data);
		$this->assertCount(1, $data);

		$this->assertFalse(isset($data['test2']));
	}

	/* ====================================================================== */
	
	public function testCanRemoveSegmentPart()
	{
		$this->tmx->delete('test2', 'fr_FR');

		$data = $this->tmx->get();

		$this->assertInternalType('array', $data);
		$this->assertCount(2, $data);

		$this->assertTrue(isset($data['test2']['en_UK']));
		$this->assertFalse(isset($data['test2']['fr_FR']));
	}
}