<?php
/**
 * Tests for native Notes recipient selection.
 *
 * @package RtCamp\GoogleLogin
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use Mockery;
use ReflectionMethod;
use WP_Mock;
use RtCamp\GoogleLogin\Modules\NotesSlack;
use RtCamp\GoogleLogin\Tests\TestCase;

/**
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\NotesSlack
 */
class NotesSlackTest extends TestCase {
	/**
	 * An override from another Slack workspace must never be used.
	 *
	 * @covers ::override_id
	 */
	public function testOverrideIsScopedToConnectedWorkspace(): void {
		$module = new NotesSlack();
		$this->setTesteeProperty( $module, 'settings', [ 'team_id' => 'TNEW12345' ] );
		$user = Mockery::mock( 'WP_User' );
		$user->ID = 7;
		WP_Mock::userFunction( 'get_user_meta', [
			'args'   => [ 7, '_rtcamp_google_slack_user_id', true ],
			'return' => [ 'team_id' => 'TOLD12345', 'id' => 'U12345678' ],
		] );
		$method = new ReflectionMethod( NotesSlack::class, 'override_id' );
		$method->setAccessible( true );
		$this->assertSame( '', $method->invoke( $module, $user ) );
	}

	/**
	 * The profile's contact link opens a DM in the right workspace.
	 *
	 * @covers ::contact_admin
	 */
	public function testContactAdminUsesConfiguredAdministratorMapping(): void {
		$module = new NotesSlack();
		$this->setTesteeProperty( $module, 'settings', [ 'team_id' => 'T12345678', 'contact_admin_id' => 8, 'token' => 'xoxb-test' ] );
		$profile = Mockery::mock( 'WP_User' );
		$profile->ID = 7;
		$admin = Mockery::mock( 'WP_User' );
		$admin->ID = 8;
		$admin->roles = [ 'administrator' ];
		WP_Mock::userFunction( 'get_userdata', [ 'args' => [ 8 ], 'return' => $admin ] );
		WP_Mock::userFunction( 'get_user_meta', [
			'args'   => [ 8, '_rtcamp_google_slack_user_id', true ],
			'return' => [ 'team_id' => 'T12345678', 'id' => 'U12345678' ],
		] );
		$method = new ReflectionMethod( NotesSlack::class, 'contact_admin' );
		$method->setAccessible( true );
		$contact = $method->invoke( $module, $profile );
		$this->assertSame( 'slack://user?team=T12345678&id=U12345678', $contact['url'] );
	}

	/**
	 * @covers ::recipient_ids
	 */
	public function testReplyRecipientsAreDeduplicatedAndPermissionChecked(): void {
		$module = new NotesSlack();
		$this->setTesteeProperty(
			$module,
			'settings',
			[
				'notify_post_author'   => 1,
				'notify_mentions'      => 0,
				'notify_thread_author' => 1,
			]
		);

		$note = Mockery::mock( 'WP_Comment' );
		$note->comment_ID      = 12;
		$note->comment_post_ID = 42;
		$note->comment_parent  = 11;
		$note->user_id         = 1;
		$note->comment_content = 'Reply';

		$root = Mockery::mock( 'WP_Comment' );
		$root->comment_ID     = 11;
		$root->comment_parent = 0;
		$root->comment_type   = 'note';
		$root->user_id        = 3;

		WP_Mock::userFunction( 'get_post', [ 'args' => [ 42 ], 'return' => (object) [ 'post_author' => 2 ] ] );
		WP_Mock::userFunction( 'get_comment', [ 'args' => [ 11 ], 'return' => $root ] );
		WP_Mock::onFilter( 'rtcamp.google_notes_slack_recipient_ids' )->with( [ 2, 3 ], $note )->reply( [ 2, 3, 2, 1, 4 ] );
		WP_Mock::userFunction( 'user_can', [
			'args'            => [ \WP_Mock\Functions::type( 'int' ), 'edit_comment', 12 ],
			'return_in_order' => [ true, true, false ],
			'times'           => 3,
		] );

		$this->assertSame( [ 2, 3 ], $module->recipient_ids( $note ) );
	}
}
