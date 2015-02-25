<?php
/*
The MIT License (MIT)

Copyright (c) 2015 Twitter Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

namespace Twitter\Tests\WordPress\Shortcodes;

/**
 * @group shortcode
 * @coversDefaultClass \Twitter\WordPress\Shortcodes\Share
 */
final class Share extends \WP_UnitTestCase {
	/**
	 * Test extracting a parent Tweet ID from a shortcode through in_reply_to attribute
	 *
	 * @since 1.0.0
	 *
	 * @covers ::sanitizeShortcodeParameters
	 * @small
	 *
	 * @dataProvider sanitizeTweetIDProvider
	 *
	 * @param string $tweet_id Tweet ID test value
	 *
	 * @return void
	 */
	public function testSanitizeShortcodeParametersID( $tweet_id ) {
		$expected = '20';

		// test setting by id attribute
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'in_reply_to' => $tweet_id ) );
		$this->assertTrue( ( isset( $options['in_reply_to'] ) && $options['in_reply_to'] === $expected ), 'Failed to extract in_reply_to ID from attribute' );
	}

	/**
	 * Provide Tweet values which should all evaluate to a Tweet ID of 20
	 *
	 * @since 1.0.0
	 *
	 * @return array array of test values
	 */
	public static function sanitizeTweetIDProvider() {
		return array(
			array( 'https://twitter.com/jack/status/20' ),
			array( 'https://twitter.com/jack/statuses/20' ),
			array( '20' ),
			array( ' 20 ' ),
		);
	}

	/**
	 * Test extracting a Tweet text from a shortcode through text attribute
	 *
	 * @since 1.0.0
	 *
	 * @covers ::sanitizeShortcodeParameters
	 * @small
	 *
	 * @return void
	 */
	public function testSanitizeShortcodeParametersText() {
		$text = 'Hello world';
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'text' => $text ) );
		$this->assertTrue( ( isset( $options['text'] ) && $options['text'] === $text ), 'Failed to extract Tweet text from attribute' );

		// accept Tweet text over 140 characters in length
		$text = 'Stately, plump Buck Mulligan came from the stairhead, bearing a bowl of lather on which a mirror and a razor lay crossed. A yellow dressinggown, ungirdled, was sustained gently behind him on the mild morning air.';
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'text' => $text ) );
		$this->assertTrue( ( isset( $options['text'] ) && $options['text'] === $text ), 'Failed to extract lengthy Tweet text from attribute' );
	}

	/**
	 * Test setting URLs through shortcode attributes
	 *
	 * Covers both URL and countURL, which use the same code
	 *
	 * @since 1.0.0
	 *
	 * @covers ::sanitizeShortcodeParameters
	 * @small
	 *
	 * @return void
	 */
	public function testSanitizeShortcodeParametersURL() {
		foreach ( array( 'https://example.com/', ' https://example.com/ ' ) as $url ) {
			$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'url' => $url ) );
			$this->assertTrue( ( isset( $options['url'] ) && $options['url'] === trim( $url ) ), 'Failed to extract URL from attribute' );
			unset( $options );
		}

		$this->assertArrayNotHasKey(
			'url',
			\Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'url' => 'file://foo.jpg' ) ),
			'Accepted a URL not matching http or https scheme'
		);
	}

	/**
	 * Test setting related Twitter usernames through shortcode attribute
	 *
	 * @since 1.0.0
	 *
	 * @covers ::sanitizeShortcodeParameters
	 * @small
	 *
	 * @return void
	 */
	public function testSanitizeShortcodeParametersRelated() {
		// set by array
		$related = array( 'twitterdev' => '', 'twitterapi' => '' );
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'related' => $related ) );
		$this->assertTrue( ( isset( $options['related'] ) && $options['related'] === $related ), 'Failed to set two related accounts' );

		// set by CSV
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'related' => implode( ',', array_keys( $related ) ) ) );
		$this->assertTrue( ( isset( $options['related'] ) && $options['related'] === $related ), 'Failed to set two related accounts from CSV' );

		// set same account multiple times. should be collapsed
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'related' => array_merge( $related, array( '@TwitterDev' => '' ) ) ) );
		$this->assertTrue( ( isset( $options['related'] ) && $options['related'] === $related ), 'Failed to collapse multiple references to the same Twitter account' );
	}

	/**
	 * Test setting hashtags through shortcode attribute
	 *
	 * @since 1.0.0
	 *
	 * @covers ::sanitizeShortcodeParameters
	 * @small
	 *
	 * @return void
	 */
	public function testSanitizeShortcodeParametersHashtags() {
		// set by array
		$hashtags = array( 'foo', 'bar' );
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'hashtags' => $hashtags ) );
		$this->assertTrue( ( isset( $options['hashtags'] ) && $options['hashtags'] === $hashtags ), 'Failed to set two hashtags' );

		// set by CSV
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'hashtags' => implode( ',', $hashtags ) ) );
		$this->assertTrue( ( isset( $options['hashtags'] ) && $options['hashtags'] === $hashtags ), 'Failed to set two hashtags from CSV' );

		// set the same hashtags mutliple times. should be collapsed
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'hashtags' => array_merge( $hashtags, array( 'Foo', '#foo', '#Bar' ) ) ) );
		$this->assertTrue( ( isset( $options['hashtags'] ) && $options['hashtags'] === $hashtags ), 'Failed to collapse multiple references to the same hashtag' );
	}

	/**
	 * Test setting alignment of Tweet button through a shortcode attribute
	 *
	 * @since 1.0.0
	 *
	 * @covers ::sanitizeShortcodeParameters
	 * @small
	 *
	 * @dataProvider alignProvider
	 *
	 * @param string $align          align preference
	 * @param bool   $expected_valid expected validity
	 * @param string $message        error message to display on negative assertion
	 *
	 * @return void
	 */
	public function testSanitizeShortcodeParametersAlign( $align, $expected_valid, $message = '' ) {
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'align' => $align ) );

		if ( $expected_valid ) {
			$this->assertTrue( ( isset( $options['align'] ) && $options['align'] === $align ), $message );
		} else {
			$this->assertEmpty( $options, $message );
		}
	}

	/**
	 * Align values
	 *
	 * @since 1.0.0
	 *
	 * @return array align values {
	 *   @type array align value, expected validity, error message
	 * }
	 */
	public static function alignProvider() {
		return array(
			array( 'left', true, 'Failed to set a left button alignment' ),
			array( 'right', true, 'Failed to set a right button alignment' ),
			array( 'center', false, 'Allowed an invalid align value' ),
		);
	}

	/**
	 * Test setting a count display option from a shortcode attribute
	 *
	 * @since 1.0.0
	 *
	 * @covers ::sanitizeShortcodeParameters
	 * @small
	 *
	 * @dataProvider countProvider
	 *
	 * @param string $count          count display configuration
	 * @param bool   $expected_valid expected validity
	 * @param string $message        error message to display on negative assertion
	 *
	 * @return void
	 */
	public function testSanitizeShortcodeParametersCount( $count, $expected_valid, $message = '' ) {
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'count' => $count ) );

		if ( $expected_valid ) {
			$this->assertTrue( ( isset( $options['count'] ) && $options['count'] === $count ), $message );
		} else {
			$this->assertEmpty( $options, $message );
		}
	}

	/**
	 * Tweet button count configurations
	 *
	 * @since 1.0.0
	 *
	 * @return array count display configurations {
	 *   @type array type config, expected validity, error message
	 * }
	 */
	public static function countProvider() {
		return array(
			array( 'none', true, 'Failed to set a valid count of none' ),
			array( 'vertical', true, 'Failed to set a valid count of vertical' ),
			array( 'horizontal', false, 'Set an invalid count value' ),
		);
	}

	/**
	 * Test setting Tweet button sizes from a shortcode attribute
	 *
	 * @since 1.0.0
	 *
	 * @covers ::sanitizeShortcodeParameters
	 * @small
	 *
	 * @dataProvider sizeProvider
	 *
	 * @param string $size           button size configuration
	 * @param bool   $expected_valid expected validity
	 * @param string $message        error message to display on negative assertion
	 *
	 * @return void
	 */
	public function testSanitizeShortcodeParametersSize( $size, $expected_valid, $message = '' ) {
		$options = \Twitter\WordPress\Shortcodes\Share::sanitizeShortcodeParameters( array( 'size' => $size ) );

		if ( $expected_valid ) {
			$this->assertTrue( ( isset( $options['size'] ) && $options['size'] === 'large' ), $message );
		} else {
			$this->assertEmpty( $options, $message );
		}
	}

	/**
	 * Button sizes
	 *
	 * @since 1.0.0
	 *
	 * @return array sizes {
	 *   @type array size, expected validity, error message
	 * }
	 */
	public static function sizeProvider() {
		return array(
			array( 'large', true, 'Failed to set a valid size' ),
			array( 'L', true, 'Failed to set a valid size' ),
			array( 'small', false, 'Set an invalid size' ),
		);
	}
}
